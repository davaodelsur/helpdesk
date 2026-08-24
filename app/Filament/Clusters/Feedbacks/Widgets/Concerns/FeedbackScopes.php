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

        return $this->applyPageFilters($query);
    }

    protected function applyPageFilters(Builder $query): Builder
    {
        $filters = property_exists($this, 'filters') ? ($this->filters ?? []) : [];

        return $query
            ->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($filters['service_type'] ?? null, fn (Builder $q, $serviceTypeId) => $q->whereHas('category', fn (Builder $q) => $q->where('service_type', $serviceTypeId)));
    }

    protected function applyPageFiltersToResponseQuery(Builder $query): Builder
    {
        $filters = property_exists($this, 'filters') ? ($this->filters ?? []) : [];

        return $query->whereHas('feedback', function (Builder $q) use ($filters) {
            $q->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
                ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId))
                ->when($filters['service_type'] ?? null, fn (Builder $q, $serviceTypeId) => $q->whereHas('category', fn (Builder $q) => $q->where('service_type', $serviceTypeId)));
        });
    }
}
