<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoContextField extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'field_type',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MemoContextCategory::class, 'category_id');
    }
}
