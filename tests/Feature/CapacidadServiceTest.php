<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Services\CapacidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CapacidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private CapacidadService $servicio;

    private SubDepartment $subDepartment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(CapacidadService::class);
        Carbon::setTestNow(Carbon::parse('2026-07-27 10:00:00')); // lunes

        $department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearTarea(User $user, array $extra = []): Task
    {
        return Task::create(array_merge([
            'titulo' => 'Tarea',
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $user->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => now(),
            'fecha_limite' => now()->addDays(3),
            'horas_estimadas' => 8,
        ], $extra));
    }

    public function test_capacidad_periodo_cuenta_solo_dias_laborales(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $lunes = Carbon::parse('2026-07-27');
        $domingo = $lunes->copy()->addDays(6);

        $this->assertEquals(40.0, $this->servicio->capacidadPeriodo($user, $lunes, $domingo));
    }

    public function test_sin_disponibilidad_configurada_la_capacidad_es_cero(): void
    {
        $user = User::factory()->create(['dias_laborales' => null, 'horas_diarias' => null]);

        $this->assertEquals(0.0, $this->servicio->capacidadPeriodo($user, now(), now()->addDays(5)));
    }

    public function test_valida_asignacion_permite_dentro_de_capacidad_semanal(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $resultado = $this->servicio->validarAsignacion($user, 20);

        $this->assertTrue($resultado['ok']);
        $this->assertNull($resultado['mensaje']);
        $this->assertEquals(40.0, $resultado['disponibles']);
    }

    public function test_dos_tareas_de_12h_dejan_16h_libres(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $this->crearTarea($user, [
            'titulo' => 'Tarea A',
            'fecha_asignacion' => now()->subHour(),
            'horas_estimadas' => 12,
        ]);
        $this->crearTarea($user, [
            'titulo' => 'Tarea B',
            'fecha_asignacion' => now(),
            'horas_estimadas' => 12,
        ]);

        $carga = $this->servicio->cargaSemanaActual($user);
        $this->assertEquals(40.0, $carga['disponibles']);
        $this->assertEquals(24.0, $carga['asignadas']);

        $resultado = $this->servicio->validarAsignacion($user, 16);
        $this->assertTrue($resultado['ok']);
        $this->assertEquals(16.0, $resultado['restante']);
    }

    public function test_carga_semanal_cuenta_todas_las_horas_de_tareas_abiertas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 10:00:00')); // martes

        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $this->crearTarea($user, ['titulo' => 'A', 'fecha_asignacion' => now()->subHours(3), 'horas_estimadas' => 12]);
        $this->crearTarea($user, ['titulo' => 'B', 'fecha_asignacion' => now()->subHours(2), 'horas_estimadas' => 12]);
        $this->crearTarea($user, ['titulo' => 'C', 'fecha_asignacion' => now()->subHour(), 'horas_estimadas' => 16]);

        $carga = $this->servicio->cargaSemanaActual($user);

        $this->assertEquals(40.0, $carga['disponibles']);
        $this->assertEquals(40.0, $carga['asignadas']);
        $this->assertEquals(100.0, $carga['porcentaje']);

        // Sumar 8 h mas debe bloquearse pidiendo solo el incremento.
        $resultado = $this->servicio->validarAsignacion($user, 8);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Superas la capacidad de trabajo', $resultado['mensaje']);
        // La tarea referenciada es la mas antigua (FIFO) entre las abiertas de la semana.
        $this->assertNotNull($resultado['tarea_bloqueante']);
        $this->assertEquals('A', $resultado['tarea_bloqueante']->titulo);
        $this->assertStringContainsString('A', $resultado['mensaje']);
    }

    public function test_cupo_semanal_permite_aunque_parte_del_libre_este_en_dias_pasados(): void
    {
        // Martes: el lunes ya paso. 24 h asignadas dejan 16 h libres en la
        // semana; 8 h de ese libre pueden caer en el lunes (ya no colocable
        // desde "hoy"). Aun asi, una tarea de 12 h debe aceptarse.
        Carbon::setTestNow(Carbon::parse('2026-07-28 10:00:00')); // martes

        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        // Ocupa mar–jue (24 h). Lunes y viernes quedan libres (= 16 h).
        $this->crearTarea($user, [
            'titulo' => 'Carga mar-jue',
            'fecha_asignacion' => now()->subHour(),
            'fecha_inicio' => Carbon::parse('2026-07-28'), // martes
            'horas_estimadas' => 24,
        ]);

        $resultado = $this->servicio->validarAsignacion($user, 12);

        $this->assertTrue($resultado['ok'], $resultado['mensaje'] ?? 'sin mensaje');
        $this->assertEquals(16.0, $resultado['restante']);
        $this->assertEquals(12.0, array_sum($resultado['plan']));
    }

    public function test_reparte_horas_nuevas_en_huecos_diarios(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $this->crearTarea($user, [
            'titulo' => 'Casi llena lunes',
            'fecha_asignacion' => now()->subHour(),
            'fecha_inicio' => Carbon::parse('2026-07-27'),
            'horas_estimadas' => 7,
        ]);

        $resultado = $this->servicio->validarAsignacion($user, 6, Carbon::parse('2026-07-27'));

        $this->assertTrue($resultado['ok']);
        $this->assertEquals(1.0, $resultado['plan']['2026-07-27'] ?? null);
        $this->assertEquals(5.0, $resultado['plan']['2026-07-28'] ?? null);
    }

    public function test_valida_asignacion_bloquea_si_supera_semana(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $this->crearTarea($user, [
            'titulo' => 'Carga casi full',
            'horas_estimadas' => 38,
        ]);

        $resultado = $this->servicio->validarAsignacion($user, 4);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('capacidad', mb_strtolower($resultado['mensaje']));
        $this->assertNotNull($resultado['tarea_bloqueante']);
        $this->assertEquals('Carga casi full', $resultado['tarea_bloqueante']->titulo);
        $this->assertStringContainsString('Carga casi full', $resultado['mensaje']);
    }

    public function test_mensaje_de_bloqueo_usa_el_nombre_del_usuario_si_no_hay_tarea_abierta_para_referenciar(): void
    {
        // Capacidad excedida sin ninguna tarea previa: el fallback debe
        // mencionar al colaborador en vez de dejar el mensaje sin contexto.
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $resultado = $this->servicio->validarAsignacion($user, 41);

        $this->assertFalse($resultado['ok']);
        $this->assertNull($resultado['tarea_bloqueante']);
        $this->assertStringContainsString($user->name, $resultado['mensaje']);
    }

    public function test_distribucion_diaria_llena_dias_secuencialmente(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $task = $this->crearTarea($user, [
            'titulo' => 'Tarea repartida',
            'fecha_inicio' => Carbon::parse('2026-07-27'),
            'horas_estimadas' => 10,
        ]);

        $distribucion = $this->servicio->distribucionDiaria($task, $user);

        $this->assertEquals(8.0, $distribucion['2026-07-27'] ?? null);
        $this->assertEquals(2.0, $distribucion['2026-07-28'] ?? null);
        $this->assertEquals(10.0, array_sum($distribucion));
    }

    public function test_sin_horas_solicitadas_se_permite(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L'], 'horas_diarias' => 1]);

        $resultado = $this->servicio->validarAsignacion($user, 0);

        $this->assertTrue($resultado['ok']);
    }
}
