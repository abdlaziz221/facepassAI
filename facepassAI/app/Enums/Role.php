<?php

namespace App\Enums;

/**
 * Énumération des rôles utilisateurs de la plateforme.
 *
 * Hiérarchie (du moins privilégié au plus privilégié) :
 *   Employe → Consultant → Gestionnaire → Administrateur
 *
 * La valeur stockée en base correspond à la chaîne (ex : "employe").
 */
enum Role: string
{
    case Employe        = 'employe';
    case Consultant     = 'consultant';
    case Gestionnaire   = 'gestionnaire';
    case Administrateur = 'administrateur';

    /**
     * Libellé lisible (pour les vues, les exports, etc.).
     */
    public function label(): string
    {
        return match ($this) {
            self::Employe        => 'Employé',
            self::Consultant     => 'Consultant',
            self::Gestionnaire   => 'Gestionnaire',
            self::Administrateur => 'Administrateur',
        };
    }

    /**
     * Liste des valeurs (utile pour les règles de validation Laravel,
     * ex : Rule::in(Role::values())).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
