<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller du projet.
 *
 * Inclut les traits AuthorizesRequests et ValidatesRequests qui
 * permettent d'utiliser $this->authorize(), $this->authorizeResource()
 * et $this->validate() dans les controllers enfants.
 *
 * Note : Laravel 11+ a retiré ces traits du base controller par défaut,
 * il faut les rajouter manuellement quand on les utilise.
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
