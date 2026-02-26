<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequiredItemTemplate extends Model
{
    protected $guarded = [];

    public function ticketRequiredItems(): HasMany
    {
        return $this->hasMany(TicketRequiredItem::class, 'required_item_template_id');
    }
}
