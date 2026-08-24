<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Construye el libro Excel de agenda de tareas por proyecto: una hoja por
 * Project, con una fila por Subtask (o por Task cuando no tiene subtareas)
 * de sus tareas asignadas y no canceladas.
 */
class ReporteAgendaExcelService
{
    private const ENCABEZADO = [
        'Id_Colaborador', 'Nombre_Colaborador', 'Rol_Colaborador', 'Proyecto',
        'Sub_Tareas', 'Fecha Inicio', 'Fecha Fin', 'Duración',
    ];

    private const FORMATO_FECHA = 'dd/mm/yyyy hh:mm';

    public function __construct(private AgendaTareasService $agenda) {}

    public function construir(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $proyectos = Project::query()
            ->with(['equipo', 'tareas' => function ($query) {
                $query->whereNotNull('asignado_id')
                    ->where('estado', '!=', 'cancelada')
                    ->with(['asignado', 'subtareas']);
            }])
            ->orderBy('nombre')
            ->get();

        $nombresUsados = [];
        $totalFilas = 0;

        foreach ($proyectos as $project) {
            $filas = $this->filasDelProyecto($project);

            if ($filas === []) {
                Log::info('agenda_excel.proyecto_omitido', ['project_id' => $project->id]);

                continue;
            }

            $titulo = $this->tituloHojaUnico($project->nombre, $nombresUsados);
            $hoja = new Worksheet($spreadsheet, $titulo);
            $spreadsheet->addSheet($hoja);
            $this->escribirHoja($hoja, $filas);

            $totalFilas += count($filas);

            Log::debug('agenda_excel.hoja_generada', [
                'project_id' => $project->id,
                'nombre_hoja' => $titulo,
                'filas' => count($filas),
            ]);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $vacia = new Worksheet($spreadsheet, 'Sin datos');
            $spreadsheet->addSheet($vacia);
            $vacia->setCellValue('A1', 'No hay tareas asignadas (no canceladas) para exportar.');
        }

        $spreadsheet->setActiveSheetIndex(0);

        Log::info('agenda_excel.generado', [
            'total_hojas' => $spreadsheet->getSheetCount(),
            'total_filas' => $totalFilas,
        ]);

        return $spreadsheet;
    }

    /** @return array<int, array{0: int, 1: string, 2: string, 3: string, 4: string, 5: \Illuminate\Support\Carbon, 6: \Illuminate\Support\Carbon, 7: float}> */
    private function filasDelProyecto(Project $project): array
    {
        $equipoPorId = $project->equipo->keyBy('id');
        $filas = [];

        foreach ($project->tareas as $task) {
            $colaborador = $task->asignado;
            if (! $colaborador) {
                continue;
            }

            $miembro = $equipoPorId->get($colaborador->id);
            $rol = $miembro?->pivot?->rol_en_proyecto ?? 'Sin equipo';

            foreach ($this->agenda->agendaDeTarea($task) as $unidad) {
                $filas[] = [
                    $colaborador->id,
                    $colaborador->name,
                    $rol,
                    $project->nombre,
                    $unidad['etiqueta'],
                    $unidad['fecha_inicio'],
                    $unidad['fecha_fin'],
                    $unidad['horas'],
                ];
            }
        }

        return $filas;
    }

    private function escribirHoja(Worksheet $hoja, array $filas): void
    {
        $hoja->fromArray(self::ENCABEZADO, null, 'A1');

        $fila = 2;
        foreach ($filas as [$id, $nombre, $rol, $proyecto, $subtarea, $inicio, $fin, $horas]) {
            $hoja->setCellValue("A{$fila}", $id);
            $hoja->setCellValue("B{$fila}", $nombre);
            $hoja->setCellValue("C{$fila}", $rol);
            $hoja->setCellValue("D{$fila}", $proyecto);
            $hoja->setCellValue("E{$fila}", $subtarea);
            $hoja->setCellValue("F{$fila}", ExcelDate::dateTimeToExcel($inicio));
            $hoja->setCellValue("G{$fila}", ExcelDate::dateTimeToExcel($fin));
            $hoja->setCellValue("H{$fila}", $horas);

            $hoja->getStyle("F{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_FECHA);
            $hoja->getStyle("G{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_FECHA);

            $fila++;
        }

        foreach (range('A', 'H') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }
    }

    /** Genera un titulo de hoja valido para Excel (<=31 caracteres, sin \ / ? * [ ] :) y unico dentro del libro. */
    private function tituloHojaUnico(string $nombre, array &$usados): string
    {
        $limpio = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $nombre);
        $limpio = trim((string) $limpio) ?: 'Proyecto';
        $base = mb_substr($limpio, 0, 31);

        $titulo = $base;
        $sufijo = 2;
        while (in_array($titulo, $usados, true)) {
            $extra = " ({$sufijo})";
            $titulo = mb_substr($base, 0, 31 - mb_strlen($extra)).$extra;
            $sufijo++;
        }

        $usados[] = $titulo;

        return $titulo;
    }
}
