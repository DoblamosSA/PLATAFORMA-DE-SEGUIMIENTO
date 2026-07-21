<?php

use App\Livewire\Colaboradores\FormColaborador;
use App\Livewire\Colaboradores\ListaColaboradores;
use App\Livewire\Dashboard;
use App\Livewire\Informes\ReporteMensual;
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

    Route::get('proyectos', ListaProyectos::class)->name('proyectos');
    Route::get('proyectos/nuevo', FormProyecto::class)->name('proyectos.crear');
    Route::get('proyectos/{project}', VerProyecto::class)->name('proyectos.ver');
    Route::get('proyectos/{project}/editar', FormProyecto::class)->name('proyectos.editar');
    Route::get('proyectos/{project}/tablero', TableroProyecto::class)->name('proyectos.tablero');

    Route::get('tareas', ListaTareas::class)->name('tareas');
    Route::get('tareas/nueva', FormTarea::class)->name('tareas.crear');
    Route::get('tareas/{task}/editar', FormTarea::class)->name('tareas.editar');

    Route::get('informes/cumplimiento', ReporteMensual::class)->name('informes.cumplimiento');

    Route::get('colaboradores', ListaColaboradores::class)->name('colaboradores');
    Route::get('colaboradores/nuevo', FormColaborador::class)->name('colaboradores.crear');
    Route::get('colaboradores/{colaborador}/editar', FormColaborador::class)->name('colaboradores.editar');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
