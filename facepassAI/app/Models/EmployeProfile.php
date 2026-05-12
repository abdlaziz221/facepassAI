<?php

namespace App\Models;

use Database\Factories\EmployeProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil métier d'un Employé (Sprint 2, US-020).
 *
 * Contient les données spécifiques au poste : matricule, fonction,
 * département, salaire brut, photo faciale. Lié 1-1 à un User
 * (avec role=employe) via la colonne `user_id`.
 *
 * Pour accéder au profil depuis un Employe :  $employe->profile
 * Pour accéder au User depuis un profil :     $profile->user
 */
class EmployeProfile extends Model
{
    /** @use HasFactory<EmployeProfileFactory> */
    use HasFactory;

    protected $table = 'employes';

    protected $fillable = [
        'user_id',
        'matricule',
        'poste',
        'departement',
        'salaire_brut',
        'photo_faciale',
    ];

    protected function casts(): array
    {
        return [
            'salaire_brut' => 'decimal:2',
        ];
    }

    /**
     * Le user (STI : Employe) propriétaire de ce profil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper : retourne l'instance Employe (sous-classe STI) plutôt
     * que le User générique.
     */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'user_id');
    }

    /**
     * Mutateur : matricule toujours en MAJUSCULES.
     */
    public function setMatriculeAttribute(string $value): void
    {
        $this->attributes['matricule'] = strtoupper(trim($value));
    }
}
