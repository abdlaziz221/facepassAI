<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeders du projet facepassAI.
     *
     * Ordre important :
     *   1. RolePermissionSeeder — crée les rôles + permissions spatie
     *   2. UserHierarchySeeder  — crée les users de démo et leur assigne un rôle
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserHierarchySeeder::class,
        ]);
    }
}
