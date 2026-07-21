<?php

namespace App\Livewire;

use App\Models\Task;
use App\Services\MetricasService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Url]
    public string $rango = 'mes';

    public function periodos(): array
    {
        return [
            'semana'    => 'Esta semana',
            'mes'       => 'Este mes',
            'trimestre' => 'Este trimestre',
            'anio'      => 'Este ano',
        ];
    }

    private function fechas(): array
    {
        return match ($this->rango) {
            'semana'    => [now()->startOfWeek(), now()->endOfDay()],
            'trimestre' => [now()->firstOfQuarter(), now()->endOfDay()],
            'anio'      => [now()->startOfYear(), now()->endOfDay()],
            default     => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    public function render(MetricasService $metricas)
    {
        [$desde, $hasta] = $this->fechas();

        return view('livewire.dashboard', [
            'resumen'    => $metricas->resumen($desde, $hasta),
            'porTipo'    => $metricas->porTipo($desde, $hasta),
            'porPersona' => $metricas->porPersona($desde, $hasta),
            'proximasVencer' => Task::abiertas()
                ->whereNotNull('fecha_limite')
                ->where('fecha_limite', '>=', now())
                ->orderBy('fecha_limite')
                ->with(['asignado', 'proyecto'])
                ->limit(6)
                ->get(),
            'vencidas' => Task::vencidas()
                ->with(['asignado', 'proyecto'])
                ->orderBy('fecha_limite')
                ->limit(6)
                ->get(),
            'periodos' => $this->periodos(),
            'rango' => $this->rango,
        ]);
    }
}
