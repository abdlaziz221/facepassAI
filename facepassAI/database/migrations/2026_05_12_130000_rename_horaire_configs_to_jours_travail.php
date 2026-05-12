<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 Horaires carte 1 — renomme horaire_configs en jours_travail
 * pour matcher la nomenclature métier de la Trello (JoursTravail).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('horaire_configs') && !Schema::hasTable('jours_travail')) {
            Schema::rename('horaire_configs', 'jours_travail');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('jours_travail') && !Schema::hasTable('horaire_configs')) {
            Schema::rename('jours_travail', 'horaire_configs');
        }
    }
};
