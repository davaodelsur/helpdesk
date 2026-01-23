<?php

namespace App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages;

use App\Filament\Actions\TallyTransactionsAction;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TallyTransactionsAction::make(),
        ];
    }
}
