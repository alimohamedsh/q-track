<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitTaskResult extends Model
{
    protected $guarded = [];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function ticketTask(): BelongsTo
    {
        return $this->belongsTo(TicketTask::class, 'ticket_task_id');
    }
}
