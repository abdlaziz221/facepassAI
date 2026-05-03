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
        Schema::create('logs_systeme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->nullable()->constrained('employes')->onDelete('set null');
            $table->dateTime('date_heure');
            $table->string('type_action'); // ex: "connexion", "modification", "suppression"
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_systeme');
    }
};
