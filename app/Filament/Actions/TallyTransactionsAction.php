<?php

namespace App\Filament\Actions;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class TallyTransactionsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('tally-transactions');

        $this->label('Transactions');

        $this->icon('uni-exchange-alt-o');

        $this->hidden(fn () => !in_array(Filament::getCurrentPanel()->getId(), [UserRole::ROOT->value, UserRole::ADMIN->value]));

        $this->form([
            Repeater::make('transactions')
                        ->label('List of Transactions')
                        ->schema([
                            Select::make('category_id')
                                ->label('Service Type')
                                ->options(function () {
                                    return Category::where('organization_id', Filament::auth()->user()->organization_id)->pluck('name', 'id')->toArray();
                                })
                                ->required(),
                            TextInput::make('total_transactions')
                                ->label('Total Transactions')
                                ->mask('9999999999')
                                ->required(),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->addActionLabel('Add Transaction')
                        ->reorderable(false),
        ]);

        $this->action(function ($data): void {
            try{

                $this->beginDatabaseTransaction();

                foreach ($data['transactions'] as $transactionData) {
                    Transaction::create([
                        'category_id' => $transactionData['category_id'],
                        'organization_id' => Filament::auth()->user()->organization_id,
                        'total_transactions' => $transactionData['total_transactions'],
                    ]);
                }

                $this->commitDatabaseTransaction();

                $this->sendSuccessNotification();

            } catch (\Exception $e) {
                $this->rollbackDatabaseTransaction();

                $this->sendFailureNotification();
            }
        });

    }

}
