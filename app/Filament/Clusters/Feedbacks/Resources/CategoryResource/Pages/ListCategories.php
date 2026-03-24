<?php

namespace App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages;

use App\Filament\Actions\TallyTransactionsAction;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\SelectFilter;

class ListCategories extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TallyTransactionsAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CategoryResource\Widgets\TransactionOverview::class,
        ];
    }

}
