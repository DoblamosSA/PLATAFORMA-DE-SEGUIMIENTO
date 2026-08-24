<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Services\AgendaTareasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaTareasServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgendaTareasService $servicio;

    private SubDepartment $subDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operativa.hora_inicio_jornada' => 8, 'operativa.minuto_inicio_jornada' => 0]);

        $this->servicio = app(AgendaTareasService::class);

        $department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);
    }

    private function colaborador(array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'rol' => 'tecnico',
            'dias_laborales' => ['L', 'M', 'X', 'J', 'V'],
            'horas_diarias' => 8,
        ], $extra));
    }

    private function tarea(User $colaborador, array $extra = []): Task
    {
        return Task::create(array_merge([
            'titulo' => 'Tarea',
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $colaborador->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'), // lunes
            'fecha_limite' => Carbon::parse('2026-08-17')->addDays(5),
            'horas_estimadas' => 8,
        ], $extra));
    }

    public function test_sin_subtareas_usa_horas_estimadas_como_unidad_unica(): void
    {
        $colaborador = $this->colaborador();
        $tarea = $this->tarea($colaborador, ['horas_estimadas' => 8]);

        $agenda = $this->servicio->agendaDeTarea($tarea);

        $this->assertCount(1, $agenda);
        $this->assertSame($tarea->titulo, $agenda[0]['etiqueta']);
        $this->assertSame(8.0, $agenda[0]['horas']);
        $this->assertTrue(Carbon::parse('2026-08-17 08:00')->equalTo($agenda[0]['fecha_inicio']));
        $this->assertTrue(Carbon::parse('2026-08-17 16:00')->equalTo($agenda[0]['fecha_fin']));
    }

    public function test_unidad_que_llena_exactamente_la_jornada_termina_el_mismo_dia(): void
    {
        $colaborador = $this->colaborador(['horas_diarias' => 8]);
        $tarea = $this->tarea($colaborador);
        $tarea->subtareas()->create(['titulo' => 'Unica', 'horas' => 8]);

        $agenda = $this->servicio->agendaDeTarea($tarea->fresh(['subtareas', 'asignado']));

        $this->assertCount(1, $agenda);
        $this->assertTrue($agenda[0]['fecha_inicio']->isSameDay($agenda[0]['fecha_fin']));
        $this->assertTrue(Carbon::parse('2026-08-17 16:00')->equalTo($agenda[0]['fecha_fin']));
    }

    public function test_subtareas_se_encadenan_de_forma_continua_dentro_de_la_misma_tarea(): void
    {
        $colaborador = $this->colaborador();
        $tarea = $this->tarea($colaborador, ['horas_estimadas' => 40]);
        $tarea->subtareas()->create(['titulo' => 'Sub 1', 'horas' => 14]);
        $tarea->subtareas()->create(['titulo' => 'Sub 2', 'horas' => 12]);

        $agenda = $this->servicio->agendaDeTarea($tarea->fresh(['subtareas', 'asignado']));

        $this->assertCount(2, $agenda);

        // Sub 1 (14h): lunes 08:00-16:00 (8h) + martes 08:00-14:00 (6h)
        $this->assertTrue(Carbon::parse('2026-08-17 08:00')->equalTo($agenda[0]['fecha_inicio']));
        $this->assertTrue(Carbon::parse('2026-08-18 14:00')->equalTo($agenda[0]['fecha_fin']));

        // Sub 2 (12h) continua exactamente donde quedo Sub 1 (martes 14:00,
        // sin redondear al dia siguiente): martes 14:00-16:00 (2h) +
        // miercoles 08:00-16:00 (8h) + jueves 08:00-10:00 (2h)
        $this->assertTrue(Carbon::parse('2026-08-18 14:00')->equalTo($agenda[1]['fecha_inicio']));
        $this->assertTrue(Carbon::parse('2026-08-20 10:00')->equalTo($agenda[1]['fecha_fin']));
    }

    public function test_una_unidad_larga_salta_el_fin_de_semana(): void
    {
        $colaborador = $this->colaborador();
        // viernes 2026-08-21; 16h = jornada completa del viernes + 8h que
        // deben saltar sabado/domingo y caer el lunes siguiente (2026-08-24)
        $tarea = $this->tarea($colaborador, [
            'fecha_inicio' => Carbon::parse('2026-08-21'),
            'fecha_limite' => Carbon::parse('2026-08-26'),
            'horas_estimadas' => 16,
        ]);

        $agenda = $this->servicio->agendaDeTarea($tarea);

        $this->assertCount(1, $agenda);
        $this->assertTrue(Carbon::parse('2026-08-21 08:00')->equalTo($agenda[0]['fecha_inicio']));
        $this->assertTrue(Carbon::parse('2026-08-24 16:00')->equalTo($agenda[0]['fecha_fin']));
    }

    public function test_colaborador_sin_horas_diarias_configuradas_no_lanza_excepcion(): void
    {
        $colaborador = $this->colaborador(['horas_diarias' => 0]);
        $tarea = $this->tarea($colaborador, ['horas_estimadas' => 8]);

        $agenda = $this->servicio->agendaDeTarea($tarea);

        $this->assertCount(1, $agenda);
        $this->assertTrue($agenda[0]['fecha_inicio']->equalTo($agenda[0]['fecha_fin']));
    }

    public function test_colaborador_sin_dias_laborales_configurados_no_lanza_excepcion(): void
    {
        $colaborador = $this->colaborador(['dias_laborales' => []]);
        $tarea = $this->tarea($colaborador, ['horas_estimadas' => 8]);

        $agenda = $this->servicio->agendaDeTarea($tarea);

        $this->assertCount(1, $agenda);
        $this->assertTrue($agenda[0]['fecha_inicio']->equalTo($agenda[0]['fecha_fin']));
    }

    public function test_tarea_sin_colaborador_asignado_devuelve_agenda_vacia(): void
    {
        $tarea = Task::create([
            'titulo' => 'Sin asignar',
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
            'fecha_inicio' => Carbon::parse('2026-08-17'),
            'fecha_limite' => Carbon::parse('2026-08-20'),
            'horas_estimadas' => 8,
        ]);

        $this->assertSame([], $this->servicio->agendaDeTarea($tarea));
    }
}
