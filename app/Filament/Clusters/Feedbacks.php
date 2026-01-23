<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;

class Feedbacks extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return in_array(Filament::getCurrentPanel()->getId(), ['root','admin', 'auditor']);
    }
}
