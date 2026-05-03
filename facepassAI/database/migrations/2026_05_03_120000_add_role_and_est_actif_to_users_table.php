<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes "role" et "est_actif" à la table users
     * pour mettre en place la STI (Single Table Inheritance) :
     *   employe / consultant / gestionnaire / administrateur.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'employe',
                'consultant',
                'gestionnaire',
                'administrateur',
            ])->default('employe')->after('email')->index();

            $table->boolean('est_actif')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'est_actif']);
        });
    }
};
