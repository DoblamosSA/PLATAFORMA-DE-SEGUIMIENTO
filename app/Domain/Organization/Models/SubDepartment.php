<?php

namespace App\Domain\Organization\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Factories\SubDepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubDepartment extends Model
{
    use HasFactory, SoftDeletes;

    /** Iconos del catalogo (x-icon) ofrecidos al crear/editar un subdepartamento. */
    public const ICONOS = ['sitemap', 'code', 'support', 'server', 'building', 'briefcase', 'tasks', 'folder', 'shield-check', 'sparkles'];

    /** Colores de acento disponibles: clave => clases Tailwind (badge, gradiente para tarjetas/barras, texto del icono). */
    public const COLORES = [
        'slate' => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-slate-500/15 dark:text-slate-300', 'gradiente' => 'from-slate-600 to-slate-700', 'icono' => 'text-slate-400 dark:text-slate-500'],
        'indigo' => ['badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300', 'gradiente' => 'from-indigo-600 to-indigo-700', 'icono' => 'text-indigo-500 dark:text-indigo-400'],
        'teal' => ['badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300', 'gradiente' => 'from-teal-600 to-teal-700', 'icono' => 'text-teal-500 dark:text-teal-400'],
        'cyan' => ['badge' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300', 'gradiente' => 'from-cyan-600 to-cyan-700', 'icono' => 'text-cyan-500 dark:text-cyan-400'],
        'sky' => ['badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'gradiente' => 'from-sky-600 to-sky-700', 'icono' => 'text-sky-500 dark:text-sky-400'],
        'amber' => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', 'gradiente' => 'from-amber-600 to-amber-700', 'icono' => 'text-amber-500 dark:text-amber-400'],
        'emerald' => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'gradiente' => 'from-emerald-600 to-emerald-700', 'icono' => 'text-emerald-500 dark:text-emerald-400'],
        'rose' => ['badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', 'gradiente' => 'from-rose-600 to-rose-700', 'icono' => 'text-rose-500 dark:text-rose-400'],
        'violet' => ['badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300', 'gradiente' => 'from-violet-600 to-violet-700', 'icono' => 'text-violet-500 dark:text-violet-400'],
    ];

    protected static function newFactory(): SubDepartmentFactory
    {
        return SubDepartmentFactory::new();
    }

    protected $fillable = [
        'department_id',
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'color',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sub_department_user')->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'sub_department_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'sub_department_id');
    }

    /** Clases Tailwind (badge/gradiente/icono) del color de acento asignado. */
    public function colores(): array
    {
        return self::COLORES[$this->color] ?? self::COLORES['slate'];
    }
}
