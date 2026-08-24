<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Livewire\Informes\ReporteMensual;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ReporteAgendaExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReporteAgendaExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operativa.hora_inicio_jornada' => 8, 'operativa.minuto_inicio_jornada' => 0]);
    }

    public function test_el_libro_tiene_una_hoja_por_proyecto_con_tareas_calificadas(): void
    {
        $department = Department::factory()->create();
        $subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);

        $colaborador = User::factory()->create([
            'name' => 'Daniel Daza',
            'rol' => 'tecnico',
            'dias_laborales' => ['L', 'M', 'X', 'J', 'V'],
            'horas_diarias' => 8,
        ]);

        $conDatos = Project::create([
            'nombre' => 'Cotizador',
            'sub_department_id' => $subDepartment->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);
        $conDatos->equipo()->attach($colaborador->id, ['rol_en_proyecto' => 'desarrollador']);

        $tareaConSubtareas = Task::create([
            'project_id' => $conDatos->id,
            'titulo' => 'Tarea con subtareas',
            'sub_department_id' => $subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $colaborador->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'),
            'fecha_limite' => Carbon::parse('2026-08-20'),
            'horas_estimadas' => 8,
        ]);
        $tareaConSubtareas->subtareas()->create(['titulo' => 'Diseño', 'horas' => 8]);

        Task::create([
            'project_id' => $conDatos->id,
            'titulo' => 'Tarea sin subtareas',
            'sub_department_id' => $subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $colaborador->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'),
            'fecha_limite' => Carbon::parse('2026-08-18'),
            'horas_estimadas' => 8,
        ]);

        // Proyecto sin ninguna tarea calificada (la unica tarea esta cancelada): se omite del libro.
        $sinDatos = Project::create([
            'nombre' => 'Sin tareas activas',
            'sub_department_id' => $subDepartment->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);
        Task::create([
            'project_id' => $sinDatos->id,
            'titulo' => 'Cancelada',
            'sub_department_id' => $subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'cancelada',
            'asignado_id' => $colaborador->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'),
            'fecha_limite' => Carbon::parse('2026-08-18'),
            'horas_estimadas' => 8,
        ]);

        $spreadsheet = app(ReporteAgendaExcelService::class)->construir();

        $this->assertSame(1, $spreadsheet->getSheetCount());

        $hoja = $spreadsheet->getSheet(0);
        $this->assertSame('Cotizador', $hoja->getTitle());
        $this->assertSame(3, $hoja->getHighestRow()); // encabezado + 2 filas (1 subtarea + 1 tarea sin subtareas)

        $encabezado = [];
        foreach (range('A', 'H') as $col) {
            $encabezado[] = $hoja->getCell("{$col}1")->getValue();
        }
        $this->assertSame(
            ['Id_Colaborador', 'Nombre_Colaborador', 'Rol_Colaborador', 'Proyecto', 'Sub_Tareas', 'Fecha Inicio', 'Fecha Fin', 'Duración'],
            $encabezado
        );

        $this->assertSame($colaborador->id, $hoja->getCell('A2')->getValue());
        $this->assertSame('Daniel Daza', $hoja->getCell('B2')->getValue());
        $this->assertSame('desarrollador', $hoja->getCell('C2')->getValue());
        $this->assertSame('Cotizador', $hoja->getCell('D2')->getValue());
        $this->assertSame('Diseño', $hoja->getCell('E2')->getValue());
        $this->assertSame(8.0, (float) $hoja->getCell('H2')->getValue());

        $this->assertSame('Tarea sin subtareas', $hoja->getCell('E3')->getValue());
    }

    public function test_un_colaborador_fuera_del_equipo_se_marca_sin_equipo(): void
    {
        $department = Department::factory()->create();
        $subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);

        $colaborador = User::factory()->create([
            'rol' => 'tecnico',
            'dias_laborales' => ['L', 'M', 'X', 'J', 'V'],
            'horas_diarias' => 8,
        ]);

        $project = Project::create([
            'nombre' => 'Proyecto huerfano',
            'sub_department_id' => $subDepartment->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);
        // Nota: el colaborador NO se agrega a $project->equipo().

        Task::create([
            'project_id' => $project->id,
            'titulo' => 'Tarea',
            'sub_department_id' => $subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $colaborador->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'),
            'fecha_limite' => Carbon::parse('2026-08-18'),
            'horas_estimadas' => 8,
        ]);

        $spreadsheet = app(ReporteAgendaExcelService::class)->construir();
        $hoja = $spreadsheet->getSheet(0);

        $this->assertSame('Sin equipo', $hoja->getCell('C2')->getValue());
    }

    public function test_el_boton_de_exportar_agenda_no_lanza_errores(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ReporteMensual::class)
            ->call('exportarAgendaExcel')
            ->assertOk();
    }
}
