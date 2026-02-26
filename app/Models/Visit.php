<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    protected $guarded = [];

    protected $casts = [
        'check_in_at'  => 'datetime',
        'arrived_at'   => 'datetime',
        'check_out_at' => 'datetime',
    ];

    /**
     * العلاقة مع Ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * العلاقة مع User (الفني)
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع VisitAttachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(VisitAttachment::class);
    }

    /**
     * علاقة نتائج المهام
     */
    public function taskResults(): HasMany
    {
        return $this->hasMany(VisitTaskResult::class);
    }

    public function failureReason(): BelongsTo
    {
        return $this->belongsTo(VisitFailureReason::class, 'failure_reason_id');
    }
}
