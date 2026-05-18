<?php

namespace App\Filament\Actions;

use App\Models\Feedback;
use App\Models\Request;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncTransactions extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('sync-transactions');

        $this->label('Sync Transactions');

        $this->icon('gmdi-sync');

        $this->requiresConfirmation();

        $this->hidden(fn () => Filament::getCurrentPanel()->getId() !== 'root');

        $this->color('blue');

        $this->action(function ($data): void {
            try{
                $feedback = Feedback::query()
                    ->select('organization_id', 'category_id', DB::raw('count(id) as total'))
                    ->groupBy('organization_id', 'category_id')
                    ->get();
                $requests = Request::query()
                    ->select('organization_id', 'category_id', DB::raw('count(id) as total'))
                    ->groupBy('organization_id', 'category_id')
                    ->get();
                $merged = collect($feedback)->concat($requests)
                    ->groupBy(fn($item) => $item->organization_id . '-' . $item->category_id)
                    ->map(fn($group) => [
                        'id' => (string) Str::ulid(),
                        'organization_id' => $group->first()->organization_id,
                        'category_id' => $group->first()->category_id,
                        'total_transactions' => $group->sum('total'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $this->beginDatabaseTransaction();

                if(Transaction::count() > 0){
                    Transaction::query()->whereNull('user_id')->delete();
                }
                foreach ($merged->chunk(500) as $chunk) {
                    Transaction::insert($chunk->toArray());
                }
                $this->commitDatabaseTransaction();
                Notification::make()
                    ->title('Transactions synced successfully')
                    ->success()
                    ->send();
            }catch(\Exception $e){
                $this->rollBackDatabaseTransaction();
                Notification::make()
                    ->title('Error syncing transactions')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        });

        $this->form([
            TextInput::make('password')
                ->label('Password')
                ->markAsRequired()
                ->password()
                ->revealable()
                ->rules(['required',
                    fn() => function ($attribute, $value, $fail){
                        if(! password_verify($value, Auth::user()->password)) {
                            $fail('Incorrect password');
                        }
                    }
                ])

        ]);

    }
}
