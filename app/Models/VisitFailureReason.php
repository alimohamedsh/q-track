<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitFailureReason extends Model
{
    protected $guarded = [];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'failure_reason_id');
    }
}
