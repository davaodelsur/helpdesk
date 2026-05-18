<?php

namespace App\Filament\Clusters\Feedbacks\Resources\CategoryResource\Pages;

use App\Filament\Actions\SyncTransactions;
use App\Filament\Actions\TallyTransactionsAction;
use App\Filament\Clusters\Feedbacks\Resources\CategoryResource;
use App\Filament\Clusters\Feedbacks\Widgets\TransactionOverview;
use App\Models\Transaction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SyncTransactions::make(),
            TallyTransactionsAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionOverview::class,
        ];
    }


}
