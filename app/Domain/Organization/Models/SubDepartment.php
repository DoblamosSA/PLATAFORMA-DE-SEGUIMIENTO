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

    /**
     * Colores de acento disponibles: clave => clases Tailwind (badge, gradiente
     * para tarjetas/barras, texto del icono). Es la paleta completa de Tailwind
     * (22 familias), asi todos los subdepartamentos pueden tener un color bien
     * distinguible y atractivo. IMPORTANTE: estas clases viven en un archivo PHP,
     * fuera del `content` que escanea Tailwind (solo mira .blade.php) - por eso
     * cada clase usada aqui debe existir tambien, literal, en el `safelist` de
     * tailwind.config.js o no se compila y el color no se ve.
     */
    public const COLORES = [
        'slate' => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-slate-500/15 dark:text-slate-300', 'gradiente' => 'from-slate-600 to-slate-700', 'icono' => 'text-slate-400 dark:text-slate-500'],
        'gray' => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300', 'gradiente' => 'from-gray-600 to-gray-700', 'icono' => 'text-gray-500 dark:text-gray-400'],
        'zinc' => ['badge' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300', 'gradiente' => 'from-zinc-600 to-zinc-700', 'icono' => 'text-zinc-500 dark:text-zinc-400'],
        'neutral' => ['badge' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-500/15 dark:text-neutral-300', 'gradiente' => 'from-neutral-600 to-neutral-700', 'icono' => 'text-neutral-500 dark:text-neutral-400'],
        'stone' => ['badge' => 'bg-stone-100 text-stone-700 dark:bg-stone-500/15 dark:text-stone-300', 'gradiente' => 'from-stone-600 to-stone-700', 'icono' => 'text-stone-500 dark:text-stone-400'],
        'red' => ['badge' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', 'gradiente' => 'from-red-600 to-red-700', 'icono' => 'text-red-500 dark:text-red-400'],
        'orange' => ['badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300', 'gradiente' => 'from-orange-600 to-orange-700', 'icono' => 'text-orange-500 dark:text-orange-400'],
        'amber' => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', 'gradiente' => 'from-amber-600 to-amber-700', 'icono' => 'text-amber-500 dark:text-amber-400'],
        'yellow' => ['badge' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-300', 'gradiente' => 'from-yellow-600 to-yellow-700', 'icono' => 'text-yellow-500 dark:text-yellow-400'],
        'lime' => ['badge' => 'bg-lime-100 text-lime-700 dark:bg-lime-500/15 dark:text-lime-300', 'gradiente' => 'from-lime-600 to-lime-700', 'icono' => 'text-lime-500 dark:text-lime-400'],
        'green' => ['badge' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300', 'gradiente' => 'from-green-600 to-green-700', 'icono' => 'text-green-500 dark:text-green-400'],
        'emerald' => ['badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'gradiente' => 'from-emerald-600 to-emerald-700', 'icono' => 'text-emerald-500 dark:text-emerald-400'],
        'teal' => ['badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300', 'gradiente' => 'from-teal-600 to-teal-700', 'icono' => 'text-teal-500 dark:text-teal-400'],
        'cyan' => ['badge' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300', 'gradiente' => 'from-cyan-600 to-cyan-700', 'icono' => 'text-cyan-500 dark:text-cyan-400'],
        'sky' => ['badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'gradiente' => 'from-sky-600 to-sky-700', 'icono' => 'text-sky-500 dark:text-sky-400'],
        'blue' => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300', 'gradiente' => 'from-blue-600 to-blue-700', 'icono' => 'text-blue-500 dark:text-blue-400'],
        'indigo' => ['badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300', 'gradiente' => 'from-indigo-600 to-indigo-700', 'icono' => 'text-indigo-500 dark:text-indigo-400'],
        'violet' => ['badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300', 'gradiente' => 'from-violet-600 to-violet-700', 'icono' => 'text-violet-500 dark:text-violet-400'],
        'purple' => ['badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300', 'gradiente' => 'from-purple-600 to-purple-700', 'icono' => 'text-purple-500 dark:text-purple-400'],
        'fuchsia' => ['badge' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-300', 'gradiente' => 'from-fuchsia-600 to-fuchsia-700', 'icono' => 'text-fuchsia-500 dark:text-fuchsia-400'],
        'pink' => ['badge' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/15 dark:text-pink-300', 'gradiente' => 'from-pink-600 to-pink-700', 'icono' => 'text-pink-500 dark:text-pink-400'],
        'rose' => ['badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', 'gradiente' => 'from-rose-600 to-rose-700', 'icono' => 'text-rose-500 dark:text-rose-400'],
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
