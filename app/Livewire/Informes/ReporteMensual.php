<?php

namespace App\Livewire\Informes;

use App\Services\MetricasService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class ReporteMensual extends Component
{
    /** Mes del informe en formato YYYY-MM. */
    #[Url]
    public string $mes = '';

    public function mount(): void
    {
        if ($this->mes === '') {
            $this->mes = now()->format('Y-m');
        }
    }

    /** Rango [inicio, fin] del mes seleccionado. */
    private function rango(): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $this->mes)->startOfMonth();

        return [$inicio, $inicio->copy()->endOfMonth()];
    }

    public function getEtiquetaMesProperty(): string
    {
        [$inicio] = $this->rango();
        $meses = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        return $meses[$inicio->month] . ' ' . $inicio->year;
    }

    /**
     * Exporta el detalle por proyecto y por persona a un CSV (abre en Excel).
     */
    public function exportarCsv(MetricasService $metricas): StreamedResponse
    {
        [$desde, $hasta] = $this->rango();
        $porProyecto = $metricas->porProyecto($desde, $hasta);
        $porPersona  = $metricas->porPersona($desde, $hasta);
        $etiqueta    = $this->etiquetaMes;

        $nombre = 'reporte-cumplimiento-' . $this->mes . '.csv';

        return response()->streamDownload(function () use ($porProyecto, $porPersona, $etiqueta) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel reconozca UTF-8
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ["Reporte de cumplimiento - {$etiqueta}"]);
            fputcsv($out, []);

            fputcsv($out, ['CUMPLIMIENTO POR PROYECTO']);
            fputcsv($out, ['Proyecto', 'Responsable', 'Tareas', 'Completadas', 'A tiempo', '% Cumplimiento', 'Abiertas', 'Vencidas', 'Progreso %']);
            foreach ($porProyecto as $r) {
                fputcsv($out, [
                    $r['proyecto']->nombre,
                    $r['proyecto']->responsable?->name ?? 'Sin asignar',
                    $r['total'], $r['completadas'], $r['a_tiempo'],
                    $r['cumplimiento'] !== null ? $r['cumplimiento'] : 'N/A',
                    $r['abiertas'], $r['vencidas'], $r['progreso'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['CUMPLIMIENTO POR PERSONA']);
            fputcsv($out, ['Empleado', 'Area', 'Completadas', 'A tiempo', '% Cumplimiento', 'Abiertas', 'Vencidas']);
            foreach ($porPersona as $r) {
                fputcsv($out, [
                    $r['usuario']->name,
                    ucfirst($r['usuario']->area),
                    $r['completadas'], $r['a_tiempo'],
                    $r['cumplimiento'] !== null ? $r['cumplimiento'] : 'N/A',
                    $r['abiertas'], $r['vencidas'],
                ]);
            }

            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(MetricasService $metricas)
    {
        [$desde, $hasta] = $this->rango();

        return view('livewire.informes.reporte-mensual', [
            'resumen'     => $metricas->resumen($desde, $hasta),
            'porProyecto' => $metricas->porProyecto($desde, $hasta),
            'porPersona'  => $metricas->porPersona($desde, $hasta),
            'porTipo'     => $metricas->porTipo($desde, $hasta),
        ]);
    }
}
