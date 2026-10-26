<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Barber = 'barber';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Barber => 'Barbero',
        };
    }
}
