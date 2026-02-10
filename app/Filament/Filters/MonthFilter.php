<?php

namespace App\Filament\Filters;

use App\Models\Request;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class MonthFilter extends SelectFilter
{

    public static function make(?string $name = null): static
    {
        $filterClass = static::class;

        $name ??= 'month-filter';

        $static = app($filterClass, ['name' => $name]);

        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('date_month');

        $this->label('Month');

        $this->placeholder('Select month');

        $this->searchable(true);

        $this->options(fn() => Request::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key")
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%M') as month_label")
            ->groupBy('month_key', 'month_label')
            ->orderByDesc('month_key')
            ->pluck('month_label', 'month_key'));

        $this->query(function (Builder $query, array $data) {
            if (empty($data['value'])) {
                return;
            }
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$data['value']])->toSql();
        });
    }


}
