<?php

namespace App\Livewire\Proyectos;

use App\Models\Project;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormProyecto extends Component
{
    public ?Project $project = null;

    public string $nombre = '';
    public string $descripcion = '';
    public string $tipo = 'software';
    public string $estado = 'planeado';
    public string $prioridad = 'media';
    public ?int $responsable_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin_estimada = null;

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->project            = $project;
            $this->nombre             = $project->nombre;
            $this->descripcion        = $project->descripcion ?? '';
            $this->tipo               = $project->tipo;
            $this->estado             = $project->estado;
            $this->prioridad          = $project->prioridad;
            $this->responsable_id     = $project->responsable_id;
            $this->fecha_inicio       = $project->fecha_inicio?->format('Y-m-d');
            $this->fecha_fin_estimada = $project->fecha_fin_estimada?->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        return [
            'nombre'             => 'required|string|min:3|max:255',
            'descripcion'        => 'nullable|string',
            'tipo'               => 'required|in:software,soporte,infraestructura',
            'estado'             => 'required|in:planeado,en_progreso,en_pausa,completado,cancelado',
            'prioridad'          => 'required|in:baja,media,alta,critica',
            'responsable_id'     => 'nullable|exists:users,id',
            'fecha_inicio'       => 'nullable|date',
            'fecha_fin_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $esNuevo = ! $this->project;
        $project = $this->project ?? new Project();

        // Al pasar a completado, sellar fecha fin real
        if ($this->estado === 'completado' && ! $project->fecha_fin_real) {
            $data['fecha_fin_real'] = now();
        }

        $project->fill($data)->save();

        session()->flash('ok', $esNuevo ? 'Proyecto creado.' : 'Proyecto actualizado.');

        return $this->redirect(route('proyectos'), navigate: true);
    }

    public function render()
    {
        return view('livewire.proyectos.form-proyecto', [
            'lideres' => User::where('activo', true)
                ->whereIn('rol', ['admin', 'lider'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
