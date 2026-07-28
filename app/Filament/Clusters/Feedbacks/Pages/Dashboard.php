<?php

namespace App\Filament\Clusters\Feedbacks\Pages;

use App\Enums\Feedback;
use App\Enums\SqdQuestion;
use App\Enums\UserRole;
use App\Filament\Actions\Header\FilterAction;
use App\Filament\Actions\Header\SelectAction;
use App\Filament\Clusters\Feedbacks;
use App\Filament\Clusters\Feedbacks\Widgets\AgeChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\CustomerTypeWidget;
use App\Filament\Clusters\Feedbacks\Widgets\GenderChart;
use App\Filament\Clusters\Feedbacks\Widgets\OverviewStatsWidget;
use App\Filament\Clusters\Feedbacks\Widgets\RegionChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\SQD0Widget;
use App\Models\Category;
use App\Models\Response;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class Dashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'filament.panels.feedback.pages.dashboard';

    protected static ?string $cluster = Feedbacks::class;

    protected static ?string $navigationIcon = 'gmdi-dashboard-o';

    #[Url(history: true)]
    public ?string $selectedOrganizationId = null;

    #[Url]
    public ?array $filters = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->filtersForm->fill($this->filters);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm()),
            'filtersForm' => $this->filtersForm($this->makeForm()),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label('Start date')
                    ->native(false)
                    ->maxDate(fn (callable $get) => $get('endDate') ?: now()),
                DatePicker::make('endDate')
                    ->label('End date')
                    ->native(false)
                    ->minDate(fn (callable $get) => $get('startDate'))
                    ->maxDate(now()),
                Select::make('category_id')
                    ->label('Category')
                    ->placeholder('All categories')
                    ->searchable()
                    ->options(fn (): array => $this->getCategoryOptions()),
            ])
            ->statePath('filters')
            ->live();
    }

    public function updated(string $name): void
    {
        if ($name !== 'filters' && ! str_starts_with($name, 'filters.')) {
            return;
        }

        if (! collect($this->filters ?? [])->contains(fn ($value) => filled($value))) {
            $this->filters = null;
        }

        $this->resetTable();
    }

    public function resetDashboardFilters(): void
    {
        $this->filters = null;
        $this->filtersForm->fill();
        $this->resetTable();
    }

    public function tableQuery(): Builder
    {
        $query = Response::query();

        $organizationId = (Auth::user()->role === UserRole::ADMIN)
            ? Auth::user()->organization_id
            : $this->selectedOrganizationId;

        $filters = $this->filters ?? [];

        $query->when(
            $organizationId || filled($filters['startDate'] ?? null) || filled($filters['endDate'] ?? null) || filled($filters['category_id'] ?? null),
            function (Builder $query) use ($organizationId, $filters) {
                $query->whereHas('feedback', function (Builder $q) use ($organizationId, $filters) {
                    $q->when($organizationId, fn (Builder $q) => $q->where('organization_id', $organizationId))
                        ->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
                        ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId));
                });
            }
        );

        return $query->select('question')
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
                        if (is_null($data['value'])) {
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
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).'%' : 'N/A')
                            ->width('15%')
                            ->summarize(Average::make()
                                ->label('')
                                ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).'%' : 'N/A')
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
            FilterAction::make(),
            Action::make('report')
                ->label('Generate Report')
                ->icon('gmdi-file-download'),
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'filters' => $this->filters,
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

    protected function getCategoryOptions(): array
    {
        $organizationId = Auth::user()->role === UserRole::ADMIN
            ? Auth::user()->organization_id
            : $this->selectedOrganizationId;

        return Category::query()
            ->when($organizationId, fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->pluck('name', 'id')
            ->all();
    }
}
