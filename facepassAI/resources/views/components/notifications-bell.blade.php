@auth
@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $notifications = auth()->user()->notifications()->limit(5)->get();
@endphp

<div x-data="{ open: false }" style="position: relative;">

    {{-- Bouton cloche --}}
    <button type="button"
            @click="open = !open"
            style="position: relative; padding: 8px; background: transparent; border: none; cursor: pointer; color: #cbd5e1;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
        </svg>
        @if ($unreadCount > 0)
            <span style="position: absolute; top: 2px; right: 2px; min-width: 18px; height: 18px; background: #ef4444; color: white; font-size: 10px; font-weight: 700; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px;">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-cloak
         @click.outside="open = false"
         style="position: absolute; right: 0; top: 100%; margin-top: 8px; width: 360px; background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); z-index: 50; max-height: 480px; overflow-y: auto;">

        <div style="padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; color: white; font-size: 14px;">Notifications</span>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: #67e8f9; font-size: 12px; cursor: pointer;">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <p style="padding: 24px 16px; text-align: center; color: #6b7280; font-size: 13px;">
                Aucune notification.
            </p>
        @else
            @foreach ($notifications as $notif)
                <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); {{ $notif->read_at ? 'opacity: 0.6;' : '' }}">
                    <p style="margin: 0; color: white; font-size: 13px; line-height: 1.4;">
                        {{ $notif->data['message'] ?? 'Notification' }}
                    </p>
                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 11px;">
                        {{ $notif->created_at->diffForHumans() }}
                    </p>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endauth
