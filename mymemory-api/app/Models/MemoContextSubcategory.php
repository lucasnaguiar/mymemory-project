<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoContextSubcategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MemoContextCategory::class, 'category_id');
    }
}
