<?php

namespace App\Filament\Clusters\Feedbacks\Pages;

use App\Enums\Feedback;
use App\Enums\SqdQuestion;
use App\Enums\UserRole;
use App\Filament\Actions\Header\FilterAction;
use App\Filament\Clusters\Feedbacks;
use App\Filament\Clusters\Feedbacks\Widgets\AgeChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\CustomerTypeWidget;
use App\Filament\Clusters\Feedbacks\Widgets\GenderChart;
use App\Filament\Clusters\Feedbacks\Widgets\OverviewStatsWidget;
use App\Filament\Clusters\Feedbacks\Widgets\RegionChartWidget;
use App\Filament\Clusters\Feedbacks\Widgets\SQD0Widget;
use App\Models\Category;
use App\Models\Organization;
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
                Select::make('organization_id')
                    ->label('Organization')
                    ->placeholder('All organizations')
                    ->searchable()
                    ->hidden(fn() => request()->user()->role !== UserRole::AUDITOR)
                    ->options(fn (): array => Organization::pluck('code', 'id')->toArray()),
                Select::make('standard_type')
                    ->label('Standard Type')
                    ->placeholder('All standard types')
                    ->searchable()
                    ->options(fn (): array => Feedback::standardizationsLabel()),
                Select::make('category_id')
                    ->label('Category')
                    ->placeholder('All categories')
                    ->searchable()
                    ->options(fn (): array => $this->getCategoryOptions()),
                Select::make('service_type')
                    ->label('Service Type')
                    ->placeholder('All service types')
                    ->searchable()
                    ->options(fn (): array => Feedback::serviceTypesLabel()),
                DatePicker::make('startDate')
                    ->label('Start date')
                    ->maxDate(fn (callable $get) => $get('endDate') ?: now()),
                DatePicker::make('endDate')
                    ->label('End date')
                    ->minDate(fn (callable $get) => $get('startDate'))
                    ->maxDate(now()),
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

        $filters = $this->filters ?? [];

        $organizationId = (Auth::user()->role === UserRole::ADMIN)
            ? Auth::user()->organization_id
            : $filters['organization_id'] ?? null;

        $query->when(
            $organizationId || filled($filters['startDate'] ?? null) || filled($filters['endDate'] ?? null) || filled($filters['category_id'] ?? null) || filled($filters['service_type'] ?? null) || filled($filters['standard_type'] ?? null),
            function (Builder $query) use ($organizationId, $filters) {
                $query->whereHas('feedback', function (Builder $q) use ($organizationId, $filters) {
                    $q->when($organizationId, fn (Builder $q) => $q->where('organization_id', $organizationId))
                        ->when($filters['startDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($filters['endDate'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
                        ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId))
                        ->when($filters['service_type'] ?? null, fn (Builder $q, $serviceType)=> $q->whereHas('category', fn (Builder $q2) => $q2->where('service_type', $serviceType)));
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
            // SelectAction::make(),
            Action::make('report')
                ->label('Generate Report')
                ->icon('gmdi-file-download'),
            FilterAction::make(),
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
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
                'selectedCategoryId' => $this->filters['category_id'] ?? null,
            ]),
            SQD0Widget::make([
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
            ]),
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            GenderChart::make([
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
            ]),
            CustomerTypeWidget::make([
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
            ]),
            AgeChartWidget::make([
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
            ]),
            RegionChartWidget::make([
                'selectedOrganizationId' => $this->filters['organization_id'] ?? null,
            ]),
        ];
    }

    protected function getCategoryOptions(): array
    {
        $organizationId = Auth::user()->role === UserRole::ADMIN
            ? Auth::user()->organization_id
            : $this->filters['organization_id'] ?? null;

        return Category::query()
            ->when($organizationId, fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->pluck('name', 'id')
            ->all();
    }
}
