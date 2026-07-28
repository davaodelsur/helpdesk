<?php

namespace App\Filament\Actions\Header;

use Filament\Actions\Action;

class FilterAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('dashboard-filters');

        $this->view('filament.panels.feedback.components.FilterAction');

        $this->label(__('filament-tables::table.actions.filter.label'));
    }
}
