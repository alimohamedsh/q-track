<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAttachment extends Model
{
    protected $guarded = [];

    /**
     * العلاقة مع Visit
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
