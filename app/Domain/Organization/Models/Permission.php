<?php

namespace App\Domain\Organization\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /** Etiquetas en español para los grupos del catalogo (vease PermissionSeeder). */
    public const GRUPO_LABELS = [
        'departments' => 'Departamentos',
        'subdepartments' => 'Subdepartamentos',
        'projects' => 'Proyectos',
        'tasks' => 'Tareas',
        'subtasks' => 'Subtareas',
        'roles' => 'Roles',
        'users' => 'Usuarios',
    ];

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }

    protected $fillable = [
        'slug',
        'nombre',
        'descripcion',
        'grupo',
    ];

    public static function grupoLabel(?string $grupo): string
    {
        return self::GRUPO_LABELS[$grupo] ?? ucfirst($grupo ?? 'general');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('tipo')
            ->withTimestamps();
    }
}
