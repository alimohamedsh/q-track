<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequiredItem extends Model
{
    protected $guarded = [];

    /** قيمة خاصة في الفورم تعني "أخرى" (اسم مخصص) */
    public const OTHER_TEMPLATE_KEY = 0;

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requiredItemTemplate(): BelongsTo
    {
        return $this->belongsTo(RequiredItemTemplate::class);
    }

    /**
     * في الفورم نعرض 0 عند عدم وجود قالب (أخرى).
     */
    public function getRequiredItemTemplateIdAttribute($value)
    {
        return $value === null ? self::OTHER_TEMPLATE_KEY : (int) $value;
    }

    /**
     * تحويل 0 (أخرى) إلى null قبل الحفظ.
     */
    public function setRequiredItemTemplateIdAttribute($value): void
    {
        $v = $value === '' ? null : $value;
        $this->attributes['required_item_template_id'] = ($v === null || (int) $v === self::OTHER_TEMPLATE_KEY) ? null : $v;
    }

    /**
     * عند الحفظ: إن كان قد اختير قالب، نخزّن نسخة من اسم القالب في name لعرض لاحق ("الفني كان واخد إيه").
     */
    protected static function booted(): void
    {
        static::saving(function (TicketRequiredItem $item) {
            if ($item->required_item_template_id) {
                $template = RequiredItemTemplate::find($item->required_item_template_id);
                if ($template) {
                    $item->name = $template->name;
                }
            }
        });
    }

    /**
     * الاسم المعروض (من القالب أو النص المخصص).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->requiredItemTemplate?->name ?? '';
    }
}
