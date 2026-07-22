<?php

namespace App\Domain\Organization\DTOs;

final readonly class EffectivePermissionSetData
{
    /**
     * @param  array<int, string>  $permissionSlugs
     */
    public function __construct(
        public int $roleId,
        public array $permissionSlugs,
    ) {}

    public function has(string $permissionSlug): bool
    {
        return in_array($permissionSlug, $this->permissionSlugs, true);
    }
}
