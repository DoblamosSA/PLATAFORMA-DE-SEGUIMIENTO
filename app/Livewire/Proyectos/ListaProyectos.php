<?php

namespace App\Livewire\Proyectos;

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaProyectos extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $estado = '';

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $proyectos = Project::query()
            ->withCount([
                'tareas',
                'tareas as tareas_completadas_count' => fn ($q) => $q->where('estado', 'completada'),
                'tareas as tareas_vencidas_count' => fn ($q) => $q->vencidas(),
            ])
            ->with('responsable')
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->latest()
            ->paginate(12);

        return view('livewire.proyectos.lista-proyectos', [
            'proyectos' => $proyectos,
        ]);
    }
}
