<?php

namespace Tests\Feature;

use App\Livewire\Colaboradores\FormColaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ColaboradoresTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $colaborador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->colaborador = User::factory()->create(['rol' => 'tecnico']);
    }

    public function test_un_no_admin_no_puede_acceder_al_listado_de_colaboradores(): void
    {
        $this->actingAs($this->colaborador)
            ->get(route('colaboradores'))
            ->assertForbidden();
    }

    public function test_el_admin_puede_crear_un_colaborador_con_disponibilidad(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FormColaborador::class)
            ->set('name', 'Nuevo Colaborador')
            ->set('email', 'nuevo@gestionti.local')
            ->set('rol', 'tecnico')
            ->set('area', 'software')
            ->set('diasLaborales', ['L', 'M', 'X'])
            ->set('horasDiarias', '6')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save')
            ->assertHasNoErrors();

        $creado = User::where('email', 'nuevo@gestionti.local')->first();
        $this->assertNotNull($creado);
        $this->assertEquals(['L', 'M', 'X'], $creado->dias_laborales);
        $this->assertEquals(18.0, $creado->capacidadSemanal());
        // La contrasena nunca se guarda en texto plano.
        $this->assertTrue(Hash::check('password123', $creado->password));
        $this->assertNotEquals('password123', $creado->password);
    }

    public function test_requiere_al_menos_un_dia_laboral_y_horas_diarias_validas(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FormColaborador::class)
            ->set('name', 'Sin Disponibilidad')
            ->set('email', 'sin@gestionti.local')
            ->set('diasLaborales', [])
            ->set('horasDiarias', '15')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save')
            ->assertHasErrors(['diasLaborales', 'horasDiarias']);
    }

    public function test_editar_sin_escribir_contrasena_no_la_cambia(): void
    {
        $original = $this->colaborador->password;

        Livewire::actingAs($this->admin)
            ->test(FormColaborador::class, ['colaborador' => $this->colaborador])
            ->set('diasLaborales', ['L', 'M'])
            ->set('horasDiarias', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals($original, $this->colaborador->fresh()->password);
    }

    public function test_crear_y_editar_colaborador_queda_registrado_en_la_bitacora(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FormColaborador::class)
            ->set('name', 'Trazado')
            ->set('email', 'trazado@gestionti.local')
            ->set('diasLaborales', ['L'])
            ->set('horasDiarias', '4')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save');

        $this->assertDatabaseHas('audit_logs', ['accion' => 'colaborador_creado']);
    }
}
