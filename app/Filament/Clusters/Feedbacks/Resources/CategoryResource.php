<?php

namespace App\Filament\Clusters\Feedbacks\Resources;

use App\Filament\Clusters\Feedbacks;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'gmdi-change-circle-o';

    protected static ?string $cluster = Feedbacks::class;

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction Category';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable(isIndividual:true)
                    ->limit(50),
                TextColumn::make('organization.code')
                    ->label('Organization')
                    ->default('N/A')
                    ->searchable(isIndividual:true)
                    ->hidden(fn () => in_array(Filament::getCurrentPanel()->getId(), ['admin', 'auditor'])),
                TextColumn::make('surveyed_count')
                    ->label('Surveyed')
                    ->formatStateUsing(fn($state, $record) => $state + $record->feedbacks()->count())
                    ->default(0),
                TextColumn::make('not_surveyed_count')
                    ->label('Not Surveyed')
                    ->default(0)
                    ->formatStateUsing(fn($state, $record) => $record->getTotalTransactionsAttribute() - $record->feedbacks()->count()),
                TextColumn::make('total_transactions')
                    ->label('Total Transactions')
                    ->sortable()
                    ->default(0),
            ])
            ->recordUrl( fn (Category $record) => static::getUrl('ListFeedbacks', ['record' => $record]) )
            ->filters([
                SelectFilter::make('organization_id')
                   ->label('Organization')
                   ->searchable()
                   ->options(fn () => \App\Models\Organization::pluck('code', 'id'))
                   ->hidden(fn() => !in_array(Filament::getCurrentPanel()->getId(), ['root', 'auditor'])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withSum('transactions', 'total_transactions');

        return match (Filament::getCurrentPanel()->getId()) {
            'root' => $query,
            'auditor' => $query,
            'admin' => $query->where('organization_id', Filament::auth()->user()->organization_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'ListFeedbacks' => Pages\ListFeedbacks::route('/{record}/feedbacks'),
        ];
    }
}
