<?php

namespace App\Livewire\Colaboradores;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\DepartmentService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

    public string $area = 'general';

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

    public string $role_id = '';

    public function mount(?User $colaborador = null, bool $enModal = false): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);

        $this->enModal = $enModal;

        if ($colaborador?->exists) {
            $this->colaborador = $colaborador;
            $this->name = $colaborador->name;
            $this->email = $colaborador->email;
            $this->telefono = $colaborador->telefono ?? '';
            $this->rol = $colaborador->rol ?? 'tecnico';
            $this->area = $colaborador->area ?? 'general';
            $this->cargo = $colaborador->cargo ?? '';
            $this->activo = $colaborador->activo ?? true;
            $this->diasLaborales = $colaborador->dias_laborales ?? [];
            $this->horasDiarias = $colaborador->horas_diarias !== null ? (string) $colaborador->horas_diarias : null;

            $departamento = $colaborador->departments()->first();
            if ($departamento) {
                $this->department_id = (string) $departamento->id;
                $this->role_id = $departamento->pivot->role_id ? (string) $departamento->pivot->role_id : '';
            }
        }
    }

    /**
     * Roles asignables dentro del departamento elegido: primarios (globales) + heredados de ese departamento.
     * Super Administrador es un rol global, no un rol de departamento, por lo que nunca aparece aqui.
     */
    public function getRolesDisponiblesProperty()
    {
        $query = $this->department_id
            ? Role::where('is_primary', true)->orWhere('department_id', $this->department_id)
            : Role::where('is_primary', true);

        return $query->orderBy('nombre')->get()
            ->reject(fn (Role $r) => $r->slug === 'super-admin')
            ->values();
    }

    /** Capacidad semanal en vivo: dias seleccionados x horas diarias. */
    public function getCapacidadSemanalProperty(): float
    {
        return count($this->diasLaborales) * (float) ($this->horasDiarias ?? 0);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->colaborador?->id)],
            'telefono' => 'nullable|string|max:30',
            'foto' => 'nullable|image|max:2048',
            'rol' => 'required|in:'.implode(',', array_keys(User::ROLES_LABEL)),
            'area' => 'required|in:software,soporte,infraestructura,general',
            'cargo' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'password' => $this->colaborador
                ? 'nullable|string|min:8|confirmed'
                : 'required|string|min:8|confirmed',
            'diasLaborales' => 'required|array|min:1',
            'diasLaborales.*' => 'in:'.implode(',', User::DIAS),
            'horasDiarias' => 'required|numeric|min:0.01|max:12',
            'department_id' => 'nullable|exists:departments,id',
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
            'rol' => $data['rol'],
            'area' => $data['area'],
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

        if ($data['department_id']) {
            app(DepartmentService::class)->assignUserToDepartment(
                $colaborador,
                Department::findOrFail($data['department_id']),
                $data['role_id'] ? Role::find($data['role_id']) : null,
                esPrincipal: true,
            );
        }

        AuditLog::registrar(
            $esNuevo ? 'colaborador_creado' : 'colaborador_actualizado',
            $colaborador,
            ($esNuevo ? 'Colaborador creado: ' : 'Colaborador actualizado: ').$colaborador->name." ({$colaborador->rolLabel()})",
        );

        session()->flash('ok', $esNuevo ? 'Colaborador creado correctamente.' : 'Colaborador actualizado.');
        $this->dispatch('app-toast', type: 'success', message: $esNuevo ? 'Colaborador creado correctamente.' : 'Colaborador actualizado.');

        if ($this->enModal) {
            $this->dispatch('cerrar-modal-colaborador');

            return;
        }

        return $this->redirect(route('colaboradores'), navigate: true);
    }

    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-colaborador');
    }

    public function render()
    {
        return view('livewire.colaboradores.form-colaborador', [
            'capacidadSemanal' => $this->capacidadSemanal,
        ]);
    }
}
