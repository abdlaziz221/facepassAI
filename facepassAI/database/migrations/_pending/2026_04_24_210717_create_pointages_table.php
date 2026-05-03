<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('jours_travail_id')->nullable()->constrained('jours_travail')->onDelete('set null');
            $table->dateTime('date_heure');
            $table->enum('type', ['arrivee', 'debut_pause', 'fin_pause', 'depart']);
            $table->enum('statut', ['valide', 'en_retard', 'depart_anticipe'])->default('valide');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
