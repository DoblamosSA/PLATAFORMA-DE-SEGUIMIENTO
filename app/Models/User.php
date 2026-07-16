<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'area',
        'cargo',
        'activo',
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
        return $this->rol === 'admin';
    }

    public function esLider(): bool
    {
        return in_array($this->rol, ['admin', 'lider'], true);
    }
}
