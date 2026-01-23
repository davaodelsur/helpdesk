<?php

namespace App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages;

use App\Filament\Clusters\Feedbacks\Resources\CategoryResource;
use App\Filament\Clusters\Feedbacks\Resources\FeedbacksResource;
use App\Filament\Clusters\Feedbacks\Resources\FeedbacksResource\Pages\FeedbackForm;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListFeedbacks extends ManageRelatedRecords
{
    protected static string $resource = CategoryResource::class;

    protected static string $relationship = 'feedbacks';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('feedbacks.category_id')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->category?->name),
                TextColumn::make('organization.code')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sqdAverage')
                    ->label('SQD Average')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : 'N/A')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->sortable(),
            ])
            ->recordUrl(fn ($record) => FeedbacksResource::getUrl('view', ['record' => $record]));
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    public function getBreadcrumbs(): array
    {
        return array_merge(array_slice(parent::getBreadcrumbs(), 0, -1), [
            $this->record->name,
            'Feedbacks',
            'List',
        ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(static::getResource()::getUrl('index'))
                ->icon('gmdi-arrow-back-ios-o')
                ->color('gray'),
        ];
    }
}
