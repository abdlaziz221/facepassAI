<section x-data="{ confirmingDelete: false }">

    {{-- Bouton "Supprimer mon compte" --}}
    <button @click="confirmingDelete = true"
            type="button"
            style="display: inline-flex; align-items: center; gap: 8px;
                   padding: 11px 20px; border-radius: 10px;
                   background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                   color: white; font-weight: 600; font-size: 14px;
                   border: none; cursor: pointer;
                   box-shadow: 0 8px 24px rgba(239, 68, 68, 0.25);
                   transition: transform .15s, box-shadow .2s;"
            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 12px 32px rgba(239, 68, 68, 0.4)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(239, 68, 68, 0.25)'">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
        </svg>
        Supprimer mon compte
    </button>

    {{-- Modal de confirmation --}}
    <div x-show="confirmingDelete"
         x-transition.opacity
         @keydown.escape.window="confirmingDelete = false"
         style="position: fixed; inset: 0; display: flex; align-items: center; justify-content: center;
                background: rgba(5, 6, 9, 0.85); backdrop-filter: blur(8px); z-index: 100; padding: 24px;"
         @click.self="confirmingDelete = false"
         style="display: none;">

        <div class="glass" style="max-width: 480px; width: 100%; padding: 32px;
                                  border-radius: 16px; border-color: rgba(239, 68, 68, 0.3);">

            <h3 style="font-size: 20px; font-weight: 700; color: white; margin: 0 0 12px;">
                Êtes-vous absolument sûr·e ?
            </h3>

            <p style="font-size: 14px; color: #9ca3af; margin: 0 0 24px; line-height: 1.6;">
                Cette action est <strong style="color: #fca5a5;">définitive</strong>.
                Toutes les données associées à votre compte seront supprimées et
                ne pourront pas être récupérées.
                <br><br>
                Pour confirmer, saisissez votre mot de passe.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}"
                  style="display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('delete')

                <input type="password" name="password" id="password"
                       placeholder="Votre mot de passe"
                       required
                       class="input-dark" />
                @error('password', 'userDeletion')
                    <p class="error-text">{{ $message }}</p>
                @enderror

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                    <button type="button" @click="confirmingDelete = false" class="btn-secondary">
                        Annuler
                    </button>
                    <button type="submit"
                            style="display: inline-flex; align-items: center; gap: 8px;
                                   padding: 11px 20px; border-radius: 10px;
                                   background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                                   color: white; font-weight: 600; font-size: 14px;
                                   border: none; cursor: pointer;
                                   box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);">
                        Oui, supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
