<?php

namespace App\Services;

use App\Models\JoursTravail;
use App\Models\Pointage;
use Carbon\CarbonInterface;

/**
 * Sprint 5 carte 3 (US-062) — Calcul des retards et départs anticipés.
 *
 * Compare l'heure réelle d'un pointage à l'heure théorique configurée
 * dans JoursTravail et renvoie l'écart en minutes (signé).
 *
 * Convention de signe :
 *   - écart POSITIF = heure réelle APRÈS l'heure théorique
 *   - écart NÉGATIF = heure réelle AVANT l'heure théorique
 *
 * Interprétation par type :
 *   - arrivee   : + = retard, - = en avance
 *   - fin_pause : + = retour tardif (retard), - = retour anticipé
 *   - depart    : + = heures sup, - = départ anticipé
 *   - debut_pause : + = pause prise tard, - = pause prise tôt
 */
class RetardService
{
    public function __construct(public readonly JoursTravail $config)
    {
    }

    /** Construit le service à partir de la configuration courante. */
    public static function fromCurrent(): self
    {
        return new self(JoursTravail::current());
    }

    /**
     * Heure théorique (format H:i) pour un type de pointage donné.
     * Renvoie null si le type n'a pas d'heure de référence.
     */
    public function heureTheoriquePour(string $type): ?string
    {
        $raw = match ($type) {
            Pointage::TYPE_ARRIVEE     => $this->config->heure_arrivee,
            Pointage::TYPE_DEBUT_PAUSE => $this->config->heure_debut_pause,
            Pointage::TYPE_FIN_PAUSE   => $this->config->heure_fin_pause,
            Pointage::TYPE_DEPART      => $this->config->heure_depart,
            default                    => null,
        };
        return $raw ? substr((string) $raw, 0, 5) : null;
    }

    /**
     * Écart en minutes entre l'heure réelle et l'heure théorique.
     * Retourne 0 si le type n'a pas d'heure de référence.
     */
    public function ecartEnMinutes(string $type, CarbonInterface $heureReelle): int
    {
        $theorique = $this->heureTheoriquePour($type);
        if ($theorique === null) {
            return 0;
        }

        $theoriqueDateTime = $heureReelle->copy()->setTimeFromTimeString($theorique);
        $diffSeconds = $heureReelle->getTimestamp() - $theoriqueDateTime->getTimestamp();

        return (int) round($diffSeconds / 60);
    }

    /**
     * Vrai si le pointage est un retard.
     * Concerne uniquement arrivée et fin de pause (retour tardif).
     */
    public function isRetard(string $type, CarbonInterface $heureReelle): bool
    {
        if (!in_array($type, [Pointage::TYPE_ARRIVEE, Pointage::TYPE_FIN_PAUSE], true)) {
            return false;
        }
        return $this->ecartEnMinutes($type, $heureReelle) > 0;
    }

    /**
     * Vrai si le pointage est un départ anticipé.
     * Concerne uniquement départ et début de pause (pause anticipée).
     */
    public function isDepartAnticipe(string $type, CarbonInterface $heureReelle): bool
    {
        if (!in_array($type, [Pointage::TYPE_DEPART, Pointage::TYPE_DEBUT_PAUSE], true)) {
            return false;
        }
        return $this->ecartEnMinutes($type, $heureReelle) < 0;
    }

    /**
     * Vrai si le pointage est à l'heure, dans la tolérance donnée.
     *
     * @param int $toleranceMinutes Marge en minutes (par défaut : 0).
     */
    public function isATemps(string $type, CarbonInterface $heureReelle, int $toleranceMinutes = 0): bool
    {
        if ($this->heureTheoriquePour($type) === null) {
            return true; // pas de référence = considéré à temps
        }
        return abs($this->ecartEnMinutes($type, $heureReelle)) <= $toleranceMinutes;
    }

    /**
     * Analyse complète d'un pointage Eloquent.
     *
     * @return array{type:string, heure_reelle:string, heure_theorique:?string, ecart_minutes:int, is_retard:bool, is_depart_anticipe:bool, is_a_temps:bool}
     */
    public function analyserPointage(Pointage $pointage): array
    {
        $reelle = $pointage->created_at;

        return [
            'type'               => $pointage->type,
            'heure_reelle'       => $reelle->format('H:i'),
            'heure_theorique'    => $this->heureTheoriquePour($pointage->type),
            'ecart_minutes'      => $this->ecartEnMinutes($pointage->type, $reelle),
            'is_retard'          => $this->isRetard($pointage->type, $reelle),
            'is_depart_anticipe' => $this->isDepartAnticipe($pointage->type, $reelle),
            'is_a_temps'         => $this->isATemps($pointage->type, $reelle),
        ];
    }
}
