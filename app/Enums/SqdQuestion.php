<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SqdQuestion: string implements HasLabel, HasDescription
{
    case SQD0 = 'SQD0';
    case SQD1 = 'SQD1';
    case SQD2 = 'SQD2';
    case SQD3 = 'SQD3';
    case SQD4 = 'SQD4';
    case SQD5 = 'SQD5';
    case SQD6 = 'SQD6';
    case SQD7 = 'SQD7';
    case SQD8 = 'SQD8';

    public function getStandardizedName(): ?string
    {
        return match ($this) {
            self::SQD1 => 'Responsiveness',
            self::SQD2 => 'Reliability',
            self::SQD3 => 'Access and Facilities',
            self::SQD4 => 'Communication',
            self::SQD5 => 'Costs',
            self::SQD6 => 'Integrity',
            self::SQD7 => 'Assurance',
            self::SQD8 => 'Outcome',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SQD0 => 'SQD0',
            self::SQD1 => 'SQD1',
            self::SQD2 => 'SQD2',
            self::SQD3 => 'SQD3',
            self::SQD4 => 'SQD4',
            self::SQD5 => 'SQD5',
            self::SQD6 => 'SQD6',
            self::SQD7 => 'SQD7',
            self::SQD8 => 'SQD8',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::SQD0 => 'I am satisfied with the service that I availed.',
            self::SQD1 => 'I spent a reasonable amount of time for my transaction.',
            self::SQD2 => 'The office followed the transaction’s requirements and steps based on the information provided.',
            self::SQD3 => 'The steps (including payment) I needed to do for my transaction were easy and simple.',
            self::SQD4 => 'I easily found information about my transaction from this office or its website.',
            self::SQD5 => 'I paid a reasonable amount of fees for my transaction. (if service is free, mark ‘N/A’ column)',
            self::SQD6 => 'I feel the office was fair to everyone, or “walang palakasan”, during my transaction.',
            self::SQD7 => 'I was treated courteously by the staff, and (if asked for help) the staff was helpful.',
            self::SQD8 => 'I got what I needed from this office, or (if denied) denial of request was sufficiently explained to me.',
        };
    }

}
