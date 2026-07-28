<?php

namespace App\Filament\Clusters\Feedbacks\Widgets;

use App\Enums\SqdQuestion;
use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Widgets\Concerns\FeedbackScopes;
use App\Models\Response;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

class SQD0Widget extends TableWidget
{
    use FeedbackScopes;
    use InteractsWithPageFilters;

    #[Reactive]
    public ?string $selectedOrganizationId = null;

    protected static ?string $heading = '';

    protected int | string | array $columnSpan = 2;

    public function updatedFilters(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $query = Response::query();

        $organizationId = (request()->user()->role == UserRole::ADMIN)
            ? request()->user()->organization_id
            : $this->selectedOrganizationId;
        return $table
            ->query(
                $this->applyPageFiltersToResponseQuery(
                    $query
                        ->select('question')
                        ->selectRaw('COUNT(*) as total_responses')
                        ->selectRaw('SUM(CASE WHEN answer = 0 THEN 1 ELSE 0 END) as na_count')
                        ->selectRaw('SUM(CASE WHEN answer = 1 THEN 1 ELSE 0 END) as strongly_disagree_count')
                        ->selectRaw('SUM(CASE WHEN answer = 2 THEN 1 ELSE 0 END) as disagree_count')
                        ->selectRaw('SUM(CASE WHEN answer = 3 THEN 1 ELSE 0 END) as neither_agree_nor_disagree_count')
                        ->selectRaw('SUM(CASE WHEN answer = 4 THEN 1 ELSE 0 END) as agree_count')
                        ->selectRaw('SUM(CASE WHEN answer = 5 THEN 1 ELSE 0 END) as strongly_agree_count')
                        ->selectRaw('ROUND((SUM(CASE WHEN answer IN (4, 5) THEN 1 ELSE 0 END) * 100.0) / (NULLIF(COUNT(*), 0) - SUM(CASE WHEN answer = 0 THEN 1 ELSE 0 END)), 2) AS overall_percentage')
                        ->when($organizationId, function ($query) use ($organizationId) {
                            $query->whereHas('feedback', function ($q) use ($organizationId) {
                                $q->where('organization_id', $organizationId);
                            });
                        })
                        ->where('question', '=', SqdQuestion::SQD0->value)
                        ->groupBy('question')
                        ->orderBy('total_responses', 'desc')
                )

            )
            ->columns([
                TextColumn::make('question')
                    ->label('')
                    ->alignCenter()
                    ->width('20%'),
                TextColumn::make('strongly_agree_count')
                    ->label('Strongly Agree')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('agree_count')
                    ->label('Agree')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('neither_agree_nor_disagree_count')
                    ->label('Neither Agree nor Disagree')
                    ->alignCenter()
                    ->wrapHeader()
                    ->width('10%'),
                TextColumn::make('disagree_count')
                    ->label('Disagree')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('strongly_disagree_count')
                    ->label('Strongly Disagree')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('na_count')
                    ->label('N/A')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('total_responses')
                    ->label('Total Responses')
                    ->alignCenter()
                    ->width('10%'),
                TextColumn::make('overall_percentage')
                    ->label('Overall')
                    ->formatStateUsing(fn($state) => is_null($state) ? '0%' : $state . '%')
                    ->alignCenter()
                    ->width('10%'),
            ])
            ->paginated(false);
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->question;
    }
}
