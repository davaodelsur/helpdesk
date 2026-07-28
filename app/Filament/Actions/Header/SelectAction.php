<?php

namespace App\Filament\Actions\Header;

use App\Enums\UserRole;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\Concerns\HasSelect;
use Illuminate\Support\Facades\Auth;

class SelectAction extends Action
{
    use HasSelect;


    protected function setUp(): void
    {
        parent::setUp();

        $this->name('select-organization');

        $this->view('filament.panels.feedback.components.SelectAction');

        $this->placeholder('Select Organization');

        $this->hidden(fn() => Auth::user()->role != UserRole::AUDITOR);

        $this->options(function($arguments){
            $selectedValue = $arguments['value'] ?? null;
            $options = Organization::pluck('code', 'id')->prepend('Select All Organizations', 'all')->toArray() ;

            if($selectedValue) {
                unset($options[$selectedValue]);
            }

            return $options;
        });

        $this->action(function ($arguments, $livewire) {
            $livewire->selectedOrganizationId = $arguments['value'] === 'all' ? null : $arguments['value'];

            if (is_array($livewire->filters ?? null)) {
                $livewire->filters['category_id'] = null;
            }

            $livewire->resetTable();
        });
    }

}
