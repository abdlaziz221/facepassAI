@extends('layouts.app')

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 bg-white border-b border-gray-200">
            <h1 class="text-3xl font-semibold text-gray-900">
                Bienvenue, {{ Auth::user()->name }}!
            </h1>
        </div>

        <div class="px-6 py-4">
            <p class="text-gray-600 text-lg">
                Vous êtes connecté au tableau de bord.
            </p>
            
            <div class="mt-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions disponibles:</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-700">
                    <li>Consultez vos paramètres de profil</li>
                    <li>Gérez vos données personnelles</li>
                    <li>Déconnectez-vous via le bouton "Déconnexion" en haut à droite</li>
                </ul>
            </div>

            <div class="mt-8">
                <p class="text-sm text-gray-500">
                    Pour vous déconnecter, cliquez sur le bouton <strong>"Déconnexion"</strong> dans la barre de navigation en haut.
                </p>
            </div>
        </div>
    </div>
@endsection
