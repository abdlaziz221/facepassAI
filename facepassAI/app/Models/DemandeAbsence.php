<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Demande d'absence d'un employé (Sprint 4 Horaires cartes 6 + 8, US-050/051).
 */
class DemandeAbsence extends Model
{
    use HasFactory;
    use LogsActivity;

    /** Sprint 6 carte 6 (US-091) — Configuration du log d'activité. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['statut', 'commentaire_gestionnaire', 'gestionnaire_id', 'date_debut', 'date_fin', 'motif'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'Demande d\'absence créée',
                'updated' => 'Demande d\'absence mise à jour',
                'deleted' => 'Demande d\'absence supprimée',
                default   => $event,
            })
            ->useLogName('absences');
    }

    protected $table = 'demandes_absence';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE    = 'validee';
    public const STATUT_REFUSEE    = 'refusee';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDEE,
        self::STATUT_REFUSEE,
    ];

    /** Statuts qui "bloquent" pour la détection de chevauchement. */
    public const STATUTS_BLOQUANTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDEE,
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

    public function employe(): BelongsTo
    {
        return $this->belongsTo(EmployeProfile::class, 'employe_id');
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    public function estEnAttente(): bool { return $this->statut === self::STATUT_EN_ATTENTE; }
    public function estValidee(): bool   { return $this->statut === self::STATUT_VALIDEE; }
    public function estRefusee(): bool   { return $this->statut === self::STATUT_REFUSEE; }

    // ============================================================
    // Sprint 4 carte 8 — Détection de chevauchement (US-051)
    // ============================================================

    /**
     * Indique si l'employé a déjà une demande qui chevauche la période.
     *
     * Deux périodes se chevauchent si :
     *   A.date_debut <= B.date_fin ET A.date_fin >= B.date_debut
     *
     * Seules les demandes en_attente ou validee sont prises en compte
     * (les refusées n'empêchent pas une nouvelle demande).
     *
     * @param int      $employeId  ID de l'EmployeProfile concerné
     * @param string   $debut      Date de début (format Y-m-d)
     * @param string   $fin        Date de fin (format Y-m-d)
     * @param int|null $excludeId  ID de la demande courante (pour update, exclure soi-même)
     */
    public static function hasOverlap(int $employeId, string $debut, string $fin, ?int $excludeId = null): bool
    {
        return static::query()
            ->where('employe_id', $employeId)
            ->whereIn('statut', self::STATUTS_BLOQUANTS)
            ->where('date_debut', '<=', $fin)
            ->where('date_fin', '>=', $debut)
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Retourne les demandes qui chevauchent une période donnée pour un employé.
     * Utile pour l'UI quand on veut montrer LESQUELLES chevauchent.
     */
    public static function findOverlaps(int $employeId, string $debut, string $fin, ?int $excludeId = null)
    {
        return static::query()
            ->where('employe_id', $employeId)
            ->whereIn('statut', self::STATUTS_BLOQUANTS)
            ->where('date_debut', '<=', $fin)
            ->where('date_fin', '>=', $debut)
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->orderBy('date_debut')
            ->get();
    }
}
