<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 (Horaires), US-040 — Configuration des horaires de l'entreprise.
 *
 * Table singleton : une seule ligne pour tout le système. Stocke les
 * jours ouvrables, les heures clés et les jours fériés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horaire_configs', function (Blueprint $table) {
            $table->id();
            $table->json('jours_ouvrables');                // ['lundi', 'mardi', ...]
            $table->time('heure_arrivee');                  // 09:00
            $table->time('heure_debut_pause');              // 12:00
            $table->time('heure_fin_pause');                // 13:00
            $table->time('heure_depart');                   // 18:00
            $table->json('jours_feries')->nullable();       // ['2026-01-01', '2026-05-01', ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horaire_configs');
    }
};
