<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_date'     => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
            if (empty($ticket->tracking_token)) {
                $ticket->tracking_token = Str::random(32);
            }
        });

        static::saving(function (Ticket $ticket) {
            foreach (['scheduled_at', 'due_date'] as $attr) {
                if ($ticket->{$attr} !== null) {
                    try {
                        $parsed = Carbon::parse($ticket->{$attr});
                        if ($parsed->year < 1900) {
                            $ticket->{$attr} = null;
                        }
                    } catch (\Throwable) {
                        $ticket->{$attr} = null;
                    }
                }
            }
        });
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    /**
     * من أنشأ التذكرة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TicketTask::class, 'ticket_id')->orderBy('sort_order');
    }

    public function evaluation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TicketEvaluation::class);
    }

    public function ticketAttachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }
}
