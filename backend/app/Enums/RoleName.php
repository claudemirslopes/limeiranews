<?php

namespace App\Enums;

enum RoleName: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case EditorChefe = 'editor_chefe';
    case Editor = 'editor';
    case Journalist = 'journalist';
    case Columnist = 'columnist';
    case Commercial = 'commercial';
    case Analyst = 'analyst';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Super administrador',
            self::Admin => 'Administrador',
            self::EditorChefe => 'Editor-chefe',
            self::Editor => 'Editor',
            self::Journalist => 'Jornalista',
            self::Columnist => 'Colunista',
            self::Commercial => 'Comercial',
            self::Analyst => 'Analista',
        };
    }

    /** @return list<self> */
    public static function administrators(): array
    {
        return [self::Superadmin, self::Admin];
    }

    /** @return list<self> */
    public static function editors(): array
    {
        return [self::EditorChefe, self::Editor];
    }

    /** @return list<self> */
    public static function writers(): array
    {
        return [self::Journalist, self::Columnist];
    }
}
