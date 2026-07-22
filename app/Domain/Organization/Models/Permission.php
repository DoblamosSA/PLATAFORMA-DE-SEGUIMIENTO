<?php

namespace App\Domain\Organization\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('tipo')
            ->withTimestamps();
    }
}
