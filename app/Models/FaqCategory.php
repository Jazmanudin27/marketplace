<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'subtitle',
        'icon',
        'color',
        'color_rgb',
        'read_time',
        'workflow_title',
        'sort_order',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class)->orderBy('sort_order');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(FaqItem::class)
            ->where('type', 'workflow')
            ->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(FaqItem::class)
            ->where('type', 'faq')
            ->orderBy('sort_order');
    }
}
