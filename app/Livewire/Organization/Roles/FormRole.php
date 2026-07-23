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

    public bool $soloLectura = false;

    public string $nombre = '';

    public string $parent_role_id = '';

    public string $department_id = '';

    /** Overrides explicitos de este rol, indexados por permission_id: 'grant'|'deny'. Ausente = hereda. */
    public array $overrides = [];

    public function mount(PermissionRepositoryInterface $permissions, ?Role $role = null): void
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);

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

    /** Slugs de permisos que el padre seleccionado concede efectivamente (usado como sugerencia por defecto del switch). */
    public function getPermisosHeredadosProperty(): array
    {
        if (! $this->parent_role_id) {
            return [];
        }

        $padre = Role::find($this->parent_role_id);

        if (! $padre) {
            return [];
        }

        return app(PermissionResolutionService::class)->resolveEffectivePermissions($padre)->permissionSlugs;
    }

    /** Estado efectivo (encendido/apagado) del switch de un permiso. */
    public function permisoActivo(Permission $permiso): bool
    {
        return $this->estadoEfectivo($permiso->id, $permiso->slug);
    }

    /**
     * True si el switch de este permiso no se puede tocar: el formulario entero
     * es de solo lectura, o es un rol heredado intentando tocar un grupo
     * reservado (departamentos/usuarios) que solo los roles primarios pueden
     * conceder.
     */
    public function permisoBloqueado(Permission $permiso): bool
    {
        if ($this->soloLectura) {
            return true;
        }

        return ! $this->esPrimario() && in_array($permiso->grupo, self::GRUPOS_BLOQUEADOS_EN_HEREDADOS, true);
    }

    /** Alterna el switch de un permiso, guardando solo el override necesario respecto a la sugerencia del padre. */
    public function togglePermiso(int $permisoId, string $slug): void
    {
        $permiso = Permission::find($permisoId);

        if (! $permiso || $this->permisoBloqueado($permiso)) {
            return;
        }

        $sugerido = in_array($slug, $this->permisosHeredados, true);
        $nuevo = ! $this->estadoEfectivo($permisoId, $slug);

        $this->overrides[$permisoId] = $nuevo === $sugerido ? 'heredado' : ($nuevo ? 'grant' : 'deny');
    }

    private function estadoEfectivo(int $permisoId, string $slug): bool
    {
        return match ($this->overrides[$permisoId] ?? 'heredado') {
            'grant' => true,
            'deny' => false,
            default => in_array($slug, $this->permisosHeredados, true),
        };
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
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);
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

        session()->flash('ok', $this->role ? 'Rol actualizado.' : 'Rol creado correctamente.');

        return $this->redirect(route('roles'), navigate: true);
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

    public function render(PermissionRepositoryInterface $permissions, RoleService $roleService)
    {
        return view('livewire.organization.roles.form-role', [
            'gruposPermisos' => $permissions->allGroupedByGrupo(),
            'rolesPadre' => $roleService->assignableParentsFor($this->role),
            'departamentos' => Department::orderBy('nombre')->get(),
        ]);
    }
}
