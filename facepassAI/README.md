# facepassAI — Plateforme de Gestion des Présences (ESP Dakar)

Application Laravel 12 de gestion des présences avec reconnaissance faciale.

## Stack technique

- PHP 8.2+
- Laravel 12
- MySQL (production) / SQLite (développement)
- Tailwind CSS + Alpine.js (à venir, Sprint 0)
- Microservice Python FastAPI pour la reconnaissance faciale (Sprint 3)

## Prérequis

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 18 et npm
- MySQL 8 (ou SQLite pour le dev local)

## Installation

```bash
git clone https://github.com/abdlaziz221/facepassAI.git
cd facepassAI
composer install
copy .env.example .env       # Linux/Mac : cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

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

## Équipe

- Sprint planning : voir `Backlog_Plateforme_Presence_Laravel.xlsx` et le tableau Trello.
- Diagramme UML : voir `projet_uml.pdf`.

## Commandes utiles

```bash
php artisan serve              # Lancer le serveur local
php artisan migrate            # Appliquer les migrations
php artisan tinker             # Console PHP interactive
php artisan test               # Lancer les tests
composer dump-autoload         # Recharger l'autoloader après ajout de classes
```
