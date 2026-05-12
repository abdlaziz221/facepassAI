<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 Horaires carte 6 (US-050) — Table des demandes d'absence.
 *
 * Chaque demande :
 *   - Est faite par un employé (FK employes.id)
 *   - Sera validée ou refusée par un gestionnaire (FK users.id, nullable
 *     tant qu'elle est en attente)
 *   - Couvre une plage de dates (date_debut → date_fin)
 *   - Inclut un motif obligatoire
 *   - Peut recevoir un commentaire du gestionnaire à la validation/refus
 *   - A un statut : en_attente (défaut), validee, refusee
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_absence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')
                ->constrained('employes')
                ->cascadeOnDelete();
            $table->foreignId('gestionnaire_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])
                ->default('en_attente');
            $table->text('commentaire_gestionnaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_absence');
    }
};
