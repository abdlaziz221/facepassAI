<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 Horaires carte 5 (US-042) — Table dédiée des jours fériés
 * et exceptions de fermeture de l'entreprise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jours_feries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jours_feries');
    }
};
