<?php

namespace App\Domain\Organization\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    protected $fillable = [
        'nombre',
        'slug',
        'parent_role_id',
        'department_id',
        'is_primary',
        'is_deletable',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_deletable' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('tipo')
            ->withTimestamps();
    }

    /** Permisos que este rol otorga explicitamente (independiente de lo heredado). */
    public function grantedPermissions(): BelongsToMany
    {
        return $this->permissions()->wherePivot('tipo', 'grant');
    }

    /** Permisos que este rol revoca explicitamente de lo heredado del padre. */
    public function deniedPermissions(): BelongsToMany
    {
        return $this->permissions()->wherePivot('tipo', 'deny');
    }
}
