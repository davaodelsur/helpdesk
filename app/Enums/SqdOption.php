<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SqdOption: string implements HasLabel
{
    case STRONGLY_DISAGREE = '1';
    case DISAGREE = '2';
    case NEUTRAL = '3';
    case AGREE = '4';
    case STRONGLY_AGREE = '5';
    case NOT_APPLICABLE = '0';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::STRONGLY_DISAGREE => '1 - Strongly Disagree',
            self::DISAGREE => '2 - Disagree',
            self::NEUTRAL => '3 - Neutral',
            self::AGREE => '4 - Agree',
            self::STRONGLY_AGREE => '5 - Strongly Agree',
            self::NOT_APPLICABLE => 'N/A - Not Applicable',
        };
    }

}
