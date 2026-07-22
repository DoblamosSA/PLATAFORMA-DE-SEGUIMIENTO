<?php

namespace App\Domain\Organization\Models;

use App\Models\User;
use Database\Factories\SubDepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubDepartment extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): SubDepartmentFactory
    {
        return SubDepartmentFactory::new();
    }

    protected $fillable = [
        'department_id',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sub_department_user')->withTimestamps();
    }
}
