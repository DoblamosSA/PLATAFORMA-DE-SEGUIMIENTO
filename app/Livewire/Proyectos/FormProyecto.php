<?php

namespace App\Livewire\Proyectos;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormProyecto extends Component
{
    public ?Project $project = null;

    public bool $enModal = false;

    public string $nombre = '';

    public string $descripcion = '';

    public string $sub_department_id = '';

    public string $estado = 'planeado';

    public string $prioridad = 'media';

    public ?int $responsable_id = null;

    public ?string $fecha_inicio = null;

    public ?string $fecha_fin_estimada = null;

    /** IDs de los usuarios que integran el equipo del proyecto. */
    public array $equipo = [];

    public function mount(?Project $project = null, bool $enModal = false): void
    {
        // Solo el administrador y el coordinador pueden crear proyectos nuevos.
        abort_unless(! $project?->exists ? Auth::user()?->puedeCrearProyecto() : true, 403);

        $this->enModal = $enModal;

        if ($project?->exists) {
            $this->project = $project;
            $this->nombre = $project->nombre;
            $this->descripcion = $project->descripcion ?? '';
            $this->sub_department_id = (string) $project->sub_department_id;
            $this->estado = $project->estado;
            $this->prioridad = $project->prioridad;
            $this->responsable_id = $project->responsable_id;
            $this->fecha_inicio = $project->fecha_inicio?->format('Y-m-d');
            $this->fecha_fin_estimada = $project->fecha_fin_estimada?->format('Y-m-d');
            $this->equipo = $project->equipo()->pluck('users.id')->map(fn ($id) => (string) $id)->toArray();
        }
    }

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|min:3|max:255',
            'descripcion' => 'nullable|string',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'estado' => 'required|in:planeado,en_progreso,en_pausa,completado,cancelado',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'responsable_id' => 'nullable|exists:users,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
            'equipo' => 'array',
            'equipo.*' => 'exists:users,id',
        ];
    }

    public function save()
    {
        $esNuevo = ! $this->project;

        // Re-chequeo defensivo de permisos en el servidor (no solo en mount).
        abort_unless($esNuevo ? Auth::user()?->puedeCrearProyecto() : true, 403);

        $data = $this->validate();
        $equipo = $data['equipo'] ?? [];
        unset($data['equipo']);

        $project = $this->project ?? new Project;

        // Al pasar a completado, sellar fecha fin real
        if ($this->estado === 'completado' && ! $project->fecha_fin_real) {
            $data['fecha_fin_real'] = now();
        }

        $project->fill($data)->save();

        // Sincronizar el equipo del proyecto
        $project->equipo()->sync($equipo);

        session()->flash('ok', $esNuevo ? 'Proyecto creado.' : 'Proyecto actualizado.');

        if ($this->enModal) {
            $this->dispatch('cerrar-modal-proyecto');

            return;
        }

        return $this->redirect(route('proyectos.ver', $project), navigate: true);
    }

    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-proyecto');
    }

    public function render()
    {
        return view('livewire.proyectos.form-proyecto', [
            'lideres' => User::where('activo', true)
                ->whereIn('rol', ['admin', 'lider'])
                ->orderBy('name')
                ->get(),
            'empleados' => User::where('activo', true)->orderBy('name')->get(),
            'subDepartamentos' => SubDepartment::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
