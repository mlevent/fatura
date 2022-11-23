<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum DocumentType: string
{
    case Invoice             = 'FATURA';
    case ProducerReceipt     = 'MÜSTAHSİL MAKBUZU';
    case SelfEmployedReceipt = 'SERBEST MESLEK MAKBUZU';
}