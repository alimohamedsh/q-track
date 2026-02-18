<?php

namespace App\Notifications;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class VisitCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Visit $visit
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $visit = $this->visit->load('ticket', 'technician');
        return [
            'visit_id'        => $this->visit->id,
            'ticket_id'       => $visit->ticket_id,
            'ticket_number'   => $visit->ticket->ticket_number ?? '',
            'technician_name' => $visit->technician?->name ?? 'فني',
            'status'          => $this->visit->status,
            'message'         => "انتهت زيارة التذكرة {$visit->ticket->ticket_number} بواسطة {$visit->technician?->name}",
        ];
    }
}
