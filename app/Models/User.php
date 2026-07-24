<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Organization\Concerns\HasOrganizationAccess;
use App\Domain\Organization\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasOrganizationAccess;

    /** Codigos de dia laboral, en el orden en que se muestran en el formulario. */
    public const DIAS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

    /** Mapa codigo de dia -> Carbon::dayOfWeek (0=domingo ... 6=sabado). */
    public const DIAS_CARBON = ['L' => 1, 'M' => 2, 'X' => 3, 'J' => 4, 'V' => 5, 'S' => 6, 'D' => 0];

    public const ROLES_LABEL = [
        'admin' => 'Administrador',
        'lider' => 'Coordinador',
        'tecnico' => 'Colaborador',
        'evaluador' => 'Evaluador',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'area',
        'cargo',
        'activo',
        'telefono',
        'foto_path',
        'dias_laborales',
        'horas_diarias',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'dias_laborales' => 'array',
            'horas_diarias' => 'decimal:2',
        ];
    }

    /** Tareas asignadas a este usuario. */
    public function tareas(): HasMany
    {
        return $this->hasMany(Task::class, 'asignado_id');
    }

    /** Proyectos donde este usuario es responsable. */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Project::class, 'responsable_id');
    }

    /** Proyectos en cuyo equipo participa este usuario. */
    public function proyectosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('rol_en_proyecto')
            ->withTimestamps();
    }

    public function esAdmin(): bool
    {
        return $this->effectiveRol() === 'admin';
    }

    public function esLider(): bool
    {
        return in_array($this->effectiveRol(), ['admin', 'lider'], true);
    }

    /** Coordinador (o admin, que hereda todos los permisos). */
    public function esCoordinador(): bool
    {
        return in_array($this->effectiveRol(), ['admin', 'lider'], true);
    }

    public function esColaborador(): bool
    {
        return $this->effectiveRol() === 'tecnico';
    }

    public function esEvaluador(): bool
    {
        return $this->effectiveRol() === 'evaluador';
    }

    public function rolLabel(): string
    {
        return self::ROLES_LABEL[$this->rol] ?? ucfirst((string) $this->rol);
    }

    /**
     * Rol legado efectivo: normalmente users.rol, pero si el usuario tiene
     * mas de una identidad de rol simultanea y ya eligio una (vease
     * RoleContextService), se usa la resuelta a partir de esa eleccion.
     */
    private function effectiveRol(): string
    {
        return app(\App\Domain\Organization\Services\RoleContextService::class)->effectiveLegacyRol($this);
    }

    public function fotoUrl(): ?string
    {
        // URL relativa a la raiz (no absoluta con APP_URL) para que siempre
        // resuelva contra el host/puerto real desde el que se sirve la app.
        return $this->foto_path ? '/storage/'.$this->foto_path : null;
    }

    public function iniciales(): string
    {
        return collect(explode(' ', $this->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    }

    /**
     * Nombre del subdepartamento del colaborador (lo que antes se mostraba como
     * "área"). Devuelve un guion cuando aun no tiene subdepartamento asignado.
     */
    public function subDepartamentoNombre(): string
    {
        return $this->subDepartments->first()?->nombre ?? '—';
    }

    /**
     * Nombre del rol de departamento del colaborador (Administrador/Coordinador/
     * Colaborador/etc. segun el pivote department_user.role_id) - es el rol que
     * hoy se gestiona desde el formulario de colaborador. Distinto del enum
     * legado `rol` (admin/lider/tecnico/evaluador), que ya no se edita ahi.
     */
    public function rolDepartamentoNombre(): string
    {
        $departamento = $this->departments->first();

        if (! $departamento || ! $departamento->pivot->role_id) {
            return '—';
        }

        return Role::find($departamento->pivot->role_id)?->nombre ?? '—';
    }

    // ---------------------------------------------------------------
    // Disponibilidad y capacidad operativa
    // ---------------------------------------------------------------

    /** Capacidad semanal en horas: dias laborales seleccionados x horas diarias. */
    public function capacidadSemanal(): float
    {
        return count($this->dias_laborales ?? []) * (float) ($this->horas_diarias ?? 0);
    }

    /** True si el usuario trabaja el dia de semana indicado (Carbon::dayOfWeek: 0=domingo). */
    public function trabajaEnDiaSemana(int $dayOfWeek): bool
    {
        foreach ($this->dias_laborales ?? [] as $codigo) {
            if ((self::DIAS_CARBON[$codigo] ?? null) === $dayOfWeek) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------
    // Permisos de proyectos/tareas/subtareas/comentarios
    // ---------------------------------------------------------------

    /** Crear tareas requiere el permiso granular 'tasks.create' (via rol de departamento, primario o heredado). */
    public function puedeCrearTarea(): bool
    {
        return $this->hasPermission('tasks.create');
    }

    /** Editar tareas requiere el permiso granular 'tasks.edit'. */
    public function puedeEditarTarea(): bool
    {
        return $this->hasPermission('tasks.edit');
    }

    /** Crear proyectos requiere el permiso granular 'projects.create'. */
    public function puedeCrearProyecto(): bool
    {
        return $this->hasPermission('projects.create');
    }

    /**
     * Eliminar una tarea requiere el permiso granular 'tasks.delete'. Quien
     * ademas tenga rol admin puede eliminar aunque la tarea tenga subtareas;
     * el resto (ej. coordinador con 'tasks.delete') solo si no tiene.
     */
    public function puedeEliminarTarea(Task $task): bool
    {
        if (! $this->hasPermission('tasks.delete')) {
            return false;
        }

        if ($this->esAdmin()) {
            return true;
        }

        return ! $task->subtareas()->exists();
    }

    /** Crear subtareas requiere el permiso granular 'subtasks.create'. */
    public function puedeCrearSubtarea(): bool
    {
        return $this->hasPermission('subtasks.create');
    }

    /** Eliminar subtareas requiere el permiso granular 'subtasks.delete'. */
    public function puedeEliminarSubtarea(): bool
    {
        return $this->hasPermission('subtasks.delete');
    }

    public function puedeCrearComentario(): bool
    {
        return $this->esAdmin() || $this->esCoordinador() || $this->esColaborador() || $this->esEvaluador();
    }

    public function puedeEliminarComentario(): bool
    {
        return $this->esAdmin();
    }

    /** Solo el evaluador (o el admin) puede rechazar una tarea completada. */
    public function puedeRechazarTarea(): bool
    {
        return $this->esAdmin() || $this->esEvaluador();
    }
}
