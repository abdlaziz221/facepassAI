<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestion des notifications utilisateur (Sprint 4 carte 9, US-050).
 */
class NotificationController extends Controller
{
    /**
     * Marque toutes les notifications de l'utilisateur connecté comme lues.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }

    /**
     * Marque une notification spécifique comme lue.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * API JSON : nombre de notifications non lues + 5 dernières.
     * Utilisé par le widget cloche pour rafraîchissement asynchrone.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'latest'       => $user->notifications()->limit(5)->get()->map(fn ($n) => [
                'id'      => $n->id,
                'data'    => $n->data,
                'read_at' => $n->read_at,
                'created' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
