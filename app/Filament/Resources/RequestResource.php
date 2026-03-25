<?php

namespace App\Filament\Resources;

use App\Enums\ActionResolution;
use App\Enums\ActionStatus;
use App\Enums\RequestClass;
use App\Filament\Clusters\Requests;
use App\Filament\Filters\AssigneeFilter;
use App\Filament\Filters\MonthFilter;
use App\Filament\Filters\OrganizationFilter;
use App\Filament\Filters\TagFilter;
use App\Models\Action;
use App\Models\Category;
use App\Models\Request;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class RequestResource extends Resource
{
    public static ?bool $inbound = true;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Requests::class;

    protected static ?RequestClass $class = null;

    public static function table(Table $table): Table
    {
        $panel = Filament::getCurrentPanel()->getId();

        return $table
            ->columns([
                TextColumn::make('action.status')
                    ->searchable(['code'])
                    ->label('Status')
                    ->badge()
                    ->description(function (Request $request) {
                        $action = match($request->class) {
                            RequestClass::SUGGESTION => match ($request->action->status) {
                                ActionStatus::CLOSED => match ($request->action->resolution) {
                                    default => $request->action,
                                },
                                default => null,
                            },
                            default => match ($request->action->status) {
                                ActionStatus::COMPLETED,
                                ActionStatus::SUSPENDED,
                                ActionStatus::REOPENED,
                                ActionStatus::REINSTATED => $request->action,
                                ActionStatus::CLOSED => match ($request->action->resolution) {
                                    ActionResolution::RESOLVED => $request->completion,
                                    default => $request->action,
                                },
                                default => null,
                            },
                        };

                        $color = $action?->status === ActionStatus::CLOSED ? $action->resolution->getColor() : $action?->status->getColor();

                        $latest = $action ?  $action->created_at->format('jS M H:i') : null;

                        $style = $action ? "--c-400:var(--{$color}-400);--c-600:var(--{$color}-600);" : null;

                        $html = <<<HTML
                            <div class="flex flex-col">
                                <span class="text-xs text-custom-600 dark:text-custom-400" style="{$style}"> $latest </span>

                                <span class="font-mono text-sm"> #{$request->code} </span>
                            </div>
                        HTML;

                        return str($html)->toHtmlString();
                    })
                    ->state(function (Request $request) {
                        return match ($request->action?->status) {
                            ActionStatus::CLOSED => $request->action?->resolution,
                            ActionStatus::REPLIED,
                            ActionStatus::STARTED => ActionStatus::IN_PROGRESS,
                            ActionStatus::SUSPENDED => ActionStatus::ON_HOLD,
                            default => $request->action?->status,
                        };
                    }),
                TextColumn::make('subject')
                    ->label('Request')
                    ->searchable()
                    ->limit(24)
                    ->wrap()
                    ->tooltip(fn ($column) => strlen($column->getState()) > $column->getCharacterLimit() ? $column->getState() : null)
                    ->description(function (Request $request) {
                        $submission = $request->submission
                            ? $request->submission->created_at->format('jS M H:i')
                            : null;

                        $html = <<<HTML
                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                {$submission}
                            </span>
                        HTML;

                        return str($html)->toHtmlString();
                    }, 'above')
                    ->sortable(query: function(Builder $query, string $direction) {
                            return $query->reorder()->orderBy('created_at', $direction);
                        }),
                TextColumn::make('user.name')
                    ->searchable(['name', 'email'])
                    ->description(fn (Request $request) => $request->from?->code)
                    ->hidden(static::$inbound === null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('organization.code')
                    ->sortable()
                    ->searchable()
                    ->limit(36)
                    ->extraCellAttributes(['class' => 'font-mono'])
                    ->tooltip(fn (Request $request) => $request->organization->name)
                    ->visible(in_array($panel, ['root']) || static::$inbound === null),
                TextColumn::make('category.name')
                    ->description(fn (Request $request, $column) => str($request->subcategory->name)->limit($column->getCharacterLimit()))
                    ->tooltip(fn (Request $request, $column) => max(strlen($column->getState()), strlen($request->subcategory->name)) > $column->getCharacterLimit()
                        ? "{$column->getState()} — {$request->subcategory->name}"
                        : null
                    )
                    ->limit(24),
                TextColumn::make('assignees.name')
                    ->searchable(['name', 'email'])
                    ->bulleted()
                    ->limitList(2),
                TextColumn::make('tags.name')
                    ->badge()
                    ->wrap()
                    ->alignEnd()
                    ->toggleable()
                    ->color(fn (Request $request, string $state) => $request->tags->first(fn ($tag) => $tag->name === $state)?->color ?? 'gray'),
            ])
            ->filters(static::tableFilters())
            ->actions(static::tableActions())
            ->bulkActions(static::tableBulkActions())
            ->paginationPageOptions([5, 10, 15, 20, 25])
            ->defaultPaginationPageOption(10)
            ->defaultSort(
                fn (Builder $query) => $query->orderBy(
                    Action::query()
                        ->select('id')
                        ->whereColumn('actions.request_id', 'requests.id')
                        ->where('status', ActionStatus::SUBMITTED)
                        ->latest()
                        ->limit(1),
                    'desc'
                ),
            )
            ->defaultGroup(
                Group::make('date')
                    ->getKeyFromRecordUsing(
                        fn (Request $record): string => $record->created_at->format('Y-m-0')
                    )
                    ->getTitleFromRecordUsing(
                        fn (Request $record): string => $record->created_at->format('F Y')
                    )
                    ->orderQueryUsing(
                        function($query) {
                            $query->orderBy(
                                Action::query()
                                ->select('id')
                                ->whereColumn('actions.request_id', 'requests.id')
                                ->where('status', ActionStatus::SUBMITTED)
                                ->latest()
                                ->limit(1),
                                'desc'
                                );
                        }
                    )
                    ->titlePrefixedWithLabel(false),
            )
            ;
    }

    public static function getNavigationBadge(): ?string
    {
        if (Filament::getCurrentPanel()->getId() === 'root') {
            return static::getEloquentQuery()->count() ?: null;
        }

        return static::getEloquentQuery()
            ->whereHas('action', fn ($query) => $query->where('status', ActionStatus::SUBMITTED))
            ->count() ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        $panel = Filament::getCurrentPanel()->getId();

        $query = parent::getEloquentQuery()
            ->when(static::$class, fn ($query, $class) => $query->where('class', $class))
            ->when($panel !== 'root' && static::$inbound !== null, fn ($query) => $query->whereHas('action', fn ($query) => $query->where('status', '!=', ActionStatus::RECALLED)));

        return match (static::$inbound) {
            true => match ($panel) {
                'root' => $query,
                'admin' => $query->where('organization_id', Auth::user()->organization_id),
                'moderator' => $query->where('organization_id', Auth::user()->organization_id),
                'agent' => $query->whereHas('assignees', fn ($query) => $query->where('assigned_id', Auth::id())),
                default => $query->whereRaw('1 = 0'),
            },
            false => match ($panel) {
                'root' => $query,
                'admin','moderator' => $query->where('from_id', Auth::user()->organization_id),
                default => $query->whereRaw('1 = 0'),
            },
            default => $query->where('user_id', Auth::id()),
        };
    }

    public static function tableFilters(): array
    {
        if (static::class === self::class) {
            return [
                SelectFilter::make('class')
                    ->options(RequestClass::class),
            ];
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'root' => [
                TagFilter::make(),
                OrganizationFilter::make()
                    ->withUnaffiliated(false),
                TrashedFilter::make(),
            ],
            default => match (static::$inbound) {
                true => [
                    AssigneeFilter::make(),
                    SelectFilter::make('categories')
                        ->relationship(
                            'subcategory',
                            'name',
                            fn (Builder $query) => $query
                                ->with('category')
                                ->whereRelation('category', 'categories.organization_id', Auth::user()->organization_id)
                                ->orderBy(
                                    Category::select('name')
                                        ->whereColumn('categories.id', 'subcategories.category_id'),
                                ),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->category->name} — {$record->name}")
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->placeholder('Select categories'),
                    TagFilter::make(),
                    MonthFilter::make(),
                ],
                default => [
                    TagFilter::make(),
                ],
            },
        };
    }

    abstract public static function tableActions(): array;

    public static function tableBulkActions(): array
    {
        return [];
    }
}
