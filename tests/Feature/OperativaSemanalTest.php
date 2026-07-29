<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Livewire\Informes\EvaluadorColaboradores;
use App\Models\Task;
use App\Models\User;
use App\Services\CapacidadService;
use App\Services\CierreSemanalService;
use App\Services\EvaluadorRendimientoService;
use App\Services\HorarioLaboralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class OperativaSemanalTest extends TestCase
{
    use RefreshDatabase;

    private CapacidadService $capacidad;

    private HorarioLaboralService $horario;

    private CierreSemanalService $cierre;

    private EvaluadorRendimientoService $evaluador;

    private SubDepartment $subDepartment;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-27 10:00:00')); // lunes

        $this->capacidad = app(CapacidadService::class);
        $this->horario = app(HorarioLaboralService::class);
        $this->cierre = app(CierreSemanalService::class);
        $this->evaluador = app(EvaluadorRendimientoService::class);

        $this->department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create(['department_id' => $this->department->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function colaborador(array $extra = []): User
    {
        $user = User::factory()->create(array_merge([
            'rol' => 'tecnico',
            'dias_laborales' => ['L', 'M', 'X', 'J', 'V'],
            'horas_diarias' => 8,
            'activo' => true,
        ], $extra));
        $this->department->users()->attach($user->id, ['es_principal' => true]);

        return $user;
    }

    private function tarea(User $user, array $extra = []): Task
    {
        return Task::create(array_merge([
            'titulo' => 'Tarea',
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $user->id,
            'fecha_asignacion' => now(),
            'horas_estimadas' => 8,
        ], $extra));
    }

    public function test_carga_semanal_suma_horas_asignadas_en_la_semana(): void
    {
        $user = $this->colaborador();
        $this->tarea($user, ['horas_estimadas' => 12]);
        $this->tarea($user, ['horas_estimadas' => 8]);

        $carga = $this->capacidad->cargaSemanaActual($user);

        $this->assertEquals(40.0, $carga['disponibles']);
        $this->assertEquals(20.0, $carga['asignadas']);
    }

    public function test_tarea_completada_conserva_carga_en_la_misma_semana(): void
    {
        $user = $this->colaborador();
        $this->tarea($user, [
            'horas_estimadas' => 16,
            'estado' => 'completada',
            'fecha_completada' => now(),
            'cumplida_a_tiempo' => true,
        ]);

        $carga = $this->capacidad->cargaSemanaActual($user);
        $this->assertEquals(16.0, $carga['asignadas']);
    }

    public function test_nueva_semana_reinicia_carga_sin_arrastrar_asignaciones_previas(): void
    {
        $user = $this->colaborador();
        $this->tarea($user, [
            'horas_estimadas' => 24,
            'fecha_asignacion' => now()->subWeek(),
            'estado' => 'completada',
            'cumplida_a_tiempo' => true,
            'fecha_completada' => now()->subWeek(),
        ]);

        $carga = $this->capacidad->cargaSemanaActual($user);
        $this->assertEquals(0.0, $carga['asignadas']);
    }

    public function test_horario_laboral_reparte_horas_entre_jornadas(): void
    {
        $user = $this->colaborador();
        [$inicio, $fin] = $this->horario->calcularVentana($user, 9, Carbon::parse('2026-07-27 08:00:00'));

        $this->assertTrue($inicio->equalTo(Carbon::parse('2026-07-27 08:00:00')));
        $this->assertTrue($fin->equalTo(Carbon::parse('2026-07-28 09:00:00')));
    }

    public function test_bloquea_asignacion_si_hay_pendientes_de_semanas_anteriores(): void
    {
        $user = $this->colaborador();
        $this->tarea($user, [
            'fecha_asignacion' => now()->subWeek(),
            'estado' => 'pendiente',
            'horas_estimadas' => 4,
        ]);

        $this->assertTrue($this->cierre->tienePendientesPrevios($user));

        $resultado = $this->capacidad->validarAsignacion($user, 4);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('semanas anteriores', $resultado['mensaje']);
    }

    public function test_puntaje_queda_entre_0_y_100_y_clasifica(): void
    {
        $user = $this->colaborador();
        $this->tarea($user, [
            'estado' => 'completada',
            'cumplida_a_tiempo' => true,
            'fecha_completada' => now(),
            'horas_estimadas' => 8,
            'fecha_limite' => now()->addDay(),
        ]);

        $m = $this->evaluador->metricasColaborador($user);
        $this->assertGreaterThanOrEqual(0, $m['puntaje']);
        $this->assertLessThanOrEqual(100, $m['puntaje']);
        $this->assertSame('excelente', $m['clasificacion']['clave']);

        $this->assertSame('critico', $this->evaluador->clasificar(10)['clave']);
        $this->assertSame('medio', $this->evaluador->clasificar(50)['clave']);
        $this->assertSame('medio', $this->evaluador->clasificar(89.9)['clave']);
        $this->assertSame('excelente', $this->evaluador->clasificar(90)['clave']);
    }

    public function test_ranking_desempata_por_menos_vencidas(): void
    {
        $a = $this->colaborador(['name' => 'Ana']);
        $b = $this->colaborador(['name' => 'Bruno']);

        // Misma puntualidad/carga base, pero Bruno con una vencida abierta
        $this->tarea($a, [
            'estado' => 'completada',
            'cumplida_a_tiempo' => true,
            'fecha_completada' => now(),
            'fecha_limite' => now()->addDay(),
            'horas_estimadas' => 8,
        ]);
        $this->tarea($b, [
            'estado' => 'completada',
            'cumplida_a_tiempo' => true,
            'fecha_completada' => now(),
            'fecha_limite' => now()->addDay(),
            'horas_estimadas' => 8,
        ]);
        $this->tarea($b, [
            'estado' => 'pendiente',
            'fecha_limite' => now()->subDay(),
            'horas_estimadas' => 1,
        ]);

        $ranking = $this->evaluador->ranking([$a, $b]);
        $this->assertSame($a->id, $ranking[0]['usuario']->id);
        $this->assertSame(1, $ranking[0]['posicion']);
        $this->assertSame(2, $ranking[1]['posicion']);
    }

    public function test_evaluador_restringe_acceso_a_no_admin(): void
    {
        $tecnico = $this->colaborador(['rol' => 'tecnico']);
        $admin = $this->colaborador(['rol' => 'admin', 'name' => 'Admin']);

        Livewire::actingAs($tecnico)
            ->test(EvaluadorColaboradores::class)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(EvaluadorColaboradores::class)
            ->assertOk();
    }
}
