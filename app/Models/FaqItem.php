<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqItem extends Model
{
    protected $table = 'faq_items';

    protected $fillable = [
        'faq_category_id',
        'type',
        'title',
        'content',
        'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
