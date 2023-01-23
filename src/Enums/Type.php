<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum Type: string
{
    case eArsivFatura = '5000/30000';
    case eArsivDiger  = 'Buyuk';

    /**
     * alias
     *
     * @return string
     */
    public function alias(): string
    {
        return match ($this) {
            self::eArsivFatura => 'E-Arşiv Fatura',
            self::eArsivDiger  => 'E-Arşiv Diğer',
        };
    }
}