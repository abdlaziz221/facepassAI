<?php

namespace App\Models;

use Database\Factories\EmployeProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Profil métier d'un Employé (Sprint 2, US-020).
 *
 * Contient les données spécifiques au poste : matricule, fonction,
 * département, salaire brut, photo faciale, embedding facial.
 * Lié 1-1 à un User (avec role=employe) via la colonne `user_id`.
 *
 * Pour accéder au profil depuis un Employe :  $employe->profile
 * Pour accéder au User depuis un profil :     $profile->user
 */
class EmployeProfile extends Model
{
    /** @use HasFactory<EmployeProfileFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'employes';

    protected $fillable = [
        'user_id',
        'matricule',
        'poste',
        'departement',
        'salaire_brut',
        'photo_faciale',
        'encodage_facial',
    ];

    protected function casts(): array
    {
        return [
            'salaire_brut'    => 'decimal:2',
            'encodage_facial' => 'array',
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

    /** Sprint 6 carte 6 (US-091) — Configuration du log d'activité. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['matricule', 'poste', 'departement', 'salaire_brut'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'Création employé',
                'updated' => 'Modification employé',
                'deleted' => 'Suppression employé',
                default   => $event,
            })
            ->useLogName('employes');
    }
}
