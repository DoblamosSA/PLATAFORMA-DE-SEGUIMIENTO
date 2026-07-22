<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\SlaPolicy;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PermissionSeeder::class, RoleSeeder::class]);
        $this->seedSlaPolicies();
        $usuarios = $this->seedUsuarios();
        $this->call(SuperAdminBackfillSeeder::class);
        $this->seedDemo($usuarios);
    }

    /**
     * Matriz de SLA (horas de resolucion) por tipo y prioridad.
     */
    private function seedSlaPolicies(): void
    {
        $matriz = [
            // tipo            critica  alta  media  baja
            'soporte' => [4,     8,    24,   72],
            'software' => [8,     24,   72,   160],
            'infraestructura' => [2,     6,    24,   72],
        ];

        $prioridades = ['critica', 'alta', 'media', 'baja'];

        foreach ($matriz as $tipo => $horas) {
            foreach ($prioridades as $i => $prioridad) {
                SlaPolicy::updateOrCreate(
                    ['tipo' => $tipo, 'prioridad' => $prioridad],
                    ['horas_resolucion' => $horas[$i], 'activo' => true],
                );
            }
        }
    }

    /**
     * @return array<string, User>
     */
    private function seedUsuarios(): array
    {
        $diasCompletos = ['L', 'M', 'X', 'J', 'V'];

        $admin = User::updateOrCreate(
            ['email' => 'admin@gestionti.local'],
            [
                'name' => 'Administrador TI',
                'password' => Hash::make('password'),
                'rol' => 'admin',
                'area' => 'general',
                'cargo' => 'Director de TI',
                'activo' => true,
                'dias_laborales' => $diasCompletos,
                'horas_diarias' => 8,
            ],
        );

        $lider = User::updateOrCreate(
            ['email' => 'lider.software@gestionti.local'],
            [
                'name' => 'Laura Gomez',
                'password' => Hash::make('password'),
                'rol' => 'lider',
                'area' => 'software',
                'cargo' => 'Coordinador de Desarrollo',
                'activo' => true,
                'dias_laborales' => $diasCompletos,
                'horas_diarias' => 8,
            ],
        );

        $evaluador = User::updateOrCreate(
            ['email' => 'evaluador@gestionti.local'],
            [
                'name' => 'Elena Vargas',
                'password' => Hash::make('password'),
                'rol' => 'evaluador',
                'area' => 'general',
                'cargo' => 'Evaluadora de Certificación',
                'activo' => true,
                'dias_laborales' => $diasCompletos,
                'horas_diarias' => 6,
            ],
        );

        $tecnicos = [
            ['Carlos Ruiz', 'carlos@gestionti.local', 'software', 8],
            ['Ana Torres', 'ana@gestionti.local', 'soporte', 8],
            ['Miguel Diaz', 'miguel@gestionti.local', 'infraestructura', 6],
            ['Sofia Leon', 'sofia@gestionti.local', 'soporte', 8],
        ];

        $creados = ['admin' => $admin, 'lider' => $lider, 'evaluador' => $evaluador];

        foreach ($tecnicos as [$nombre, $email, $area, $horas]) {
            $creados[$email] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                    'rol' => 'tecnico',
                    'area' => $area,
                    'cargo' => 'Colaborador de '.ucfirst($area),
                    'activo' => true,
                    'dias_laborales' => $diasCompletos,
                    'horas_diarias' => $horas,
                ],
            );
        }

        return $creados;
    }

    /**
     * Proyectos y tareas de ejemplo con distintos estados de cumplimiento.
     *
     * @param  array<string, User>  $usuarios
     */
    private function seedDemo(array $usuarios): void
    {
        if (Project::exists()) {
            return; // no duplicar demo en re-seed
        }

        $admin = $usuarios['admin'];
        $lider = $usuarios['lider'];
        $tecnicos = collect($usuarios)->filter(fn ($u) => $u->rol === 'tecnico')->values();

        $proyectos = [
            ['Portal de Clientes v2', 'software', 'en_progreso', 'alta'],
            ['Migracion de Servidores', 'infraestructura', 'en_progreso', 'critica'],
            ['Mesa de Ayuda 2026', 'soporte', 'planeado', 'media'],
        ];

        foreach ($proyectos as [$nombre, $tipo, $estado, $prioridad]) {
            $proyecto = Project::create([
                'nombre' => $nombre,
                'descripcion' => 'Proyecto de ejemplo generado por el seeder.',
                'tipo' => $tipo,
                'estado' => $estado,
                'prioridad' => $prioridad,
                'responsable_id' => $lider->id,
                'fecha_inicio' => now()->subDays(20),
                'fecha_fin_estimada' => now()->addDays(40),
            ]);

            // Equipo del proyecto: todos los tecnicos participan
            $proyecto->equipo()->sync($tecnicos->pluck('id'));

            // Tareas: mezcla de a tiempo, vencidas y abiertas
            $this->crearTarea($proyecto, $tipo, 'alta', 'completada', $tecnicos, $admin, aTiempo: true, diasAtras: 10);
            $this->crearTarea($proyecto, $tipo, 'critica', 'completada', $tecnicos, $admin, aTiempo: false, diasAtras: 8);
            $this->crearTarea($proyecto, $tipo, 'media', 'en_progreso', $tecnicos, $admin, diasAtras: 2);
            $this->crearTarea($proyecto, $tipo, 'alta', 'pendiente', $tecnicos, $admin, diasAtras: 6, vencidaAbierta: true);

            $proyecto->recalcularProgreso();

            // Crear el tablero Kanban y ubicar las tareas por su estado
            $proyecto->asegurarColumnas();
        }
    }

    private function crearTarea(
        Project $proyecto,
        string $tipo,
        string $prioridad,
        string $estado,
        $tecnicos,
        User $creador,
        bool $aTiempo = true,
        int $diasAtras = 3,
        bool $vencidaAbierta = false,
    ): void {
        $asignado = $tecnicos->random();
        $fechaAsignacion = now()->subDays($diasAtras);

        $task = new Task([
            'project_id' => $proyecto->id,
            'titulo' => 'Actividad '.fake()->words(3, true),
            'descripcion' => fake()->sentence(10),
            'tipo' => $tipo,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'asignado_id' => $asignado->id,
            'creado_por' => $creador->id,
            'fecha_asignacion' => $fechaAsignacion,
        ]);
        $task->aplicarSla();

        // Para simular una tarea abierta ya vencida, forzamos fecha limite pasada
        if ($vencidaAbierta) {
            $task->fecha_limite = now()->subDay();
        }

        if ($estado === 'completada') {
            $task->fecha_inicio_real = $fechaAsignacion->copy()->addHours(2);
            $limite = $task->fecha_limite;
            $task->fecha_completada = $aTiempo
                ? $limite->copy()->subHours(3)
                : $limite->copy()->addHours(12);
            $task->cumplida_a_tiempo = $aTiempo;
        } elseif ($estado === 'en_progreso') {
            $task->fecha_inicio_real = $fechaAsignacion->copy()->addHours(1);
        }

        $task->save();

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $creador->id,
            'accion' => 'creacion',
            'detalle' => 'Tarea creada y asignada a '.$asignado->name,
        ]);
    }
}
