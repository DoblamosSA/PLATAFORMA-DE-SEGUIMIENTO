<?php

namespace App\Models;

use App\Domain\Organization\Models\SubDepartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * Columnas iniciales del tablero Kanban. Cada una mapea a un estado
     * canonico de tarea para preservar la logica de SLA/metricas.
     */
    public const COLUMNAS_POR_DEFECTO = [
        ['nombre' => 'Pendiente',    'estado' => 'pendiente',   'color' => 'slate'],
        ['nombre' => 'En ejecucion', 'estado' => 'en_progreso', 'color' => 'sky'],
        ['nombre' => 'Terminada',    'estado' => 'completada',  'color' => 'amber'],
        ['nombre' => 'Certificada',  'estado' => 'completada',  'color' => 'emerald'],
    ];

    /** Dias de antelacion para considerar una tarea abierta "en riesgo" de vencer. */
    public const DIAS_ALERTA_VENCIMIENTO = 2;

    protected $fillable = [
        'nombre',
        'descripcion',
        'sub_department_id',
        'estado',
        'prioridad',
        'responsable_id',
        'fecha_inicio',
        'fecha_fin_estimada',
        'fecha_fin_real',
        'progreso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
            'fecha_fin_real' => 'date',
            'progreso' => 'integer',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function subDepartamento(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }

    /**
     * Limita el listado a los proyectos donde el usuario tiene acceso: el
     * admin ve todos; el resto solo los suyos (responsable o integrante del
     * equipo). El acceso al detalle/tablero ya se valida aparte con
     * usuarioPuedeGestionar().
     */
    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if ($user->esAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('responsable_id', $user->id)
                ->orWhereHas('equipo', fn (Builder $q2) => $q2->where('users.id', $user->id));
        });
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Equipo del proyecto (desarrolladores asignables a sus tareas). */
    public function equipo(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('rol_en_proyecto')
            ->withTimestamps();
    }

    /** Columnas del tablero Kanban, ordenadas. */
    public function columnas(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('posicion');
    }

    /**
     * Garantiza que el proyecto tenga un tablero: si no existen columnas,
     * crea las de por defecto y ubica las tareas existentes en la columna
     * que corresponde a su estado actual (sin alterar el estado).
     */
    public function asegurarColumnas(): void
    {
        if ($this->columnas()->exists()) {
            return;
        }

        foreach (self::COLUMNAS_POR_DEFECTO as $i => $c) {
            $this->columnas()->create([
                'nombre' => $c['nombre'],
                'estado' => $c['estado'],
                'color' => $c['color'],
                'posicion' => $i,
            ]);
        }

        $this->unsetRelation('columnas');

        // Ubicar tareas existentes (excepto canceladas) en su columna por estado.
        $this->tareas()
            ->whereNull('board_column_id')
            ->where('estado', '!=', 'cancelada')
            ->get()
            ->each(function (Task $t) {
                if ($columna = $this->columnaParaEstado($t->estado)) {
                    $t->board_column_id = $columna->id;
                    $t->save();
                }
            });
    }

    /**
     * Devuelve la primera columna que representa un estado dado. Para
     * 'en_revision' (sin columna por defecto) cae a la columna de
     * 'en_progreso'; si no hay coincidencia, a la primera columna.
     */
    public function columnaParaEstado(string $estado): ?BoardColumn
    {
        $columnas = $this->columnas()->get();

        if ($columnas->isEmpty()) {
            return null;
        }

        return $columnas->firstWhere('estado', $estado)
            ?? ($estado === 'en_revision' ? $columnas->firstWhere('estado', 'en_progreso') : null)
            ?? $columnas->first();
    }

    /**
     * Solo el admin, el responsable o un integrante del equipo pueden
     * administrar el tablero, mover cards o comentar.
     */
    public function usuarioPuedeGestionar(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->esAdmin() || $this->responsable_id === $user->id) {
            return true;
        }

        return $this->equipo()->where('users.id', $user->id)->exists();
    }

    /**
     * Indicadores de cumplimiento del proyecto completo, calculados
     * a partir de sus tareas.
     *
     * @return array<string, mixed>
     */
    public function metricasCumplimiento(): array
    {
        $completadas = $this->tareas()->where('estado', 'completada')->count();
        $aTiempo = $this->tareas()->where('estado', 'completada')->where('cumplida_a_tiempo', true)->count();
        $abiertas = $this->tareas()->abiertas()->count();
        $vencidas = $this->tareas()->vencidas()->count();

        return [
            'total' => $this->tareas()->count(),
            'completadas' => $completadas,
            'a_tiempo' => $aTiempo,
            'abiertas' => $abiertas,
            'vencidas' => $vencidas,
            'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : 0.0,
        ];
    }

    /**
     * Clasificacion visual del proyecto, calculada dinamicamente a partir
     * del estado real de sus tareas (no de fechas ni del estado propio del
     * proyecto):
     *  - 'planeado'  (azul)     ninguna tarea ha arrancado todavia (todas
     *                            siguen "pendiente"/sin tareas). Mientras no
     *                            se haya ejecutado nada, una pendiente vencida
     *                            no cuenta como riesgo: el proyecto sigue,
     *                            en la practica, en planeacion.
     *  - 'vencido'   (rojo)     ya hay trabajo en curso y al menos una tarea
     *                            abierta vencio.
     *  - 'en_riesgo' (amarillo) ya hay trabajo en curso y al menos una tarea
     *                            abierta vence dentro de DIAS_ALERTA_VENCIMIENTO dias.
     *  - 'saludable' (verde)    ya hay trabajo en curso y ninguna tarea esta
     *                            vencida ni en riesgo.
     *  - null                   proyecto cancelado.
     *
     * Acepta conteos ya calculados (p.ej. via withCount en un listado) para
     * evitar consultas N+1; si no se recibieron, los calcula bajo demanda.
     */
    public function semaforo(): ?string
    {
        if ($this->estado === 'cancelado') {
            return null;
        }

        $ejecutadas = $this->getAttribute('tareas_ejecutadas_count')
            ?? $this->tareas()->whereIn('estado', ['en_progreso', 'en_revision', 'completada'])->count();

        if ($ejecutadas == 0) {
            return 'planeado';
        }

        $vencidas = $this->getAttribute('tareas_vencidas_count')
            ?? $this->tareas()->vencidas()->count();

        if ($vencidas > 0) {
            return 'vencido';
        }

        $enRiesgo = $this->getAttribute('tareas_en_riesgo_count')
            ?? $this->tareas()->abiertas()
                ->whereNotNull('fecha_limite')
                ->whereBetween('fecha_limite', [now(), now()->addDays(self::DIAS_ALERTA_VENCIMIENTO)])
                ->count();

        if ($enRiesgo > 0) {
            return 'en_riesgo';
        }

        return 'saludable';
    }

    /**
     * Recalcula el progreso (% de tareas completadas) y lo persiste.
     */
    public function recalcularProgreso(): void
    {
        $total = $this->tareas()->whereNot('estado', 'cancelada')->count();

        if ($total === 0) {
            $this->update(['progreso' => 0]);

            return;
        }

        $completadas = $this->tareas()->where('estado', 'completada')->count();
        $this->update(['progreso' => (int) round(($completadas / $total) * 100)]);
    }
}
