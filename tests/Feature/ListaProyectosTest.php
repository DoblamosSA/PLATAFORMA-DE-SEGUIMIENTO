<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\ListaProyectos;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListaProyectosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_proyectos_carga_sin_error_para_el_admin(): void
    {
        // Regresion: Project::scopeVisiblesPara() tenia un type-hint Builder
        // sin importar (resolvia a App\Models\Builder, inexistente), lo que
        // provocaba un TypeError 500 en cada visita a /proyectos.
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('proyectos'))
            ->assertOk();
    }

    public function test_el_admin_ve_todos_los_proyectos(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $otro = User::factory()->create(['rol' => 'lider']);

        $propio = Project::create(['nombre' => 'Propio', 'tipo' => 'software', 'estado' => 'planeado', 'prioridad' => 'media', 'responsable_id' => $admin->id]);
        $ajeno = Project::create(['nombre' => 'Ajeno', 'tipo' => 'software', 'estado' => 'planeado', 'prioridad' => 'media', 'responsable_id' => $otro->id]);

        Livewire::actingAs($admin)
            ->test(ListaProyectos::class)
            ->assertSee('Propio')
            ->assertSee('Ajeno');
    }

    public function test_un_no_admin_solo_ve_sus_proyectos(): void
    {
        $lider = User::factory()->create(['rol' => 'lider']);
        $otroLider = User::factory()->create(['rol' => 'lider']);
        $miembro = User::factory()->create(['rol' => 'tecnico']);

        $comoResponsable = Project::create(['nombre' => 'Como responsable', 'tipo' => 'software', 'estado' => 'planeado', 'prioridad' => 'media', 'responsable_id' => $lider->id]);
        $comoEquipo = Project::create(['nombre' => 'Como equipo', 'tipo' => 'software', 'estado' => 'planeado', 'prioridad' => 'media', 'responsable_id' => $otroLider->id]);
        $comoEquipo->equipo()->attach($lider->id, ['rol_en_proyecto' => 'desarrollador']);
        $ajeno = Project::create(['nombre' => 'Totalmente ajeno', 'tipo' => 'software', 'estado' => 'planeado', 'prioridad' => 'media', 'responsable_id' => $otroLider->id]);

        Livewire::actingAs($lider)
            ->test(ListaProyectos::class)
            ->assertSee('Como responsable')
            ->assertSee('Como equipo')
            ->assertDontSee('Totalmente ajeno');

        Livewire::actingAs($miembro)
            ->test(ListaProyectos::class)
            ->assertDontSee('Como responsable')
            ->assertDontSee('Como equipo')
            ->assertDontSee('Totalmente ajeno');
    }
}
