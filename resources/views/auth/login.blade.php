@extends('layouts.guest')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">Connexion</h2>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-semibold mb-2">
                    Adresse Email
                </label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="votre@email.com">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    Mot de passe
                </label>
                <input type="password" name="password" id="password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="mr-2" {{ old('remember') ? 'checked' : '' }}>
                    <span class="text-gray-700">Se souvenir de moi</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                Se connecter
            </button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-gray-600">
                Pas encore inscrit? 
                <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-semibold">
                    Créer un compte
                </a>
            </p>
        </div>
    </div>
@endsection
