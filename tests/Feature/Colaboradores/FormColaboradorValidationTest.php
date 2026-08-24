<?php

namespace Tests\Feature\Colaboradores;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
use App\Livewire\Colaboradores\FormColaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reproduce el bug reportado: al fallar la validacion del formulario de
 * "Nuevo colaborador", el formulario se vacia por completo y a veces el
 * colaborador no se crea. Estos tests confirman, a nivel de componente
 * Livewire (sin navegador), si las propiedades PHP sobreviven a una
 * validacion fallida -por diseno de Livewire deberian, ya que el componente
 * no llama reset()/resetValidation() en ningun punto- y que la creacion
 * exitosa con los campos Alpine (departamento, subdepartamento, rol, dias,
 * horas) funciona de punta a punta.
 */
class FormColaboradorValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private SubDepartment $subDepartment;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->role = Role::factory()->create(['is_primary' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(): array
    {
        return [
            'name' => 'Edison Ortiz',
            'email' => 'edison.ortiz@example.test',
            'telefono' => '3125668989',
            'cargo' => 'Desarrollador',
            'activo' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'department_id' => (string) $this->department->id,
            'sub_department_id' => (string) $this->subDepartment->id,
            'role_id' => (string) $this->role->id,
            'diasLaborales' => ['L', 'M', 'X', 'J', 'V'],
            'horasDiarias' => '8.5',
        ];
    }

    public function test_una_validacion_fallida_no_vacia_las_propiedades_del_formulario(): void
    {
        // Motivo de fallo unico y deliberado: correo ya existente.
        User::factory()->create(['email' => 'edison.ortiz@example.test']);

        $usuariosAntes = User::count();

        $component = Livewire::actingAs($this->admin)
            ->test(FormColaborador::class, ['enModal' => true])
            ->set($this->datosValidos())
            ->call('save');

        $component->assertHasErrors(['email']);

        $this->assertSame($usuariosAntes, User::count(), 'No debe crearse ningun colaborador cuando la validacion falla.');

        foreach ($this->datosValidos() as $propiedad => $valorEsperado) {
            $component->assertSet(
                $propiedad,
                $valorEsperado,
                "La propiedad [{$propiedad}] no deberia vaciarse tras una validacion fallida (Livewire no llama reset() en este componente)."
            );
        }
    }

    public function test_creacion_exitosa_con_departamento_subdepartamento_rol_dias_y_horas(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(FormColaborador::class, ['enModal' => true])
            ->set($this->datosValidos())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('cerrar-modal-colaborador');

        $colaborador = User::where('email', 'edison.ortiz@example.test')->firstOrFail();

        $this->assertEquals(['L', 'M', 'X', 'J', 'V'], $colaborador->dias_laborales);
        $this->assertEquals('8.50', (string) $colaborador->horas_diarias);

        $this->assertDatabaseHas('department_user', [
            'department_id' => $this->department->id,
            'user_id' => $colaborador->id,
            'role_id' => $this->role->id,
            'es_principal' => true,
        ]);

        $this->assertDatabaseHas('sub_department_user', [
            'sub_department_id' => $this->subDepartment->id,
            'user_id' => $colaborador->id,
        ]);
    }

    public function test_un_doble_envio_no_crea_dos_colaboradores(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(FormColaborador::class, ['enModal' => true])
            ->set($this->datosValidos());

        // Primer envio: crea el colaborador.
        $component->call('save')->assertHasNoErrors();
        $this->assertSame(1, User::where('email', 'edison.ortiz@example.test')->count());

        // Segundo envio (simulando un doble clic): mismos datos, ningun reset
        // de por medio. Debe fallar por correo duplicado sin crear un segundo
        // registro ni lanzar una excepcion no controlada.
        $component->call('save')->assertHasErrors(['email']);
        $this->assertSame(1, User::where('email', 'edison.ortiz@example.test')->count(), 'El doble envio no debe crear un segundo colaborador.');
    }

    public function test_una_validacion_fallida_por_otro_motivo_conserva_los_campos_alpine(): void
    {
        $datos = $this->datosValidos();
        // Motivo de fallo ajeno a los campos Alpine: contrasenas que no coinciden.
        $datos['password_confirmation'] = 'no-coincide';

        $component = Livewire::actingAs($this->admin)
            ->test(FormColaborador::class, ['enModal' => true])
            ->set($datos)
            ->call('save');

        $component->assertHasErrors(['password']);

        foreach (['department_id', 'sub_department_id', 'role_id', 'diasLaborales', 'horasDiarias'] as $propiedad) {
            $component->assertSet(
                $propiedad,
                $datos[$propiedad],
                "El campo Alpine [{$propiedad}] no deberia vaciarse cuando el fallo de validacion es por otro motivo (contrasena)."
            );
        }
    }
}
