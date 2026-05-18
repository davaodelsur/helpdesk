<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Widgets\Concerns\FeedbackScopes;
use App\Models\Feedback;
use Livewire\Attributes\Reactive;

class CustomerTypeWidget extends GenderChart
{
    use FeedbackScopes;

    protected static ?string $heading = 'Customer Type';

    protected function getData(): array
    {
        $query = $this->applyOrganizationScope($this->selectedOrganizationId);

        $data = $query
            ->selectRaw('client_type, COUNT(*) as count')
            ->groupBy('client_type')
            ->pluck('count', 'client_type')
            ->toArray();

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                    ],
                ],
            ],
            'labels'=> ['Citizen', 'Business', 'Government'],
        ];
    }
}
