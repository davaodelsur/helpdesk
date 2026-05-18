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

        $query = match($this->filter) {
            'internal' => $query->whereHas('category', function($q) {
                $q->where('service_type', 'internal');
            }),
            'external' => $query->whereHas('category', function ($q) {
                $q->where('service_type', 'external');
            }),
            default => $query,
        };

        return $query;
    }
}
