<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pointage biométrique d'un employé (US-032).
 *
 * Chaque enregistrement = un événement de présence :
 *   - arrivée du matin
 *   - début de pause (déjeuner)
 *   - fin de pause (retour de déjeuner)
 *   - départ du soir
 *
 * Le pointage peut être :
 *   - automatique (via reconnaissance faciale) → manuel = false
 *   - manuel (saisi par un gestionnaire pour palier un échec caméra) → manuel = true + motif_manuel
 *
 * La FK employe_id pointe sur la table `employes` (EmployeProfile),
 * pas directement sur `users`. C'est l'EmployeProfile qui porte le matricule,
 * le poste, le salaire et l'embedding facial — c'est lui qui pointe.
 *
 * Conformément à la BNF-06, on ne stocke pas l'image originale du visage.
 */
class Pointage extends Model
{
    use HasFactory;

    /** Types de pointage acceptés (correspond à l'enum de la migration). */
    public const TYPE_ARRIVEE     = 'arrivee';
    public const TYPE_DEBUT_PAUSE = 'debut_pause';
    public const TYPE_FIN_PAUSE   = 'fin_pause';
    public const TYPE_DEPART      = 'depart';

    public const TYPES = [
        self::TYPE_ARRIVEE,
        self::TYPE_DEBUT_PAUSE,
        self::TYPE_FIN_PAUSE,
        self::TYPE_DEPART,
    ];

    protected $fillable = [
        'employe_id',
        'type',
        'photo_capture',
        'manuel',
        'motif_manuel',
    ];

    protected $casts = [
        'manuel' => 'boolean',
    ];

    /**
     * Le pointage appartient à un EmployeProfile (FK avec cascadeOnDelete).
     */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(EmployeProfile::class, 'employe_id');
    }
}
