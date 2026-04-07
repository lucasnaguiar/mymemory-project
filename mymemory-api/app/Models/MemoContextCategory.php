<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoContextCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'scope',
        'group_id',
        'created_by_user_id',
        'media_type_filter',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(MemoContextSubcategory::class, 'category_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MemoContextField::class, 'category_id');
    }
}
