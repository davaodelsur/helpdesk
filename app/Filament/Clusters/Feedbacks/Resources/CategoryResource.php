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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'gmdi-change-circle-o';

    protected static ?string $cluster = Feedbacks::class;

    protected static ?string $navigationLabel = 'Transactions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable(isIndividual:true),
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
                    ->formatStateUsing(fn($record) => $record->requests()->count()- $record->feedbacks()->count())
                    ->default(0),
                TextColumn::make('transactions_sum_total_transactions')
                    ->label('Total Transactions')
                    ->formatStateUsing(fn($state, $record) => $state + $record->requests()->count())
                    ->sortable()
                    ->default(0),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withSum('transactions', 'total_transactions');

        return match (Filament::getCurrentPanel()->getId()) {
            'root' => $query,
            'admin' => $query->where('organization_id', Filament::auth()->user()->organization_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
        ];
    }
}
