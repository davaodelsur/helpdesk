<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\Feedback as EnumsFeedback;
use App\Filament\Clusters\Feedbacks\Widgets\Concerns\FeedbackScopes;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Livewire\Attributes\Reactive;

class AgeChartWidget extends ChartWidget
{
    use FeedbackScopes;
    use InteractsWithPageFilters;

    #[Reactive]
    public ?string $selectedOrganizationId = null;

    #[Reactive]
    public ?string $selectedCategoryId = null;

    protected static ?string $heading = 'Age Distribution';

    protected static string $color = 'info';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $query = $this->applyOrganizationScope($this->selectedOrganizationId);

        $data=$query->selectRaw('
            CASE
                WHEN age < 20 THEN "0-19"
                WHEN age BETWEEN 20 AND 34 THEN "20-34"
                WHEN age BETWEEN 35 AND 49 THEN "35-49"
                WHEN age BETWEEN 50 AND 64 THEN "50-64"
                WHEN age >= 65 THEN "65+"
                ELSE "Did not specify"
            END as age_group,
            COUNT(*) as count
        ')
        ->groupBy('age_group')
        ->pluck('count', 'age_group')
        ->toArray();

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => 'rgb(54, 162, 235)',
                ],
            ],
            'labels'=> ['0-19', '20-34', '35-49', '50-64', '65+', 'Did not specify'],
        ];
    }
}
