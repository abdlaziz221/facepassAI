<x-layouts.app title="Test Alpine.js — FacePassAI">
    <div x-data="{ message: 'Alpine.js fonctionne 🎉', open: false, count: 0, search: '', employes: ['Aminata Diop', 'Moussa Ndiaye', 'Fatou Sall', 'Ibrahima Ba', 'Aissatou Fall'] }"
         style="max-width: 800px; margin: 0 auto;">

        <h1 x-text="message" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem;"></h1>

        {{-- 1. Toggle panneau --}}
        <section style="margin-bottom: 2rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h2 style="font-weight: 600; margin-bottom: 0.5rem;">1. Toggle (x-show)</h2>
            <button @click="open = !open"
                    style="background-color: #2563eb; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer;">
                <span x-text="open ? 'Masquer' : 'Afficher'"></span> le panneau
            </button>
            <div x-show="open" x-transition
                 style="margin-top: 1rem; padding: 1rem; background-color: #f3f4f6; border-radius: 0.375rem;">
                Contenu affiché uniquement quand <code>open === true</code>.
            </div>
        </section>

        {{-- 2. Compteur --}}
        <section style="margin-bottom: 2rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h2 style="font-weight: 600; margin-bottom: 0.5rem;">2. Compteur (x-text + @click)</h2>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button @click="count--" style="padding: 0.25rem 0.75rem; background-color: #ef4444; color: white; border-radius: 0.375rem; border: none; cursor: pointer;">−</button>
                <span x-text="count" style="font-size: 1.5rem; font-weight: bold; min-width: 2rem; text-align: center;"></span>
                <button @click="count++" style="padding: 0.25rem 0.75rem; background-color: #22c55e; color: white; border-radius: 0.375rem; border: none; cursor: pointer;">+</button>
            </div>
        </section>

        {{-- 3. Liaison bidirectionnelle (x-model) --}}
        <section style="margin-bottom: 2rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h2 style="font-weight: 600; margin-bottom: 0.5rem;">3. Liaison de formulaire (x-model)</h2>
            <input type="text" x-model="message"
                   placeholder="Tape ici, le titre se met à jour en temps réel"
                   style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </section>

        {{-- 4. Recherche dynamique (cas réel FacePassAI) --}}
        <section style="margin-bottom: 2rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <h2 style="font-weight: 600; margin-bottom: 0.5rem;">4. Recherche dynamique d'employés</h2>
            <input type="text" x-model="search"
                   placeholder="Rechercher un employé..."
                   style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; margin-bottom: 0.5rem;">
            <ul style="list-style: none; padding: 0;">
                <template x-for="employe in employes.filter(e => e.toLowerCase().includes(search.toLowerCase()))" :key="employe">
                    <li x-text="employe" style="padding: 0.5rem; border-bottom: 1px solid #f3f4f6;"></li>
                </template>
                <li x-show="employes.filter(e => e.toLowerCase().includes(search.toLowerCase())).length === 0"
                    style="padding: 0.5rem; color: #9ca3af; font-style: italic;">
                    Aucun résultat
                </li>
            </ul>
        </section>

    </div>
</x-layouts.app>
