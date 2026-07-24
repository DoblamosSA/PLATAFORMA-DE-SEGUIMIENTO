<?php

namespace App\Livewire\Colaboradores;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\DepartmentService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class FormColaborador extends Component
{
    use WithFileUploads;

    public ?User $colaborador = null;

    public bool $enModal = false;

    public string $name = '';

    public string $email = '';

    public string $telefono = '';

    public string $rol = 'tecnico';

    public string $cargo = '';

    public bool $activo = true;

    /** Nueva foto seleccionada (no se persiste hasta guardar). */
    public $foto = null;

    // La contraseña nunca se precarga ni se muestra; solo se aplica si se escribe.
    public string $password = '';

    public string $password_confirmation = '';

    /** Dias laborales seleccionados (subconjunto de User::DIAS). */
    public array $diasLaborales = [];

    public ?string $horasDiarias = null;

    public string $department_id = '';

    public string $sub_department_id = '';

    public string $role_id = '';

    public function mount(?User $colaborador = null, bool $enModal = false): void
    {
        // Crear/editar es una mutacion: puro permiso granular 'users.manage'
        // (el bypass universal es esSuperAdmin(), ya cubierto por Gate::before).
        abort_unless(Gate::allows('users.manage'), 403);

        $this->enModal = $enModal;

        if ($colaborador?->exists) {
            $this->colaborador = $colaborador;
            $this->name = $colaborador->name;
            $this->email = $colaborador->email;
            $this->telefono = $colaborador->telefono ?? '';
            $this->rol = $colaborador->rol ?? 'tecnico';
            $this->cargo = $colaborador->cargo ?? '';
            $this->activo = $colaborador->activo ?? true;
            $this->diasLaborales = $colaborador->dias_laborales ?? [];
            $this->horasDiarias = $colaborador->horas_diarias !== null ? (string) $colaborador->horas_diarias : null;

            $departamento = $colaborador->departments()->first();
            if ($departamento) {
                $this->department_id = (string) $departamento->id;
                $this->role_id = $departamento->pivot->role_id ? (string) $departamento->pivot->role_id : '';
            }

            $subDepartamento = $colaborador->subDepartments()->first();
            if ($subDepartamento) {
                $this->sub_department_id = (string) $subDepartamento->id;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->colaborador?->id)],
            'telefono' => 'nullable|string|max:30',
            'foto' => 'nullable|image|max:2048',
            'cargo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'password' => $this->colaborador
                ? 'nullable|string|min:8|confirmed'
                : 'required|string|min:8|confirmed',
            'diasLaborales' => 'required|array|min:1',
            'diasLaborales.*' => 'in:'.implode(',', User::DIAS),
            'horasDiarias' => 'required|numeric|min:0.01|max:12',
            'department_id' => 'required|exists:departments,id',
            // El subdepartamento (antes "area") debe pertenecer al departamento elegido.
            'sub_department_id' => ['required', Rule::exists('sub_departments', 'id')->where('department_id', $this->department_id)],
            'role_id' => 'nullable|exists:roles,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'diasLaborales.required' => 'Selecciona al menos un día laboral.',
            'diasLaborales.min' => 'Selecciona al menos un día laboral.',
            'horasDiarias.required' => 'Las horas diarias son obligatorias.',
            'horasDiarias.min' => 'Las horas diarias deben ser mayores a 0.',
            'horasDiarias.max' => 'Las horas diarias no pueden superar 12.',
            'department_id.required' => 'Selecciona un departamento.',
            'sub_department_id.required' => 'Selecciona un subdepartamento.',
            'sub_department_id.exists' => 'El subdepartamento no pertenece al departamento seleccionado.',
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($data['role_id']) {
            $rolSeleccionado = Role::find($data['role_id']);

            if ($rolSeleccionado?->slug === 'super-admin') {
                $this->addError('role_id', 'Super Administrador es un rol global y no puede asignarse como rol de departamento.');

                return;
            }
        }

        $esNuevo = ! $this->colaborador;
        $colaborador = $this->colaborador ?? new User;

        $colaborador->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?: null,
            // El rol global ya no se edita en este formulario: se conserva el
            // valor actual (o el default 'tecnico' al crear). El rol que se
            // gestiona aqui es el de departamento (role_id).
            'rol' => $this->rol,
            'cargo' => $data['cargo'] ?: null,
            'activo' => $data['activo'],
            'dias_laborales' => $data['diasLaborales'],
            'horas_diarias' => $data['horasDiarias'],
        ]);

        if (! empty($data['password'])) {
            $colaborador->password = Hash::make($data['password']);
        }

        if ($this->foto) {
            if ($colaborador->foto_path) {
                Storage::disk('public')->delete($colaborador->foto_path);
            }
            $colaborador->foto_path = $this->foto->store('colaboradores', 'public');
        }

        $colaborador->save();

        app(DepartmentService::class)->assignUserToDepartment(
            $colaborador,
            Department::findOrFail($data['department_id']),
            $data['role_id'] ? Role::find($data['role_id']) : null,
            esPrincipal: true,
        );

        // El subdepartamento (antes "area") se guarda en la relacion pivote;
        // el colaborador pertenece a un unico subdepartamento a la vez.
        $colaborador->subDepartments()->sync([$data['sub_department_id']]);

        AuditLog::registrar(
            $esNuevo ? 'colaborador_creado' : 'colaborador_actualizado',
            $colaborador,
            ($esNuevo ? 'Colaborador creado: ' : 'Colaborador actualizado: ').$colaborador->name." ({$colaborador->rolLabel()})",
        );

        $mensaje = $esNuevo ? 'Colaborador creado correctamente.' : 'Colaborador actualizado.';

        if ($this->enModal) {
            // El padre dispara el toast (ver ListaColaboradores::cerrarModal): su
            // elemento permanece estable, a diferencia del de este hijo justo
            // despues de esta misma accion (bug de Livewire con componentes
            // anidados montados dinamicamente - ver notas en cancelar()).
            $this->dispatch('cerrar-modal-colaborador', mensaje: $mensaje)->to('colaboradores.lista-colaboradores');

            return;
        }

        session()->flash('ok', $mensaje);
        $this->dispatch('app-toast', type: 'success', message: $mensaje);

        return $this->redirect(route('colaboradores'), navigate: true);
    }

    /**
     * ->to() en vez de dispatch() simple: este componente esta montado
     * dinamicamente dentro del modal del padre, y tras una accion Livewire
     * puede dejar su propio elemento en un estado que ya no propaga eventos
     * de forma confiable (bug de Livewire 3 con componentes anidados via @if:
     * el modal se quedaba abierto y no salia el toast). ->to() ubica al padre
     * por su nombre de componente y dispara el evento directo en su elemento,
     * sin depender del DOM de este hijo.
     */
    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-colaborador')->to('colaboradores.lista-colaboradores');
    }

    public function render()
    {
        $departamentos = Department::orderBy('nombre')->get(['id', 'nombre']);

        $subPorDepto = SubDepartment::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'department_id'])
            ->groupBy('department_id');

        // Los roles primarios son globales; se ofrecen en cualquier departamento.
        $rolesPrimarios = Role::where('is_primary', true)
            ->where('slug', '!=', 'super-admin')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $rolesPorDepto = Role::whereNotNull('department_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'department_id'])
            ->groupBy('department_id');

        // Estructura por departamento para la cascada en el cliente (Alpine):
        // { [departmentId]: { subdepartamentos: [{id,nombre}], roles: [{id,nombre}] } }
        $cascada = $departamentos->mapWithKeys(fn (Department $d) => [
            (string) $d->id => [
                'subdepartamentos' => ($subPorDepto[$d->id] ?? collect())
                    ->map(fn ($s) => ['id' => (string) $s->id, 'nombre' => $s->nombre])->values(),
                'roles' => $rolesPrimarios->concat($rolesPorDepto[$d->id] ?? collect())
                    ->map(fn ($r) => ['id' => (string) $r->id, 'nombre' => $r->nombre])->values(),
            ],
        ]);

        return view('livewire.colaboradores.form-colaborador', [
            'departamentos' => $departamentos,
            'cascada' => $cascada,
        ]);
    }
}
