# FacePass AI

> **Plateforme de gestion des présences par reconnaissance faciale**
> Projet de fin d'études — École Supérieure Polytechnique de Dakar · Groupe G03 · 2025-2026
> Encadrante : Dr. Fatou Ngom

---

## Sommaire

1. [Présentation](#présentation)
2. [Stack technique](#stack-technique)
3. [Prérequis](#prérequis)
4. [Installation locale](#installation-locale)
5. [Commandes utiles](#commandes-utiles)
6. [Architecture](#architecture)
7. [Conventions de nommage](#conventions-de-nommage)
8. [Comptes de démo](#comptes-de-démo)
9. [Équipe](#équipe)

---

## Présentation

FacePass AI est une plateforme web de **gestion des présences en entreprise** basée sur la **reconnaissance faciale**. Elle permet :

- Le pointage instantané des employés via leur visage
- La gestion des absences et des horaires de travail
- La consultation des pointages, retards et départs anticipés
- La génération de rapports (PDF / Excel)
- Le calcul du salaire en fonction des présences
- Une console d'administration complète (logs, gestionnaires, etc.)

L'application repose sur **4 rôles hiérarchiques** : `Employé` < `Consultant` < `Gestionnaire` < `Administrateur`.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | **Laravel 12** (PHP 8.2+) |
| Base de données | **MySQL 8** (utf8mb4_unicode_ci) |
| Frontend | **Tailwind CSS v3** + **Alpine.js v3** + **Vite** |
| Auth & RBAC | **Laravel Breeze** + **spatie/laravel-permission** |
| Reconnaissance faciale | Microservice Python **FastAPI** (Sprint 3) |
| Exports | **barryvdh/laravel-dompdf** + **maatwebsite/excel** (Sprint 5) |
| Audit | **spatie/laravel-activitylog** (Sprint 6) |

---

## Prérequis

À installer sur votre machine :

| Outil | Version min. | Vérifier |
|---|---|---|
| **PHP** | 8.2 | `php --version` |
| **Composer** | 2.x | `composer --version` |
| **Node.js** | 18+ | `node --version` |
| **NPM** | 9+ | `npm --version` |
| **MySQL** | 8.0 | `mysql --version` |
| **Git** | 2.x | `git --version` |

> **Windows** : Laragon ou WAMP recommandé pour Apache+MySQL+PHP en 1 clic.
> **macOS** : Herd ou MAMP.
> **Linux** : `apt install php8.2 composer mysql-server nodejs npm`.

---

## Installation locale

```bash
# 1. Cloner le dépôt
git clone https://github.com/abdlaziz221/facepassAI.git
cd facepassAI/facepassAI

# 2. Installer les dépendances PHP et JS
composer install
npm install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Créer la base de données MySQL
mysql -u root -e "CREATE DATABASE facepassai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Configurer .env
# Éditer .env et renseigner :
#   APP_NAME="FacePass AI"
#   APP_LOCALE=fr
#   DB_DATABASE=facepassai
#   DB_USERNAME=root
#   DB_PASSWORD=
#   MAIL_MAILER=log

# 6. Migrer + seeder la base
php artisan migrate:fresh --seed

# 7. Compiler les assets
npm run build       # ou `npm run dev` en développement

# 8. Lancer le serveur
php artisan serve
# → http://127.0.0.1:8000
```

---

## Commandes utiles

```bash
# Développement
php artisan serve                     # Lancer le serveur web (port 8000)
npm run dev                           # Watcher Vite (rafraîchit auto les assets)

# Base de données
php artisan migrate                   # Appliquer les migrations
php artisan migrate:fresh --seed      # Reset complet + seeders de démo
php artisan db:seed                   # Re-seeder uniquement
php artisan tinker                    # REPL Laravel (pour tester en console)

# Cache
php artisan optimize:clear            # Vider tous les caches (config, routes, vues)
php artisan view:clear                # Vider seulement les vues compilées

# Tests
php artisan test                      # Lancer tous les tests
php artisan test --filter=RbacTest    # Lancer un test spécifique

# Composer / NPM
composer dump-autoload                # Régénérer l'autoload PHP
npm install                           # Réinstaller les dépendances JS
```

---

## Architecture

Le projet suit une architecture **en couches** pour séparer la logique métier de l'accès aux données :

```
┌──────────────────────────────────────────────────────────────────┐
│  Controller     ←  reçoit les requêtes HTTP, valide, répond     │
│  ↓                                                               │
│  Service        ←  contient la logique métier (business rules)  │
│  ↓                                                               │
│  Repository     ←  abstrait l'accès à la base de données        │
│  ↓                                                               │
│  Model          ←  Eloquent (mapping DB ↔ objets PHP)           │
└──────────────────────────────────────────────────────────────────┘
```

**Pourquoi cette architecture ?**

- **Testabilité** : on peut mocker un repository pour tester un service sans BDD
- **Réutilisabilité** : un service peut être utilisé par plusieurs controllers (web + API)
- **Maintenabilité** : changer de SGBD ou de source de données = ne toucher qu'au repository

### STI (Single Table Inheritance)

La hiérarchie utilisateurs utilise la **STI** : une seule table `users` avec une colonne `role` (enum) qui détermine la sous-classe PHP. Voir `app/Models/User.php` et ses 4 enfants (`Employe`, `Consultant`, `Gestionnaire`, `Administrateur`).

---

## Conventions de nommage

### Fichiers et classes

| Type | Convention | Exemple |
|---|---|---|
| **Modèle** | `PascalCase` au singulier | `Employe.php`, `Pointage.php` |
| **Controller** | `PascalCase` + suffixe `Controller` | `EmployeController.php` |
| **Service** | `PascalCase` + suffixe `Service` | `PayrollService.php`, `FaceRecognitionService.php` |
| **Repository** | `PascalCase` + suffixe `Repository` | `UserRepository.php` |
| **Interface (contrat)** | `PascalCase` + suffixe `Interface` | `UserRepositoryInterface.php` |
| **Form Request** | `PascalCase` + suffixe `Request` | `StoreEmployeRequest.php` |
| **Migration** | snake_case + verbe descriptif | `2026_05_03_120000_add_role_and_est_actif_to_users_table.php` |
| **Factory** | `PascalCase` + suffixe `Factory` | `EmployeFactory.php` |
| **Seeder** | `PascalCase` + suffixe `Seeder` | `RolePermissionSeeder.php` |
| **Notification** | `PascalCase` + suffixe optionnel | `ResetPasswordFr.php` |
| **Middleware** | `PascalCase` (verbe descriptif) | `CheckAccountActive.php` |
| **Vue Blade** | snake_case ou kebab-case | `employes/index.blade.php` |
| **Composant Blade** | kebab-case | `text-input.blade.php` |

### Variables et méthodes

| Type | Convention | Exemple |
|---|---|---|
| **Variable PHP** | `camelCase` | `$totalEmployes`, `$dateArrivee` |
| **Méthode** | `camelCase`, verbe d'abord | `calculerSalaireBrut()`, `findActiveEmployes()` |
| **Constante / enum** | `UPPER_SNAKE` ou `PascalCase` | `Role::Employe`, `MAX_TENTATIVES = 3` |
| **Colonne BDD** | `snake_case` | `est_actif`, `salaire_brut`, `created_at` |
| **Table BDD** | `snake_case` au pluriel | `users`, `pointages`, `demandes_absence` |
| **Route nommée** | `kebab.case` ou `dot.case` | `employes.index`, `password.request` |

### Permissions spatie

Format : `<domaine>.<action>` (ex : `employes.create`, `pointages.view-all`).

Voir `database/seeders/RolePermissionSeeder.php` pour la liste complète (23 permissions).

### Exemples minimalistes

**Service** (`app/Services/UserService.php`) :

```php
namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUserFromRequest(array $data): User
    {
        // Logique métier ici (validation custom, hashage, événements...)
        return $this->userRepository->create($data);
    }
}
```

**Repository contract** (`app/Repositories/Contracts/UserRepositoryInterface.php`) :

```php
namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

**Repository implémentation** (`app/Repositories/UserRepository.php`) :

```php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    // ...
}
```

**Binding** (`app/Providers/RepositoryServiceProvider.php`) :

```php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\Contracts\UserRepositoryInterface::class,
        \App\Repositories\UserRepository::class,
    );
}
```

---

## Comptes de démo

Après `php artisan migrate:fresh --seed`, 4 comptes nominatifs sont créés (mot de passe identique : **`password`**) :

| Email | Rôle | Permissions |
|---|---|---|
| `admin@facepass.test` | Administrateur | Toutes (23) |
| `gestionnaire@facepass.test` | Gestionnaire | 19 (CRUD employés, validation absences, horaires, KPI) |
| `consultant@facepass.test` | Consultant | 12 (lecture étendue + rapports) |
| `employe@facepass.test` | Employé | 7 (pointages perso, absences perso, salaire perso) |

Le seeder crée aussi 8 employés et 2 consultants supplémentaires avec des données aléatoires.

---

## Équipe

| Membre | Rôle dans le projet |
|---|---|
| **Alioune Badara Barry** | Chef de projet, gestion Trello |
| **Souleymane Sirima Mbodj** | Backend Laravel |
| **Serigne Abdoul Aziz Ndiaye** | Repository GitHub |
| **Mohamed Moctar Niang** | Frontend / dashboards |

**Encadrante** : Dr. Fatou Ngom — ESP Dakar

---

## Statut Sprints

- ✅ **Sprint 0** — Initialisation (Laravel, MySQL, Tailwind, Alpine, Git, README)
- ✅ **Sprint 1** — Authentification & RBAC (Breeze, spatie permissions, STI, dashboards par rôle, page login custom, throttling, mot de passe oublié, déconnexion sécurisée)
- ⏳ **Sprint 2** — Gestion des employés (CRUD)
- ⏳ **Sprint 3** — Pointage biométrique (microservice Python)
- ⏳ **Sprint 4** — Horaires & demandes d'absence
- ⏳ **Sprint 5** — Consultations & rapports (PDF, Excel)
- ⏳ **Sprint 6** — Salaire & administration
- ⏳ **Sprint 7** — Qualité, sécurité & performance
- ⏳ **Sprint 8** — Déploiement & documentation

---

*FacePass AI · ESP Dakar · 2025-2026*
