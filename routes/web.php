<?php

use App\Livewire\Colaboradores\FormColaborador;
use App\Livewire\Colaboradores\ListaColaboradores;
use App\Livewire\Dashboard;
use App\Livewire\Informes\ReporteMensual;
use App\Livewire\Organization\Departamentos\ListaDepartamentos;
use App\Livewire\Organization\Permisos\ListaPermisos;
use App\Livewire\Organization\Roles\FormRole;
use App\Livewire\Organization\Roles\ListaRoles;
use App\Livewire\Organization\SubDepartamentos\FormSubDepartamento;
use App\Livewire\Organization\SubDepartamentos\ListaSubDepartamentos;
use App\Livewire\Proyectos\FormProyecto;
use App\Livewire\Proyectos\ListaProyectos;
use App\Livewire\Proyectos\TableroProyecto;
use App\Livewire\Proyectos\VerProyecto;
use App\Livewire\Tareas\FormTarea;
use App\Livewire\Tareas\ListaTareas;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // proyectos.crear/editar son pagina completa (no modal): el formulario
    // tiene el selector de equipo en vivo, fragil dentro de un modal (ver
    // roles.crear).
    Route::get('proyectos', ListaProyectos::class)->name('proyectos');
    Route::get('proyectos/nuevo', FormProyecto::class)->name('proyectos.crear');
    Route::get('proyectos/{project}', VerProyecto::class)->name('proyectos.ver');
    Route::get('proyectos/{project}/editar', FormProyecto::class)->name('proyectos.editar');
    Route::get('proyectos/{project}/tablero', TableroProyecto::class)->name('proyectos.tablero');

    // Modulo de tareas: solo administradores. tareas.crear/editar son pagina
    // completa (no modal): el formulario tiene varios campos en vivo
    // (proyecto, asignado, fechas, prioridad, subdepartamento), fragil dentro
    // de un modal (ver roles.crear).
    Route::middleware('can:admin')->group(function () {
        Route::get('tareas', ListaTareas::class)->name('tareas');
        Route::get('tareas/nueva', FormTarea::class)->name('tareas.crear');
        Route::get('tareas/{task}/editar', FormTarea::class)->name('tareas.editar');
    });

    Route::get('informes/cumplimiento', ReporteMensual::class)->name('informes.cumplimiento');

    // colaboradores.crear/editar son pagina completa (no modal): el formulario
    // tiene varios campos en vivo (departamento, dias laborales, horas
    // diarias) y ese patron es fragil dentro de un modal (ver roles.crear).
    Route::get('colaboradores', ListaColaboradores::class)->name('colaboradores');
    Route::get('colaboradores/nuevo', FormColaborador::class)->name('colaboradores.crear');
    Route::get('colaboradores/{colaborador}/editar', FormColaborador::class)->name('colaboradores.editar');

    Route::get('departamentos', ListaDepartamentos::class)->name('departamentos');
    Route::get('departamentos/nuevo', ListaDepartamentos::class)->name('departamentos.crear');
    Route::get('departamentos/{department}/editar', ListaDepartamentos::class)->name('departamentos.editar');

    // subdepartamentos.crear/editar son pagina completa (no modal): el
    // formulario tiene selectores de icono/color por wire:click, y ese patron
    // de varias idas y vueltas antes de guardar es fragil cuando el
    // componente se monta dinamicamente dentro de un modal (ver roles.crear).
    Route::get('subdepartamentos', ListaSubDepartamentos::class)->name('subdepartamentos');
    Route::get('subdepartamentos/nuevo', FormSubDepartamento::class)->name('subdepartamentos.crear');
    Route::get('subdepartamentos/{subDepartment}/editar', FormSubDepartamento::class)->name('subdepartamentos.editar');

    // A diferencia de los demas modulos, roles.crear/editar NO abren un modal:
    // el formulario de roles necesita varias idas y vueltas al servidor antes
    // de guardar (selector de rol padre en vivo + docenas de toggles de
    // permisos), y ese patron es fragil cuando el componente se monta
    // dinamicamente dentro de un modal en vez de ser la raiz de la pagina.
    Route::get('roles', ListaRoles::class)->name('roles');
    Route::get('roles/nuevo', FormRole::class)->name('roles.crear');
    Route::get('roles/{role}/editar', FormRole::class)->name('roles.editar');

    Route::get('permisos', ListaPermisos::class)->name('permisos');

    Route::view('profile', 'profile')->name('profile');

    // Web Push: alta/baja de la suscripcion del navegador actual.
    Route::post('push/subscribe', function (\Illuminate\Http\Request $request) {
        $datos = $request->validate([
            'endpoint' => 'required|string|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        \App\Models\PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $datos['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $datos['endpoint'],
                'p256dh' => $datos['keys']['p256dh'],
                'auth' => $datos['keys']['auth'],
            ],
        );

        return response()->json(['ok' => true]);
    })->name('push.subscribe');

    Route::post('push/unsubscribe', function (\Illuminate\Http\Request $request) {
        $datos = $request->validate(['endpoint' => 'required|string|max:2000']);

        \App\Models\PushSubscription::where('endpoint_hash', hash('sha256', $datos['endpoint']))->delete();

        return response()->json(['ok' => true]);
    })->name('push.unsubscribe');
});

require __DIR__.'/auth.php';
