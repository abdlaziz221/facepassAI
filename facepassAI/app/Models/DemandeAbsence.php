<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande d'absence d'un employé (Sprint 4 Horaires carte 6, US-050).
 *
 * Workflow :
 *   1. L'employé crée une demande → statut = en_attente
 *   2. Un gestionnaire la valide → statut = validee + gestionnaire_id + commentaire
 *   3. Ou la refuse → statut = refusee + gestionnaire_id + commentaire
 *
 * Le gestionnaire est nullable car une demande peut rester en attente.
 * Le motif est obligatoire à la création.
 *
 * Relations :
 *   - employe()      : belongsTo EmployeProfile (FK employe_id → employes.id)
 *   - gestionnaire() : belongsTo User (FK gestionnaire_id → users.id, nullable)
 */
class DemandeAbsence extends Model
{
    use HasFactory;

    protected $table = 'demandes_absence';

    /** Statuts possibles d'une demande. */
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE    = 'validee';
    public const STATUT_REFUSEE    = 'refusee';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDEE,
        self::STATUT_REFUSEE,
    ];

    protected $fillable = [
        'employe_id',
        'gestionnaire_id',
        'date_debut',
        'date_fin',
        'motif',
        'statut',
        'commentaire_gestionnaire',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    protected $attributes = [
        'statut' => self::STATUT_EN_ATTENTE,
    ];

    /**
     * L'employé (profil métier) qui a fait la demande.
     */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(EmployeProfile::class, 'employe_id');
    }

    /**
     * Le gestionnaire qui a validé ou refusé la demande.
     * Null tant que la demande est en attente.
     */
    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    /**
     * Indique si la demande est encore en attente de décision.
     */
    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function estRefusee(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }
}
