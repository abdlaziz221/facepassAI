<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2, US-020 : table employes (PROFIL métier).
 *
 * L'authentification reste dans `users` (STI Sprint 1).
 * Cette table stocke uniquement les données métier propres
 * aux employés : matricule, poste, département, salaire, photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();

            // FK vers users (cascade : si on supprime le user, le profil suit)
            $table->foreignId('user_id')
                  ->unique()                // 1 user = 1 profil employé
                  ->constrained()
                  ->cascadeOnDelete();

            // Identifiant métier
            $table->string('matricule', 20)->unique();

            // Données poste & affectation
            $table->string('poste', 100);
            $table->string('departement', 100);

            // Rémunération
            $table->decimal('salaire_brut', 12, 2)->default(0);

            // Photo faciale (chemin local OU embedding base64 selon BNF-06)
            $table->string('photo_faciale')->nullable();

            $table->timestamps();

            // Index : matricule (recherche), departement (filtres dashboard)
            $table->index('matricule');
            $table->index('departement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
