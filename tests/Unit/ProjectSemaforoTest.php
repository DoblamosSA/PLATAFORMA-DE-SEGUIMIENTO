<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSemaforoTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ], $overrides));
    }

    private function tarea(Project $p, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'project_id' => $p->id,
            'titulo' => 'Tarea',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'en_progreso',
        ], $overrides));
    }

    public function test_planeado_sin_tareas_es_azul(): void
    {
        $p = $this->proyecto(['estado' => 'planeado']);

        $this->assertSame('planeado', $p->semaforo());
    }

    public function test_sin_tareas_es_azul_sin_importar_el_estado_del_proyecto(): void
    {
        $p = $this->proyecto(['estado' => 'en_pausa']);

        $this->assertSame('planeado', $p->semaforo());
    }

    public function test_todas_las_tareas_pendientes_sin_iniciar_es_azul_aunque_esten_vencidas(): void
    {
        // El proyecto ya figura "en_progreso" pero ninguna tarea arranco:
        // una pendiente vencida no debe contar como riesgo todavia.
        $p = $this->proyecto(['estado' => 'en_progreso']);
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subDays(3)]);
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subDay()]);

        $this->assertSame('planeado', $p->semaforo());
    }

    public function test_en_cuanto_una_tarea_arranca_las_pendientes_vencidas_si_cuentan(): void
    {
        $p = $this->proyecto(['estado' => 'en_progreso']);
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subDay()]); // vencida, sin iniciar
        $this->tarea($p, ['estado' => 'en_progreso', 'fecha_limite' => now()->addDays(10)]); // ya arranco

        $this->assertSame('vencido', $p->semaforo());
    }

    public function test_cancelado_nunca_tiene_color_aunque_tenga_tareas_vencidas(): void
    {
        $p = $this->proyecto(['estado' => 'cancelado']);
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subDay()]);

        $this->assertNull($p->semaforo());
    }

    public function test_al_menos_una_tarea_vencida_pone_el_proyecto_en_rojo(): void
    {
        $p = $this->proyecto();
        $this->tarea($p, ['estado' => 'completada', 'cumplida_a_tiempo' => true]);
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subHour()]);

        $this->assertSame('vencido', $p->semaforo());
    }

    public function test_una_tarea_a_menos_de_dos_dias_de_vencer_pone_el_proyecto_en_amarillo(): void
    {
        $p = $this->proyecto();
        $this->tarea($p, ['estado' => 'en_progreso', 'fecha_limite' => now()->addHours(20)]);

        $this->assertSame('en_riesgo', $p->semaforo());
    }

    public function test_una_tarea_lejos_de_vencer_no_activa_el_riesgo(): void
    {
        $p = $this->proyecto();
        $this->tarea($p, ['estado' => 'en_progreso', 'fecha_limite' => now()->addDays(10)]);

        $this->assertSame('saludable', $p->semaforo());
    }

    public function test_todas_las_tareas_ok_pone_el_proyecto_en_verde(): void
    {
        $p = $this->proyecto();
        $this->tarea($p, ['estado' => 'completada', 'cumplida_a_tiempo' => true]);
        $this->tarea($p, ['estado' => 'en_progreso', 'fecha_limite' => now()->addDays(10)]);

        $this->assertSame('saludable', $p->semaforo());
    }

    public function test_vencida_tiene_prioridad_sobre_en_riesgo(): void
    {
        $p = $this->proyecto();
        $this->tarea($p, ['estado' => 'pendiente', 'fecha_limite' => now()->subHour()]);
        $this->tarea($p, ['estado' => 'en_progreso', 'fecha_limite' => now()->addHour()]);

        $this->assertSame('vencido', $p->semaforo());
    }

    public function test_proyecto_completado_sin_tareas_abiertas_es_verde(): void
    {
        $p = $this->proyecto(['estado' => 'completado']);
        $this->tarea($p, ['estado' => 'completada', 'cumplida_a_tiempo' => true]);

        $this->assertSame('saludable', $p->semaforo());
    }

    public function test_usa_los_conteos_precargados_si_estan_disponibles(): void
    {
        $p = $this->proyecto();
        // Sin llamar a tareas(), simulamos los atributos que withCount agregaria.
        $p->setAttribute('tareas_ejecutadas_count', 1);
        $p->setAttribute('tareas_vencidas_count', 1);
        $p->setAttribute('tareas_en_riesgo_count', 0);

        $this->assertSame('vencido', $p->semaforo());
    }

    public function test_usa_el_conteo_precargado_de_ejecutadas_para_detectar_planeado(): void
    {
        $p = $this->proyecto(['estado' => 'en_progreso']);
        $p->setAttribute('tareas_ejecutadas_count', 0);
        // Aunque estos conteos digan que hay vencidas, sin ejecucion es azul.
        $p->setAttribute('tareas_vencidas_count', 4);
        $p->setAttribute('tareas_en_riesgo_count', 0);

        $this->assertSame('planeado', $p->semaforo());
    }
}
