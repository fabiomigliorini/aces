<?php

namespace App\Enums;

enum Permission: string
{
    // Projetos
    case PROJECT_VIEW = "project.view";
    case PROJECT_CREATE = "project.create";
    case PROJECT_UPDATE = "project.update";
    case PROJECT_DELETE = "project.delete";

    // Usuários
    case USER_VIEW = "user.view";
    case USER_CREATE = "user.create";
    case USER_UPDATE = "user.update";
    case USER_DELETE = "user.delete";

    // Tenants (somente admins da organization)
    case TENANT_MANAGE = "tenant.manage";

    public static function values(): array
    {
        return array_column(self::cases(), "value");
    }
}