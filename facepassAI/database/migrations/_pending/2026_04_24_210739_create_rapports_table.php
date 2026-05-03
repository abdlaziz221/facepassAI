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
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genere_par')->constrained('employes')->onDelete('cascade');
            $table->string('type'); // ex: "presences", "retards", "absences"
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('format', ['pdf', 'excel']);
            $table->string('fichier')->nullable(); // chemin du fichier généré
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
