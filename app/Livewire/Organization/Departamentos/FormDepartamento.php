<?php

namespace App\Livewire\Organization\Departamentos;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\DepartmentService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormDepartamento extends Component
{
    public ?Department $department = null;

    public bool $enModal = false;

    public string $nombre = '';

    public string $descripcion = '';

    public string $responsable_id = '';

    public bool $activo = true;

    public function mount(?Department $department = null, bool $enModal = false): void
    {
        $this->authorize($department?->exists ? 'update' : 'create', $department ?? Department::class);

        $this->enModal = $enModal;

        if ($department?->exists) {
            $this->department = $department;
            $this->nombre = $department->nombre;
            $this->descripcion = $department->descripcion ?? '';
            $this->responsable_id = $department->responsable_id ? (string) $department->responsable_id : '';
            $this->activo = $department->activo;
        }
    }

    /**
     * Notifica al padre para que cierre el modal, en vez de dispatch()
     * (que dispara el evento desde el elemento de ESTE componente). Este
     * componente esta montado dinamicamente dentro del modal del padre, y
     * tras una accion (guardar) Livewire puede dejar su propio elemento en
     * un estado que ya no propaga eventos de forma confiable (bug conocido
     * de Livewire 3 con componentes anidados montados via @if: el modal se
     * quedaba abierto y no salia el toast). ->to() ubica al padre por su
     * nombre de componente y dispara el evento directo en SU elemento,
     * sin depender en absoluto del DOM de este hijo.
     */
    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-departamento')->to('organization.departamentos.lista-departamentos');
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:255', Rule::unique('departments', 'nombre')->ignore($this->department?->id)],
            'descripcion' => 'nullable|string|max:1000',
            'responsable_id' => ['nullable', Rule::in($this->usuariosAdministradores()->pluck('id')->all())],
            'activo' => 'boolean',
        ];
    }

    /**
     * Usuarios elegibles como responsable: quienes tienen el rol legado
     * `admin` (cuentas antiguas, nunca migradas a un rol de departamento) O
     * cuyo rol de departamento (department_user.role_id) desciende del rol
     * primario "Administrador" o "Super Administrador" en la jerarquia de
     * roles. Antes solo se miraba el enum legado, por lo que alguien recien
     * ascendido a Administrador via el formulario de colaborador (que ya no
     * toca ese enum) nunca aparecia aqui.
     */
    private function usuariosAdministradores(): Collection
    {
        $roles = Role::all()->keyBy('id');

        $raizSlug = function (?int $roleId) use ($roles): ?string {
            $visitados = [];
            while ($roleId && ! in_array($roleId, $visitados, true)) {
                $visitados[] = $roleId;
                $role = $roles->get($roleId);
                if (! $role) {
                    return null;
                }
                if ($role->parent_role_id === null) {
                    return $role->slug;
                }
                $roleId = $role->parent_role_id;
            }

            return null;
        };

        return User::where('activo', true)
            ->with('departments')
            ->get()
            ->filter(function (User $u) use ($raizSlug) {
                if ($u->rol === 'admin') {
                    return true;
                }

                $roleId = $u->departments->first()?->pivot->role_id;

                return in_array($raizSlug($roleId), ['admin', 'super-admin'], true);
            })
            ->sortBy('name')
            ->values();
    }

    public function save(DepartmentService $service)
    {
        $data = $this->validate();

        $esNuevo = ! $this->department;
        $slug = $esNuevo ? $this->generarSlugUnico($data['nombre']) : $this->department->slug;

        $dto = new DepartmentData(
            id: $this->department?->id,
            nombre: $data['nombre'],
            slug: $slug,
            descripcion: $data['descripcion'] ?: null,
            responsableId: $data['responsable_id'] ? (int) $data['responsable_id'] : null,
            activo: $data['activo'],
        );

        $esNuevo ? $service->create($dto) : $service->update($this->department, $dto);

        $mensaje = $esNuevo ? 'Departamento creado correctamente.' : 'Departamento actualizado.';

        if ($this->enModal) {
            // El padre es quien dispara el toast (ver ListaDepartamentos::cerrarModal):
            // su elemento permanece estable, a diferencia del de este hijo justo
            // despues de esta misma accion.
            $this->dispatch('cerrar-modal-departamento', mensaje: $mensaje)->to('organization.departamentos.lista-departamentos');

            return;
        }

        session()->flash('ok', $mensaje);
        $this->dispatch('app-toast', type: 'success', message: $mensaje);

        return $this->redirect(route('departamentos'), navigate: true);
    }

    private function generarSlugUnico(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i = 1;

        while (Department::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.organization.departamentos.form-departamento', [
            'administradores' => $this->usuariosAdministradores(),
        ]);
    }
}
