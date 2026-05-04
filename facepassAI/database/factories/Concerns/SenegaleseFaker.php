<?php

namespace Database\Factories\Concerns;

/**
 * Helper de factory : génère des noms sénégalais aléatoires.
 *
 * Utilisé par EmployeFactory, ConsultantFactory, GestionnaireFactory
 * et AdministrateurFactory pour avoir des comptes de démo cohérents
 * avec le contexte du projet (ESP Dakar).
 */
trait SenegaleseFaker
{
    /**
     * Prénoms sénégalais courants (mixés masculin/féminin).
     *
     * @var list<string>
     */
    private static array $senegalFirstNames = [
        // Masculins
        'Mamadou', 'Moussa', 'Cheikh', 'Ousmane', 'Abdoulaye', 'Boubacar',
        'Babacar', 'Modou', 'Lamine', 'Ibrahima', 'Pape', 'Souleymane',
        'Demba', 'Aliou', 'Bocar', 'Birame', 'Saliou', 'Pathé', 'Serigne',
        'Abdou', 'Amadou', 'Omar', 'Bassirou', 'Sidy', 'Ndiaga', 'Idrissa',
        // Féminins
        'Aïssatou', 'Fatou', 'Mariama', 'Khadija', 'Awa', 'Aminata',
        'Mame', 'Adji', 'Bineta', 'Coumba', 'Ndèye', 'Penda',
        'Ramatoulaye', 'Soda', 'Maimouna', 'Diarra', 'Astou', 'Marème',
        'Aida', 'Yacine', 'Khady', 'Rokhaya', 'Sokhna', 'Fanta', 'Adama',
    ];

    /**
     * Noms de famille sénégalais courants.
     *
     * @var list<string>
     */
    private static array $senegalLastNames = [
        'Diop', 'Ndiaye', 'Sow', 'Diallo', 'Sarr', 'Faye', 'Sy', 'Ba',
        'Mbaye', 'Cissé', 'Gueye', 'Wade', 'Niang', 'Fall', 'Thiam',
        'Sène', 'Gning', 'Camara', 'Kane', 'Touré', 'Diagne', 'Diaw',
        'Mbow', 'Sané', 'Kébé', 'Tine', 'Lo', 'Seck', 'Mbacké', 'Bâ',
        'Niasse', 'Samb', 'Pouye', 'Sakho', 'Dieng', 'Diatta', 'Goudiaby',
    ];

    /**
     * Retourne un nom complet sénégalais aléatoire (Prénom Nom).
     */
    protected function senegaleseName(): string
    {
        return fake()->randomElement(self::$senegalFirstNames)
            . ' '
            . fake()->randomElement(self::$senegalLastNames);
    }

    /**
     * Génère un email cohérent avec un nom (prenom.nom@facepass.test).
     */
    protected function senegaleseEmail(string $name): string
    {
        $cleaned = strtolower(
            strtr($name, [
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'à' => 'a', 'â' => 'a', 'ä' => 'a',
                'ô' => 'o', 'ö' => 'o',
                'î' => 'i', 'ï' => 'i',
                'û' => 'u', 'ü' => 'u',
                'ç' => 'c',
                ' ' => '.',
            ])
        );

        // Suffixe random pour garantir l'unicité
        return $cleaned . '.' . fake()->unique()->numberBetween(100, 9999) . '@facepass.test';
    }
}
