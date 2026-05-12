<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 Horaires carte 5 — Suppression de la colonne JSON `jours_feries`
 * de la table `jours_travail`, désormais gérée dans une table dédiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jours_travail', function (Blueprint $table) {
            if (Schema::hasColumn('jours_travail', 'jours_feries')) {
                $table->dropColumn('jours_feries');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jours_travail', function (Blueprint $table) {
            if (!Schema::hasColumn('jours_travail', 'jours_feries')) {
                $table->json('jours_feries')->nullable();
            }
        });
    }
};
