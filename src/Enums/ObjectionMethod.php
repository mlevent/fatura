<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum ObjectionMethod: string
{
    case Noter           = 'NOTER';
    case TaahhutluMektup = 'TAAHHUTLU_MEKTUP';
    case Telgraf         = 'TELGRAF';
    case Kep             = 'KEP';
}