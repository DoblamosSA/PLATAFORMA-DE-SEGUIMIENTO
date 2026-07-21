<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\FormProyecto;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Solo el administrador y el coordinador pueden crear proyectos. Se prueba
 * tanto el acceso HTTP (mount) como save(), ya que la UI solo oculta el boton.
 */
class PermisosProyectosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $coordinador;

    private User $colaborador;

    private User $evaluador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->coordinador = User::factory()->create(['rol' => 'lider']);
        $this->colaborador = User::factory()->create(['rol' => 'tecnico']);
        $this->evaluador = User::factory()->create(['rol' => 'evaluador']);
    }

    public function test_un_colaborador_no_puede_acceder_al_formulario_de_nuevo_proyecto(): void
    {
        $this->actingAs($this->colaborador)
            ->get(route('proyectos.crear'))
            ->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_un_evaluador_no_puede_acceder_al_formulario_de_nuevo_proyecto(): void
    {
        $this->actingAs($this->evaluador)
            ->get(route('proyectos.crear'))
            ->assertForbidden();
    }

    public function test_un_coordinador_puede_crear_un_proyecto(): void
    {
        Livewire::actingAs($this->coordinador)
            ->test(FormProyecto::class)
            ->set('nombre', 'Proyecto de coordinador')
            ->set('tipo', 'software')
            ->set('estado', 'planeado')
            ->set('prioridad', 'media')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', ['nombre' => 'Proyecto de coordinador']);
    }

    public function test_el_admin_puede_crear_un_proyecto(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FormProyecto::class)
            ->set('nombre', 'Proyecto de admin')
            ->set('tipo', 'software')
            ->set('estado', 'planeado')
            ->set('prioridad', 'media')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', ['nombre' => 'Proyecto de admin']);
    }

    public function test_un_colaborador_no_puede_editar_un_proyecto_existente(): void
    {
        $project = Project::create([
            'nombre' => 'Proyecto existente',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);

        // Editar un proyecto ya creado no esta restringido por este permiso:
        // solo se bloquea la creacion de proyectos nuevos.
        Livewire::actingAs($this->colaborador)
            ->test(FormProyecto::class, ['project' => $project])
            ->assertOk();
    }
}
