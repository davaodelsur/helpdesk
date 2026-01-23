<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Response extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'feedback_id',
        'question_type',
        'question',
        'answer',
        'deleted_at',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
