<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\Feedback as EnumsFeedback;
use App\Filament\Clusters\Feedbacks\Widgets\Concerns\FeedbackScopes;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class GenderChart extends ChartWidget
{
    use FeedbackScopes;

    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected static string $color = 'primary';

    protected static ?string $heading = 'Sex';

    protected static ?string $maxHeight = '400px';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $query = $this->applyOrganizationScope($this->selectedOrganizationId);

        $data = $query
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        $data['not_specified'] = ($data[''] ?? 0) + ($data['other'] ?? 0);
        unset($data[''], $data['other']);

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Male', 'Female', 'Did Not Specified'],
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            (function() {
                const genderLabelsPlugin = {
                    id: 'genderLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx, data } = chart;
                        const meta = chart.getDatasetMeta(0);
                        if (!meta || !meta.data || !meta.data.length) return;

                        meta.data.forEach((arc, i) => {
                            if (!arc || arc.x == null) return;

                            const value = data.datasets[0].data[i];
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);

                            if (percentage < 5) return;

                            const label = data.labels[i];
                            const angle = (arc.startAngle + arc.endAngle) / 2;
                            const radius = (arc.innerRadius + arc.outerRadius) / 2;
                            const x = arc.x + Math.cos(angle) * radius;
                            const y = arc.y + Math.sin(angle) * radius;
                            ctx.save();
                            ctx.fillStyle = '#000000';
                            ctx.font = '12px Urbanist, sans-serif';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(data.labels[i], x, y - 7);
                            ctx.fillText('('+percentage + '%)', x, y + 7);
                            ctx.restore();
                        });
                    }
                };

                if (window.filamentChartJsPlugins) {
                    const alreadyRegistered = window.filamentChartJsPlugins.some(p => p.id === 'genderLabels');
                    if (!alreadyRegistered) window.filamentChartJsPlugins.push(genderLabelsPlugin);
                } else {
                    window.filamentChartJsPlugins = [genderLabelsPlugin];
                }

                return {
                    scales: {
                        y: {
                            display: false,
                        },
                        x : {
                            display: false,
                        },
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart',
                    },
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                    },
                };
            })()
        JS);
    }

    protected function getFilters(): ?array
    {
        return [
            'overall' => 'Overall',
            ] + EnumsFeedback::serviceTypesLabel();
    }
}
