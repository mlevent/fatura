<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\NewableTrait;
use Mlevent\Fatura\Utils\FormatValidator;

class InvoiceReturnItemModel
{
    use ArrayableTrait, 
        NewableTrait;

    public function __construct(
        public string $faturaNo,
        public string $duzenlenmeTarihi,
    ){
        if (!FormatValidator::invoiceNumber($this->faturaNo)) {
            throw new InvalidFormatException('Fatura numarası geçerli formatta değil.', $this);
        }
        if (!FormatValidator::date($this->duzenlenmeTarihi)) {
            throw new InvalidFormatException('Tarih geçerli formatta değil.', $this);
        }
    }
}