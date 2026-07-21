<?php

namespace App\Livewire\Colaboradores;

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

    public function mount(?User $colaborador = null): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);

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
        }
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

        AuditLog::registrar(
            $esNuevo ? 'colaborador_creado' : 'colaborador_actualizado',
            $colaborador,
            ($esNuevo ? 'Colaborador creado: ' : 'Colaborador actualizado: ').$colaborador->name." ({$colaborador->rolLabel()})",
        );

        session()->flash('ok', $esNuevo ? 'Colaborador creado correctamente.' : 'Colaborador actualizado.');

        return $this->redirect(route('colaboradores'), navigate: true);
    }

    public function render()
    {
        return view('livewire.colaboradores.form-colaborador', [
            'capacidadSemanal' => $this->capacidadSemanal,
        ]);
    }
}
