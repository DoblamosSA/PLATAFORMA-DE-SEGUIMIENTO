<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\TableroProyecto;
use App\Livewire\Tareas\FormTarea;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TableroProyectoTest extends TestCase
{
    use RefreshDatabase;

    private User $lider;

    private User $dev;

    private User $ajeno;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lider = User::factory()->create(['rol' => 'lider']);
        $this->dev = User::factory()->create(['rol' => 'tecnico']);
        $this->ajeno = User::factory()->create(['rol' => 'tecnico']);

        $this->project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'alta',
            'responsable_id' => $this->lider->id,
        ]);

        $this->project->equipo()->attach($this->dev->id, ['rol_en_proyecto' => 'desarrollador']);
    }

    private function tareaEn(string $estado): Task
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea '.$estado,
            'tipo' => 'software',
            'prioridad' => 'alta',
            'estado' => $estado,
            'asignado_id' => $this->dev->id,
            'fecha_asignacion' => now(),
        ]);
        $task->aplicarSla();
        $task->save();

        return $task;
    }

    private function columna(string $nombre): BoardColumn
    {
        return $this->project->columnas()->where('nombre', $nombre)->firstOrFail();
    }

    public function test_el_tablero_se_crea_con_las_cuatro_columnas_por_defecto(): void
    {
        Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $this->assertSame(
            ['Pendiente', 'En ejecucion', 'Terminada', 'Certificada'],
            $this->project->columnas()->pluck('nombre')->all()
        );
    }

    public function test_mover_una_tarea_actualiza_estado_orden_y_trazabilidad(): void
    {
        $this->tareaEn('pendiente'); // asegura que asegurarColumnas ubique tareas
        $task = $this->tareaEn('pendiente');
        Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $enEjecucion = $this->columna('En ejecucion');

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('moverTarea', $task->id, $enEjecucion->id, [$task->id]);

        $task->refresh();

        $this->assertSame('en_progreso', $task->estado);
        $this->assertSame($enEjecucion->id, $task->board_column_id);
        $this->assertSame(0, $task->posicion);
        $this->assertNotNull($task->fecha_inicio_real);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'accion' => 'cambio_estado',
        ]);
    }

    public function test_mover_a_columna_completada_evalua_el_sla(): void
    {
        $task = $this->tareaEn('pendiente');
        Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $terminada = $this->columna('Terminada');

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('moverTarea', $task->id, $terminada->id, [$task->id]);

        $task->refresh();

        $this->assertSame('completada', $task->estado);
        $this->assertNotNull($task->fecha_completada);
        $this->assertTrue($task->cumplida_a_tiempo); // el SLA cae en el futuro
    }

    public function test_persiste_el_orden_de_varias_cards(): void
    {
        $a = $this->tareaEn('pendiente');
        $b = $this->tareaEn('pendiente');
        Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $pendiente = $this->columna('Pendiente');

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('moverTarea', $b->id, $pendiente->id, [$b->id, $a->id]);

        $this->assertSame(0, $b->refresh()->posicion);
        $this->assertSame(1, $a->refresh()->posicion);
    }

    public function test_no_se_puede_eliminar_una_columna_con_tareas(): void
    {
        $this->tareaEn('pendiente');
        $componente = Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $pendiente = $this->columna('Pendiente');

        $componente->call('eliminarColumna', $pendiente->id)
            ->assertHasErrors('columna');

        // La columna sigue existiendo (no se elimina si tiene tareas)
        $this->assertDatabaseHas('board_columns', ['id' => $pendiente->id]);
    }

    public function test_se_puede_eliminar_una_columna_vacia(): void
    {
        $componente = Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $certificada = $this->columna('Certificada'); // vacia

        $componente->call('eliminarColumna', $certificada->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('board_columns', ['id' => $certificada->id]);
    }

    public function test_reordenar_columnas_persiste_la_posicion(): void
    {
        $componente = Livewire::actingAs($this->lider)->test(TableroProyecto::class, ['project' => $this->project]);

        $ids = $this->project->columnas()->pluck('id')->reverse()->values()->all();

        $componente->call('reordenarColumnas', $ids);

        $this->assertSame($ids, $this->project->columnas()->pluck('id')->all());
    }

    public function test_publicar_comentario_lo_guarda_como_actividad(): void
    {
        $task = $this->tareaEn('pendiente');

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('nuevoComentario', 'Necesito acceso al repositorio')
            ->call('comentar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'user_id' => $this->dev->id,
            'accion' => 'comentario',
            'detalle' => 'Necesito acceso al repositorio',
        ]);
    }

    public function test_editar_una_tarea_no_borra_la_trazabilidad_historica(): void
    {
        $task = $this->tareaEn('pendiente');

        // Comentario previo
        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('nuevoComentario', 'Comentario historico')
            ->call('comentar');

        $antes = $task->actividades()->count();

        // Editar la tarea (cambia prioridad) mediante el formulario existente
        Livewire::actingAs($this->lider)
            ->test(FormTarea::class, ['task' => $task])
            ->set('prioridad', 'critica')
            ->call('save');

        // El comentario historico sigue existiendo y se sumaron cambios
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'accion' => 'comentario',
            'detalle' => 'Comentario historico',
        ]);
        $this->assertGreaterThan($antes, $task->actividades()->count());
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'accion' => 'cambio_prioridad',
        ]);
    }

    public function test_una_tarea_creada_desde_el_formulario_aparece_en_el_tablero(): void
    {
        $this->project->asegurarColumnas();

        Livewire::actingAs($this->lider)
            ->test(FormTarea::class)
            ->set('project_id', $this->project->id)
            ->set('titulo', 'Tarea desde formulario')
            ->set('tipo', 'software')
            ->set('prioridad', 'media')
            ->set('estado', 'pendiente')
            ->set('asignado_id', $this->dev->id)
            ->call('save');

        $task = Task::where('titulo', 'Tarea desde formulario')->firstOrFail();

        $this->assertSame($this->columna('Pendiente')->id, $task->board_column_id);
    }

    public function test_editar_la_tarea_en_linea_actualiza_y_mueve_de_columna(): void
    {
        $task = $this->tareaEn('pendiente');

        Livewire::actingAs($this->lider)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->assertSet('editando', true)
            ->assertSet('edTitulo', $task->titulo)
            ->set('edPrioridad', 'critica')
            ->set('edEstado', 'en_progreso')
            ->call('guardarEdicion')
            ->assertHasNoErrors()
            ->assertSet('editando', false)
            ->assertNoRedirect();

        $task->refresh();

        $this->assertSame('critica', $task->prioridad);
        $this->assertSame('en_progreso', $task->estado);
        $this->assertSame($this->columna('En ejecucion')->id, $task->board_column_id);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'accion' => 'cambio_prioridad']);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'accion' => 'cambio_estado']);
    }

    public function test_solo_el_admin_ve_el_campo_de_fecha_limite_en_la_edicion_en_linea(): void
    {
        $task = $this->tareaEn('pendiente');
        $admin = User::factory()->create(['rol' => 'admin']);

        Livewire::actingAs($this->lider) // lider no es admin
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->assertDontSee('Modificar fecha límite');

        Livewire::actingAs($admin)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->assertSee('Modificar fecha límite');
    }

    public function test_un_no_admin_no_puede_cambiar_la_fecha_limite_desde_el_tablero(): void
    {
        $task = $this->tareaEn('pendiente');
        $original = $task->fecha_limite->format('Y-m-d\TH:i');

        Livewire::actingAs($this->lider)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->set('edFechaLimiteInput', now()->addMonth()->format('Y-m-d\TH:i'))
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $this->assertSame($original, $task->fresh()->fecha_limite->format('Y-m-d\TH:i'));
    }

    public function test_el_admin_debe_dejar_observacion_al_cambiar_la_fecha_limite_desde_el_tablero(): void
    {
        $task = $this->tareaEn('pendiente');
        $admin = User::factory()->create(['rol' => 'admin']);
        $original = $task->fecha_limite->format('Y-m-d\TH:i');

        Livewire::actingAs($admin)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->set('edFechaLimiteInput', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->call('guardarEdicion')
            ->assertHasErrors(['edObservacionFecha' => 'required']);

        $this->assertSame($original, $task->fresh()->fecha_limite->format('Y-m-d\TH:i'));
    }

    public function test_el_admin_puede_cambiar_la_fecha_limite_desde_el_tablero_dejando_observacion(): void
    {
        $task = $this->tareaEn('pendiente');
        $admin = User::factory()->create(['rol' => 'admin']);
        $nueva = now()->addDays(3)->startOfMinute();

        Livewire::actingAs($admin)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->set('edFechaLimiteInput', $nueva->format('Y-m-d\TH:i'))
            ->set('edObservacionFecha', 'Ajuste acordado con el area solicitante.')
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertTrue($nueva->equalTo($task->fecha_limite));

        $actividad = $task->actividades()->where('accion', 'cambio_fecha_limite')->first();
        $this->assertStringContainsString('Motivo: Ajuste acordado con el area solicitante.', $actividad->detalle);
    }

    public function test_un_usuario_no_autorizado_no_puede_ver_el_tablero(): void
    {
        $this->actingAs($this->ajeno)
            ->get(route('proyectos.tablero', $this->project))
            ->assertForbidden();
    }

    public function test_el_responsable_y_los_miembros_pueden_ver_el_tablero(): void
    {
        $this->actingAs($this->lider)->get(route('proyectos.tablero', $this->project))->assertOk();
        $this->actingAs($this->dev)->get(route('proyectos.tablero', $this->project))->assertOk();
    }

    public function test_un_admin_siempre_puede_gestionar(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->get(route('proyectos.tablero', $this->project))->assertOk();
    }
}
