<?php

namespace App\Filament\Clusters\Feedbacks\Widgets\Concerns;

use App\Enums\UserRole;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Builder;

trait FeedbackScopes
{
    protected function applyOrganizationScope(?string $organizationId) : Builder
    {
        $query = Feedback::query();

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if(request()->user()->role === UserRole::ADMIN){
            $query->where('organization_id', request()->user()->organization_id);
        }

        $query = match($this->filter ?? null) {
            'internal' => $query->whereHas('category', function($q) {
                $q->where('service_type', 'internal');
            }),
            'external' => $query->whereHas('category', function ($q) {
                $q->where('service_type', 'external');
            }),
            default => $query,
        };

        return $this->applyPageFilters($query);
    }

    protected function applyPageFilters(Builder $query): Builder
    {
        $filters = property_exists($this, 'filters') ? ($this->filters ?? []) : [];

        return $query
            ->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId));
    }

    protected function applyPageFiltersToResponseQuery(Builder $query): Builder
    {
        $filters = property_exists($this, 'filters') ? ($this->filters ?? []) : [];

        if (empty($filters['startDate']) && empty($filters['endDate']) && empty($filters['category_id'])) {
            return $query;
        }

        return $query->whereHas('feedback', function (Builder $q) use ($filters) {
            $q->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
                ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId));
        });
    }
}
