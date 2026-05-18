<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\Feedback as FeedbackEnum;
use App\Enums\UserRole;
use App\Models\Feedback;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\Reactive;

class RegionChartWidget extends TableWidget
{
    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Region';

    public function table(Table $table): Table
    {
        $organizationId = (request()->user()->role == UserRole::ADMIN)
            ? request()->user()->organization_id
            : $this->selectedOrganizationId;

        return $table
            ->query(
                Feedback::query()
                    ->when($organizationId, function ($query) use ($organizationId) {
                        $query->where('organization_id', $organizationId);
                    })
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
            ])
            ->filters([
                SelectFilter::make('category.service_type')
                    ->label('Service Type')
                    ->options(FeedbackEnum::serviceTypesLabel())
                    ->query(function ($query, $data) {
                        if(is_null($data['value'])){
                            return $query;
                        };

                        return $query->whereHas('category', function ($q) use ($data) {
                            $q->where('service_type', $data);
                        });
                    }),
            ]);
    }

    public function getTableRecordKey(mixed $record): string
    {
        return $record->region;
    }
}
