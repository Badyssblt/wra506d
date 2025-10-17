<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case CLIENT = 'ROLE_CLIENT';
    case AGENT = 'ROLE_AGENT';
    case ADMIN = 'ROLE_ADMIN';
}
