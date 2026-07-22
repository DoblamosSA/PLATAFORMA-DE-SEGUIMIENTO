<?php

namespace App\Domain\Organization\Models;

use App\Models\User;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }

    /** Roles heredados propios de este departamento (no incluye los 5 roles primarios/globales). */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('role_id', 'es_principal')
            ->withTimestamps();
    }
}
