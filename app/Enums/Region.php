<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Region: string implements HasLabel
{
    case REGION_1 = 'region 1';
    case REGION_2 = 'region 2';
    case REGION_3 = 'region 3';
    case REGION_4A = 'region 4a';
    case REGION_4B = 'region 4b';
    case REGION_5 = 'region 5';
    case REGION_6 = 'region 6';
    case REGION_7 = 'region 7';
    case REGION_8 = 'region 8';
    case REGION_9 = 'region 9';
    case REGION_10 = 'region 10';
    case REGION_11 = 'region 11';
    case REGION_12 = 'region 12';
    case REGION_13 = 'region 13';
    case BARMM = 'barmm';
    case CAR = 'car';
    case NCR = 'ncr';

    public function getLabel(): ?string
    {
        return strtoupper(match($this) {
            self::REGION_1 => 'Ilocos Region (Region I)',
            self::REGION_2 => 'Cagayan Valley (Region II)',
            self::REGION_3 => 'Central Luzon (Region III)',
            self::REGION_4A => 'CALABARZON (Region IV-A)',
            self::REGION_4B => 'MIMAROPA (Region IV-B)',
            self::REGION_5 => 'Bicol Region (Region V)',
            self::REGION_6 => 'Western Visayas (Region VI)',
            self::REGION_7 => 'Central Visayas (Region VII)',
            self::REGION_8 => 'Eastern Visayas (Region VIII)',
            self::REGION_9 => 'Zamboanga Peninsula (Region IX)',
            self::REGION_10 => 'Northern Mindanao (Region X)',
            self::REGION_11 => 'Davao Region (Region XI)',
            self::REGION_12 => 'SOCCSKSARGEN (Region XII)',
            self::REGION_13 => 'Caraga (Region XIII)',
            self::BARMM => 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)',
            self::CAR => 'Cordillera Administrative Region (CAR)',
            self::NCR => 'National Capital Region (NCR)',
        });
    }

}
