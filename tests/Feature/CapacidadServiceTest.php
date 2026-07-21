<?php

namespace Tests\Feature;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(CapacidadService::class);
    }

    public function test_capacidad_periodo_cuenta_solo_dias_laborales(): void
    {
        // Lunes a viernes, 8h/dia.
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        // Un lunes a un domingo (semana completa): 5 dias laborales x 8h = 40h.
        $lunes = Carbon::parse('next monday');
        $domingo = $lunes->copy()->addDays(6);

        $this->assertEquals(40.0, $this->servicio->capacidadPeriodo($user, $lunes, $domingo));
    }

    public function test_sin_disponibilidad_configurada_la_capacidad_es_cero(): void
    {
        $user = User::factory()->create(['dias_laborales' => null, 'horas_diarias' => null]);

        $this->assertEquals(0.0, $this->servicio->capacidadPeriodo($user, now(), now()->addDays(5)));
    }

    public function test_valida_asignacion_permite_dentro_de_capacidad(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $desde = Carbon::parse('next monday');
        $hasta = $desde->copy()->addDays(4); // 5 dias laborales = 40h disponibles

        $resultado = $this->servicio->validarAsignacion($user, 20, $desde, $hasta);

        $this->assertTrue($resultado['ok']);
        $this->assertNull($resultado['mensaje']);
    }

    public function test_valida_asignacion_bloquea_si_supera_capacidad(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L'], 'horas_diarias' => 4]);

        $lunes = Carbon::parse('next monday');

        // Una sola tarea existente que ya consume las 4h disponibles del lunes.
        Task::create([
            'titulo' => 'Tarea existente',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $user->id,
            'fecha_asignacion' => now(),
            'fecha_limite' => $lunes->copy()->setTime(17, 0),
            'horas_estimadas' => 4,
        ]);

        $resultado = $this->servicio->validarAsignacion($user, 2, $lunes, $lunes);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString($user->name, $resultado['mensaje']);
        $this->assertStringContainsString('capacidad', mb_strtolower($resultado['mensaje']));
    }

    public function test_distribucion_diaria_reparte_horas_entre_dias_laborables_del_rango(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L', 'M', 'X', 'J', 'V'], 'horas_diarias' => 8]);

        $lunes = Carbon::parse('next monday');
        $task = Task::create([
            'titulo' => 'Tarea repartida',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $user->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => $lunes,
            'fecha_limite' => $lunes->copy()->addDays(1)->setTime(17, 0), // lunes y martes: 2 dias laborales
            'horas_estimadas' => 10,
        ]);

        $distribucion = $this->servicio->distribucionDiaria($task, $user);

        $this->assertCount(2, $distribucion);
        $this->assertEquals(5.0, array_sum($distribucion) / count($distribucion));
        $this->assertEquals(10.0, array_sum($distribucion));
    }

    public function test_sin_fecha_limite_no_se_puede_validar_y_se_permite(): void
    {
        $user = User::factory()->create(['dias_laborales' => ['L'], 'horas_diarias' => 1]);

        $resultado = $this->servicio->validarAsignacion($user, 100, null, null);

        $this->assertTrue($resultado['ok']);
    }
}
