<?php

namespace App\Filament\Clusters\Feedbacks\Resources\FeedbacksResource\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Feedbacks\Resources\FeedbacksResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFeedbacks extends ListRecords
{
    protected static string $resource = FeedbacksResource::class;

    public function getTabs(): array
    {
        $panel = Filament::getCurrentPanel()->getId();

        if($panel === UserRole::ROOT->value){
            return [
                'all' => Tab::make('All')
                    ->icon('gmdi-feedback-o')
                    ->badge(fn () => static::$resource::getEloquentQuery()->count()),
                'trashed' => Tab::make('Trashed')
                    ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                    ->icon('gmdi-delete-o')
                    ->badgeColor('danger')
                    ->badge(fn () => static::$resource::getEloquentQuery()->onlyTrashed()->count()),
            ];
        }
        return [];
    }
}
