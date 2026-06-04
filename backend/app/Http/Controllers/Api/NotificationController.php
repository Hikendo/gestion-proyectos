<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->customNotifications()
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($notifications);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->customNotifications()->findOrFail($id);
        return response()->json(['data' => $notification]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $request->validate(['notification_id' => ['required', 'integer']]);

        $notification = $request->user()->customNotifications()->findOrFail($request->input('notification_id'));
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->customNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas.']);
    }

    /**
     * Endpoint para calendarizar un push en diferido mediante el Scheduler y Redis.
     */
    public function schedule(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
            'delay_minutes' => ['required', 'integer', 'min:1']
        ]);

        $notification = Notification::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'type' => 'scheduled_event',
            'status' => 'pending'
        ]);

        // Despacho con retraso (Delayed Dispatching) integrado con Horizon y Redis
        SendPushNotificationJob::dispatch($notification)
            ->delay(now()->addMinutes((int) $request->input('delay_minutes')));

        return response()->json([
            'message' => "Notificación encolada con éxito. Se enviará en {$request->input('delay_minutes')} minutos."
        ]);
    }
}
