<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum Type: string
{
    case eArsiv      = '5000/30000';
    case eArsivBuyuk = 'Buyuk';

    /**
     * alias
     *
     * @return string
     */
    public function alias(): string
    {
        return match ($this) {
            self::eArsiv      => 'E-Arşiv',
            self::eArsivBuyuk => 'E-Arşiv Büyük',
        };
    }
}