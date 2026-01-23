<?php

namespace App\Models;

use App\Enums\Feedback as EnumsFeedback;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasUlids, SoftDeletes;
    protected $fillable = [
        'feedbacks',
        'category_id',
        'request_id',
        'organization_id',
        'user_id',
        'email',
        'client_type',
        'gender',
        'age',
        'residence',
        'expectation',
        'strength',
        'improvement',
        'deleted_at',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (is_null($model->control_no)) {
                do{
                    $generatedCode = fake()->bothify('????####');
                } while (static::where('control_no', $generatedCode)->exists());

                $model->control_no = $generatedCode;
            }
        });

    }

    protected function casts(): array
    {
        return [
            'feedbacks' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    protected function sqdAverage(): Attribute
    {
        return new Attribute(
            get: fn () => $this->responses
                ->filter(fn ($response) => $response->question_type === 'Service Quality Dimension')
                ->avg('answer'),
        );
    }

    public function getAnswer($question)
    {
        return $this->responses->where('question', $question)->first()->answer ?? null;
    }
}
