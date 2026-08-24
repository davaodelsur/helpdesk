<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Widgets\Concerns\FeedbackScopes;
use App\Models\Feedback;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\Reactive;

class RegionChartWidget extends TableWidget
{
    use FeedbackScopes;
    use InteractsWithPageFilters;

    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Region';

    public function updatedFilters(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $organizationId = (request()->user()->role == UserRole::ADMIN)
            ? request()->user()->organization_id
            : $this->selectedOrganizationId;

        return $table
            ->query(
                $this->applyPageFilters(
                    Feedback::query()
                        ->when($organizationId, function ($query) use ($organizationId) {
                            $query->where('organization_id', $organizationId);
                        })
                )
                    ->selectRaw("SUBSTRING_INDEX(residence, ',', 1) as region, COUNT(*) as count")
                    ->groupBy('region')
                    ->orderBy('count', 'desc')
            )
            ->columns([
                TextColumn::make('region')
                    ->label('Region')
                    ->sortable(),
                TextColumn::make('count')
                    ->label('Number of Feedbacks')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')),
            ]);
    }

    public function getTableRecordKey(mixed $record): string
    {
        return $record->region;
    }
}
