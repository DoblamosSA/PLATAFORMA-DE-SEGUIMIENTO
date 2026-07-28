<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
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

    private SubDepartment $subDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);

        $this->lider = User::factory()->create(['rol' => 'lider']);
        $this->dev = User::factory()->create(['rol' => 'tecnico']);
        $this->ajeno = User::factory()->create(['rol' => 'tecnico']);

        $department->users()->attach($this->lider->id, ['es_principal' => true]);
        $department->users()->attach($this->dev->id, ['es_principal' => true]);

        $otroDepto = Department::factory()->create();
        $otroDepto->users()->attach($this->ajeno->id, ['es_principal' => true]);

        $this->project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'sub_department_id' => $this->subDepartment->id,
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
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'alta',
            'estado' => $estado,
            'asignado_id' => $this->dev->id,
            'fecha_asignacion' => now(),
            'fecha_limite' => now()->addDays(3),
        ]);

        return $task;
    }

    private function columna(string $nombre): BoardColumn
    {
        return $this->project->columnas()->where('nombre', $nombre)->firstOrFail();
    }

    /** Asigna un permiso granular al rol de departamento del usuario. */
    private function otorgarPermiso(User $user, string $slug): void
    {
        $permiso = Permission::factory()->create(['slug' => $slug]);
        $rol = Role::factory()->create(['is_primary' => true]);
        $rol->permissions()->attach($permiso->id, ['tipo' => 'grant']);
        $user->departments()->updateExistingPivot(
            $user->departments()->first()->id,
            ['role_id' => $rol->id]
        );
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

    public function test_publicar_comentario_dispara_web_push(): void
    {
        $task = $this->tareaEn('pendiente');

        $push = \Mockery::mock(\App\Services\WebPushService::class);
        $push->shouldReceive('notificarATodos')
            ->once()
            ->withArgs(function (?int $excepto, string $titulo, string $cuerpo, string $url) use ($task) {
                return $excepto === $this->dev->id
                    && $titulo === 'Nuevo comentario'
                    && str_contains($cuerpo, 'Necesito acceso')
                    && str_contains($url, "tarea={$task->id}");
            });
        $this->app->instance(\App\Services\WebPushService::class, $push);

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('nuevoComentario', 'Necesito acceso al repositorio')
            ->call('comentar')
            ->assertHasNoErrors();
    }

    public function test_comentario_puede_llevar_evidencias_opcionales(): void
    {
        $task = $this->tareaEn('pendiente');
        $imagen = \Illuminate\Http\UploadedFile::fake()->image('captura.jpg', 800, 600);

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('nuevoComentario', 'Adjunto captura')
            ->set('evidenciasComentario', [$imagen])
            ->call('comentar')
            ->assertHasNoErrors();

        $actividad = $task->actividades()->where('accion', 'comentario')->latest('id')->first();
        $this->assertNotNull($actividad);
        $this->assertDatabaseHas('task_evidences', [
            'task_id' => $task->id,
            'task_activity_id' => $actividad->id,
            'user_id' => $this->dev->id,
        ]);
        $this->assertSame(1, $actividad->evidencias()->count());
    }

    public function test_comentario_sin_imagen_sigue_funcionando(): void
    {
        $task = $this->tareaEn('pendiente');

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('nuevoComentario', 'Solo texto')
            ->call('comentar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'accion' => 'comentario',
            'detalle' => 'Solo texto',
        ]);
        $this->assertDatabaseCount('task_evidences', 0);
    }

    public function test_evidencia_de_descripcion_se_guarda_sin_comentario(): void
    {
        $this->otorgarPermiso($this->dev, 'tasks.edit');
        $task = $this->tareaEn('pendiente');
        $imagen = \Illuminate\Http\UploadedFile::fake()->image('desc.png', 400, 300);

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->set('evidenciasDescripcion', [$imagen])
            ->call('guardarEvidenciasDescripcion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_evidences', [
            'task_id' => $task->id,
            'task_activity_id' => null,
            'user_id' => $this->dev->id,
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

        $this->otorgarPermiso($this->lider, 'tasks.edit');

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
        $this->otorgarPermiso($this->lider, 'tasks.create');

        Livewire::actingAs($this->lider)
            ->test(FormTarea::class)
            ->set('project_id', $this->project->id)
            ->set('titulo', 'Tarea desde formulario')
            ->set('sub_department_id', (string) $this->subDepartment->id)
            ->set('prioridad', 'media')
            ->set('estado', 'pendiente')
            ->set('asignado_id', $this->dev->id)
            ->call('save');

        $task = Task::where('titulo', 'Tarea desde formulario')->firstOrFail();

        $this->assertSame($this->columna('Pendiente')->id, $task->board_column_id);
        $this->assertSame($this->dev->id, $task->asignado_id);
    }

    public function test_reasignar_una_tarea_completada_desde_el_tablero_persiste_el_asignado(): void
    {
        $this->project->asegurarColumnas();
        $task = $this->tareaEn('completada');
        $task->fecha_completada = now();
        $task->cumplida_a_tiempo = true;
        $task->board_column_id = $this->columna('Terminada')->id;
        $task->save();

        $otro = User::factory()->create(['rol' => 'tecnico']);
        $this->lider->departments()->first()->users()->attach($otro->id, ['es_principal' => true]);
        $this->project->equipo()->attach($otro->id, ['rol_en_proyecto' => 'desarrollador']);

        Livewire::actingAs($this->lider)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $task->id)
            ->call('iniciarEdicion')
            ->set('edAsignadoId', $otro->id)
            ->set('edEstado', 'completada')
            ->call('guardarEdicion')
            ->assertHasNoErrors()
            ->assertSet('editando', false);

        $this->assertSame($otro->id, $task->fresh()->asignado_id);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'accion' => 'reasignacion',
        ]);
    }

    public function test_asignar_tarea_desde_el_modal_del_tablero_persiste_colaborador(): void
    {
        $this->project->asegurarColumnas();
        $this->otorgarPermiso($this->lider, 'tasks.create');

        Livewire::actingAs($this->lider)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirCrearTarea')
            ->assertSet('mostrarModalTarea', true);

        Livewire::actingAs($this->lider)
            ->test(FormTarea::class, [
                'projectId' => $this->project->id,
                'enModal' => true,
                'padreLivewire' => 'proyectos.tablero-proyecto',
            ])
            ->assertSet('project_id', $this->project->id)
            ->set('titulo', 'Tarea asignada desde tablero')
            ->set('sub_department_id', (string) $this->subDepartment->id)
            ->set('asignado_id', $this->dev->id)
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::where('titulo', 'Tarea asignada desde tablero')->firstOrFail();

        $this->assertSame($this->project->id, $task->project_id);
        $this->assertSame($this->dev->id, $task->asignado_id);
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
            ->set('edFechaLimiteInput', now()->addDays(10)->format('Y-m-d\TH:i'))
            ->call('guardarEdicion')
            ->assertHasErrors(['edObservacionFecha' => 'required']);

        $this->assertSame($original, $task->fresh()->fecha_limite->format('Y-m-d\TH:i'));
    }

    public function test_el_admin_puede_cambiar_la_fecha_limite_desde_el_tablero_dejando_observacion(): void
    {
        $task = $this->tareaEn('pendiente');
        $admin = User::factory()->create(['rol' => 'admin']);
        $nueva = now()->addDays(10)->startOfMinute();

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
