# facepassAI — Plateforme de Gestion des Présences (ESP Dakar)

Application Laravel 12 de gestion des présences avec reconnaissance faciale.

## Stack technique

- PHP 8.2+
- Laravel 12
- MySQL 8 (WAMP/Laragon en local)
- Tailwind CSS v4 + Alpine.js v3
- Microservice Python FastAPI pour la reconnaissance faciale (Sprint 3)

## Prérequis

| Outil | Version min | Vérifier | Lien |
|---|---|---|---|
| PHP | 8.2 | `php -v` | [php.net/downloads](https://www.php.net/downloads) |
| Composer | 2.x | `composer -V` | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js | 18 LTS | `node -v` | [nodejs.org](https://nodejs.org/) |
| npm | 9+ | `npm -v` | (livré avec Node) |
| MySQL | 8 | via WAMP / Laragon | [wampserver.com](https://www.wampserver.com/) |
| Git | 2.x | `git --version` | [git-scm.com](https://git-scm.com/) |

> 💡 Sous Windows, l'utilisation de **WAMP** ou **Laragon** est recommandée — ils embarquent déjà MySQL + phpMyAdmin.

## Installation

> ⚠️ Le code Laravel se trouve dans le **sous-dossier** `facepassAI/` du repo. Toutes les commandes ci-dessous doivent être lancées depuis ce sous-dossier (sauf `git clone`).

### 1. Cloner le projet

```bash
git clone https://github.com/abdlaziz221/facepassAI.git
cd facepassAI/facepassAI
```

### 2. Installer les dépendances

```bash
composer install        # Dépendances PHP
npm install             # Dépendances JS (Alpine, Vite, Tailwind)
```

### 3. Configurer l'environnement

```bash
copy .env.example .env       # Windows
# cp .env.example .env       # Linux / macOS
php artisan key:generate
```

Édite ensuite `.env` pour configurer la base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facepassai
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Créer la base de données

Démarre WAMP/Laragon, ouvre **phpMyAdmin** (`http://localhost/phpmyadmin/`) et crée la base :
- Nom : `facepassai`
- Interclassement : `utf8mb4_unicode_ci`

### 5. Lancer les migrations

```bash
php artisan migrate
```

### 6. Démarrer les serveurs

Dans deux terminaux séparés :

```bash
php artisan serve      # Backend Laravel sur http://127.0.0.1:8000
npm run dev            # Vite (hot reload assets) sur http://localhost:5173
```

Ouvre **http://127.0.0.1:8000** dans ton navigateur. ✅

## Architecture MVC étendue : Services & Repositories

Le projet suit une architecture en couches inspirée du DDD léger.
Trois rôles, trois responsabilités, **jamais mélangées** :

| Couche | Dossier | Responsabilité |
|---|---|---|
| Controller | `app/Http/Controllers` | Recevoir la requête HTTP, valider via Form Request, appeler **un seul** Service, renvoyer la réponse (vue/JSON). **Aucune logique métier.** |
| Service | `app/Services` | Logique **métier** : règles, calculs, orchestration de plusieurs repositories. **Ne touche jamais directement à Eloquent.** |
| Repository | `app/Repositories` | Accès à la **persistance** (Eloquent). C'est le seul endroit où l'on appelle `Model::query()`, `find()`, `create()`, etc. |

### Convention de nommage

- **Interface du Repository** : `app/Repositories/Contracts/{Entity}RepositoryInterface.php`
- **Implémentation du Repository** : `app/Repositories/{Entity}Repository.php`, hérite de `BaseRepository` et implémente l'interface.
- **Service** : `app/Services/{Entity}Service.php`. Reçoit les interfaces de repositories par injection dans le constructeur.

### Liaison Interface ↔ Implémentation

Toutes les liaisons sont déclarées dans `app/Providers/RepositoryServiceProvider.php` via la propriété `$bindings`. À chaque nouveau repository, ajouter une ligne :

```php
public array $bindings = [
    UserRepositoryInterface::class => UserRepository::class,
    // EmployeRepositoryInterface::class => EmployeRepository::class,
];
```

### Exemple minimal

```php
// Controller
class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function store(StoreUserRequest $request)
    {
        $user = $this->service->register($request->validated());
        return redirect()->route('users.show', $user);
    }
}

// Service
class UserService
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return $this->users->create($data);
    }
}

// Repository
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model) { parent::__construct($model); }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }
}
```

### Pourquoi cette architecture ?

- **Testabilité** : on peut mocker l'interface du Repository dans les tests du Service (pas besoin de base de données).
- **Réutilisabilité** : le `BaseRepository` factorise les méthodes CRUD courantes (`all`, `find`, `create`, `update`, `delete`, `paginate`).
- **Lisibilité** : un controller ne fait qu'une chose, un service une autre, un repository une troisième.

## Commandes utiles

### Backend Laravel

```bash
php artisan serve              # Démarre le serveur local (port 8000)
php artisan migrate            # Applique les migrations
php artisan migrate:fresh      # Supprime tout et rejoue les migrations (⚠️ perte de données)
php artisan migrate:fresh --seed   # + lance les seeders
php artisan migrate:status     # État des migrations
php artisan tinker             # Console PHP interactive (tester du code Laravel à la volée)
php artisan route:list         # Liste toutes les routes définies
php artisan make:model Foo -mfsc   # Génère Model + Migration + Factory + Seeder + Controller
php artisan test               # Lance la suite de tests
php artisan config:clear       # Vide le cache de configuration
php artisan optimize:clear     # Vide tous les caches (config, routes, vues, events)
composer dump-autoload         # Recharge l'autoloader après ajout de classes
```

### Frontend (Vite + Tailwind + Alpine)

```bash
npm install                    # Installe les dépendances JS
npm run dev                    # Compile en mode dev avec hot reload (à laisser tourner)
npm run build                  # Compile pour la production (assets minifiés dans public/build/)
```

### Git (workflow d'équipe)

```bash
git checkout -b ma-feature     # Crée une branche depuis l'actuelle
git status                     # Voir les fichiers modifiés
git add <fichier>              # Stage un fichier précis (éviter `git add .`)
git commit -m "feat: ..."      # Commit avec convention (feat / fix / docs / refactor / test)
git push origin ma-feature     # Push la branche sur GitHub
git pull --rebase origin main  # Récupère les changements de main proprement
```

## Équipe

| Membre 
|Alioune Badara Barry
| Serigne Abdoul Aziz Ndiaye
| Souleymane Sirima Mbodj 
| Mohamed Moctar Niang

