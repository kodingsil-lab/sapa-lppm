<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helpers/AuthHelper.php';

class RoleMiddleware
{
    public static function handle(array $allowedRoles): bool
    {
        return checkRole($allowedRoles);
    }
}
