<?php

namespace App\Livewire\Organization\Roles;

use App\Domain\Organization\DTOs\RoleData;
use App\Domain\Organization\Exceptions\RoleHierarchyException;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\PermissionRepositoryInterface;
use App\Domain\Organization\Services\PermissionResolutionService;
use App\Domain\Organization\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormRole extends Component
{
    /** Grupos de permisos que un rol heredado (no primario) nunca puede otorgar/revocar, para que no escale privilegios sobre departamentos o usuarios. */
    private const GRUPOS_BLOQUEADOS_EN_HEREDADOS = ['departments', 'users'];

    public ?Role $role = null;

    public bool $enModal = false;

    public bool $soloLectura = false;

    public string $nombre = '';

    public string $parent_role_id = '';

    public string $department_id = '';

    /** Overrides explicitos de este rol, indexados por permission_id: 'grant'|'deny'. Ausente = hereda. */
    public array $overrides = [];

    public function mount(PermissionRepositoryInterface $permissions, ?Role $role = null, bool $enModal = false): void
    {
        // Abrir el formulario (crear o editar) solo requiere poder VER roles;
        // save() vuelve a validar segun sea creacion o edicion.
        abort_unless(Gate::allows('roles.view'), 403);

        $this->enModal = $enModal;

        foreach ($permissions->all() as $permiso) {
            $this->overrides[$permiso->id] = 'heredado';
        }

        if ($role?->exists) {
            $this->role = $role;
            $this->soloLectura = $role->is_primary && ! Auth::user()->esSuperAdmin();
            $this->nombre = $role->nombre;
            $this->parent_role_id = (string) $role->parent_role_id;
            $this->department_id = (string) $role->department_id;

            foreach ($role->grantedPermissions as $permiso) {
                $this->overrides[$permiso->id] = 'grant';
            }
            foreach ($role->deniedPermissions as $permiso) {
                $this->overrides[$permiso->id] = 'deny';
            }
        }
    }

    /** Los roles primarios son globales: no tienen nombre editable, no heredan de otro rol y no pertenecen a un departamento. Solo su matriz de permisos se puede ajustar. */
    private function esPrimario(): bool
    {
        return $this->role?->is_primary ?? false;
    }

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|min:2|max:255',
            'parent_role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
        ];
    }

    public function save(RoleService $service, PermissionRepositoryInterface $permissions)
    {
        // Puro permiso granular: 'roles.create' al crear, 'roles.edit' al
        // editar (rol primario o heredado existente).
        abort_unless(Gate::allows($this->role ? 'roles.edit' : 'roles.create'), 403);
        abort_if($this->soloLectura, 403);

        $esPrimario = $this->esPrimario();

        // Los roles primarios son globales: no tienen nombre/padre/departamento
        // editables (esos inputs ni se muestran), asi que no hay nada que validar.
        $data = $esPrimario ? [] : $this->validate();

        if (! $esPrimario) {
            $idsBloqueados = Permission::whereIn('grupo', self::GRUPOS_BLOQUEADOS_EN_HEREDADOS)->pluck('id')->all();
            foreach ($idsBloqueados as $id) {
                unset($this->overrides[$id]);
            }
        }

        $grantIds = array_keys(array_filter($this->overrides, fn ($v) => $v === 'grant'));
        $denyIds = array_keys(array_filter($this->overrides, fn ($v) => $v === 'deny'));

        $grantedSlugs = Permission::whereIn('id', $grantIds)->pluck('slug')->all();
        $revokedSlugs = Permission::whereIn('id', $denyIds)->pluck('slug')->all();

        if ($esPrimario) {
            $service->updatePermissions($this->role, $grantedSlugs, $revokedSlugs);
        } elseif ($this->role) {
            $nuevoPadre = Role::findOrFail($data['parent_role_id']);

            try {
                $service->updateParent($this->role, $nuevoPadre);
            } catch (RoleHierarchyException $e) {
                $this->addError('parent_role_id', $e->getMessage());

                return;
            }

            $this->role->update([
                'nombre' => $data['nombre'],
                'department_id' => $data['department_id'],
            ]);
            $service->updatePermissions($this->role, $grantedSlugs, $revokedSlugs);
        } else {
            $service->createInheritedRole(new RoleData(
                id: null,
                nombre: $data['nombre'],
                slug: $this->generarSlugUnico($data['nombre']),
                parentRoleId: (int) $data['parent_role_id'],
                departmentId: (int) $data['department_id'],
                isPrimary: false,
                isDeletable: true,
            ), $grantedSlugs, $revokedSlugs);
        }

        $mensaje = $this->role ? 'Rol actualizado.' : 'Rol creado correctamente.';

        if ($this->enModal) {
            // El padre dispara el toast (ver ListaRoles::cerrarModal): ver el
            // comentario en cancelar() sobre por que se usa ->to() aqui.
            $this->dispatch('cerrar-modal-rol', mensaje: $mensaje)->to('organization.roles.lista-roles');

            return;
        }

        session()->flash('ok', $mensaje);
        $this->dispatch('app-toast', type: 'success', message: $mensaje);

        return $this->redirect(route('roles'), navigate: true);
    }

    /**
     * ->to() en vez de dispatch() simple: este componente esta montado
     * dinamicamente dentro del modal del padre, y tras una accion Livewire
     * puede dejar su propio elemento en un estado que ya no propaga eventos
     * de forma confiable (bug de Livewire 3 con componentes anidados via @if
     * - el modal se quedaba abierto y no salia el toast, especialmente
     * notorio aqui por los muchos toggles de permisos antes de guardar).
     * ->to() ubica al padre por nombre y dispara el evento directo en su
     * elemento, sin depender del DOM de este hijo.
     */
    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-rol')->to('organization.roles.lista-roles');
    }

    private function generarSlugUnico(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /**
     * Precalcula toda la data que el selector de rol padre y la matriz de
     * permisos necesitan para reaccionar en el cliente (Alpine), sin volver
     * al servidor por cada seleccion de padre o cada toggle de permiso. Ese
     * patron de muchas idas y vueltas antes de guardar es justo lo que hacia
     * fragil este formulario dentro del modal (Livewire puede perder el
     * snapshot del componente anidado en cualquiera de esos commits
     * intermedios, no solo en el de guardar).
     */
    public function render(PermissionRepositoryInterface $permissions, RoleService $roleService, PermissionResolutionService $resolucion)
    {
        $rolesPadre = $roleService->assignableParentsFor($this->role);

        // { [parentRoleId]: [slug1, slug2, ...] } - permisos que ese padre concede efectivamente.
        $permisosPorPadre = $rolesPadre->mapWithKeys(fn (Role $rp) => [
            (string) $rp->id => $resolucion->resolveEffectivePermissions($rp)->permissionSlugs,
        ])->all();

        // { [permisoId]: bool } - true si su grupo esta reservado a roles primarios.
        $gruposBloqueadosPorPermiso = collect($permissions->all())->mapWithKeys(fn (Permission $p) => [
            (string) $p->id => in_array($p->grupo, self::GRUPOS_BLOQUEADOS_EN_HEREDADOS, true),
        ])->all();

        return view('livewire.organization.roles.form-role', [
            'gruposPermisos' => $permissions->allGroupedByGrupo(),
            'rolesPadre' => $rolesPadre,
            'departamentos' => Department::orderBy('nombre')->get(),
            'permisosPorPadre' => $permisosPorPadre,
            'gruposBloqueadosPorPermiso' => $gruposBloqueadosPorPermiso,
            'esPrimario' => $this->esPrimario(),
        ]);
    }
}
