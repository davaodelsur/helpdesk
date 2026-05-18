<?php

namespace App\Filament\Clusters\Feedbacks\Pages;

use App\Enums\Feedback;
use App\Enums\SqdQuestion;
use App\Enums\UserRole;
use App\Filament\Actions\Header\SelectAction;
use App\Filament\Clusters\Feedbacks;
use App\Filament\Clusters\Feedbacks\Widgets\AgeChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\CustomerTypeWidget;
use App\Filament\Clusters\Feedbacks\Widgets\GenderChart;
use App\Filament\Clusters\Feedbacks\Widgets\OverviewStatsWidget;
use App\Filament\Clusters\Feedbacks\Widgets\RegionChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\SQD0Widget;
use App\Models\Response;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class Dashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string $view = 'filament.panels.feedback.pages.dashboard';

    protected static ?string $cluster = Feedbacks::class;

    protected static ?string $navigationIcon = 'gmdi-dashboard-o';

    #[Url(history: true)]
    public ?string $selectedOrganizationId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function tableQuery(): Builder
    {
        $query = Response::query();

        if(!is_null($this->selectedOrganizationId) && request()->user()->role != UserRole::ADMIN){
            $query->whereHas('feedback', function ($q) {
                $q->where('organization_id', $this->selectedOrganizationId);
            });
        }else{
            $query = Response::query();
        }

        if(request()->user()->role === UserRole::ADMIN) {
            $query->whereHas('feedback', function ($q) {
                $q->where('organization_id', request()->user()->organization_id);
            });
        }

        $query = $query->select('question')
            ->selectRaw('COUNT(*) as total_responses')
            ->selectRaw('SUM(CASE WHEN answer = 0 THEN 1 ELSE 0 END) as ans_0_count')
            ->selectRaw('SUM(CASE WHEN answer = 1 THEN 1 ELSE 0 END) as ans_1_count')
            ->selectRaw('SUM(CASE WHEN answer = 2 THEN 1 ELSE 0 END) as ans_2_count')
            ->selectRaw('SUM(CASE WHEN answer = 3 THEN 1 ELSE 0 END) as ans_3_count')
            ->selectRaw('SUM(CASE WHEN answer = 4 THEN 1 ELSE 0 END) as ans_4_count')
            ->selectRaw('SUM(CASE WHEN answer = 5 THEN 1 ELSE 0 END) as ans_5_count')
            ->selectRaw('ROUND((SUM(CASE WHEN answer IN (4, 5) THEN 1 ELSE 0 END) * 100.0) / (NULLIF(COUNT(*), 0) - SUM(CASE WHEN answer = 0 THEN 1 ELSE 0 END)), 2) AS overall_percentage')
            ->where('question', 'not like', 'CC%')
            ->where('question', '!=', 'SQD0')
            ->groupBy('question')
            ->orderBy('question', 'asc');

        return $query;
    }

    public function table(Table $table): Table
    {
        $answerColumns = [
            'ans_5_count' => 'Strongly Agree',
            'ans_4_count' => 'Agree',
            'ans_3_count' => 'Neither Agree nor Disagree',
            'ans_2_count' => 'Disagree',
            'ans_1_count' => 'Strongly Disagree',
            'ans_0_count' => 'No Response',
        ];


        return $table
            ->query($this->tableQuery())
            ->striped()
            ->paginated(false)
            ->filters([
                    SelectFilter::make('service_type')
                        ->label('Service Type')
                        ->options(Feedback::serviceTypesLabel())
                        ->query(function ($query, $data) {
                            if(is_null($data['value'])){
                                return;
                            }

                            $query->whereHas('feedback', function ($q) use ($data) {
                                $q->whereHas('category', function ($q2) use ($data) {
                                    $q2->where('service_type', $data);
                                });
                            });
                        }),
            ])
            ->columns(
                array_merge([
                    TextColumn::make('question')
                        ->label('Service Quality Dimensions')
                        ->width('100px')
                        ->extraHeaderAttributes(['class' => 'whitespace-normal text-center'])
                        ->formatStateUsing(fn ($state) => SqdQuestion::tryFrom($state)?->getStandardizedName() ?? $state)
                        ->width('15%')
                        ->wrapHeader(),
                ],
                array_map(fn ($column, $label) => TextColumn::make($column)
                    ->label($label)
                    ->extraHeaderAttributes(['class' => 'whitespace-normal text-center'])
                    ->alignCenter()
                    ->width('14%')
                    ->wrapHeader()
                    ->summarize(Sum::make()->label('')->extraAttributes(['class' => 'font-bold [&_span]:!text-black'])),
                    array_keys($answerColumns), $answerColumns),
                [
                    TextColumn::make('total_responses')
                        ->label('Total Responses')
                        ->extraHeaderAttributes(['class' => 'whitespace-normal text-center'])
                        ->alignCenter()
                        ->width('15%')
                        ->summarize(Sum::make()->label('')->extraAttributes(['class' => 'font-bold [&_span]:!text-black'])),
                    TextColumn::make('overall_percentage')
                        ->label('Overall')
                        ->extraHeaderAttributes(['class' => 'whitespace-normal text-center'])
                        ->alignCenter()
                        ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) . '%' : 'N/A')
                        ->width('15%')
                        ->summarize(Average::make()
                                        ->label('')
                                        ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) . '%' : 'N/A')
                                        ->extraAttributes(['class' => 'font-bold [&_span]:!text-black']))
                        ->wrapHeader(),
                ]),

            );
    }

    public function getTableRecordKey(mixed $record): string
    {
        return $record->question;
    }

    public function getBreadcrumbs(): array
    {
        return array_merge(
            parent::getBreadcrumbs(),
            ['Dashboard'],
        );
    }

    public function getHeaderActions(): array
    {
        return [
            SelectAction::make(),
            Action::make('report')
                ->label('Generate Report')
                ->icon('gmdi-file-download'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            OverviewStatsWidget::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
            SQD0Widget::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
            ];
    }

    public function getFooterWidgets(): array
    {
        return [
            GenderChart::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
            CustomerTypeWidget::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
            AgeChartWidget::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
            RegionChartWidget::make([
                'selectedOrganizationId' => $this->selectedOrganizationId,
            ]),
        ];
    }


}
