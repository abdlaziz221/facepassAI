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
        Schema::create('jours_travail', function (Blueprint $table) {
            $table->id();
            $table->string('jours_ouvrables'); // ex: "Lun,Mar,Mer,Jeu,Ven"
            $table->time('heure_arrivee');     // ex: 08:00
            $table->time('debut_pause');       // ex: 12:00
            $table->time('fin_pause');         // ex: 13:00
            $table->time('heure_depart');      // ex: 17:00
            $table->text('jours_feries')->nullable(); // JSON des dates fériées
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jours_travail');
    }
};
