<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Utils\FormatValidator;

class ProducerReceiptModel extends AbstractModel
{
    public function __construct(
        public string $vknTckn,
        public string $aliciAdi,
        public string $aliciSoyadi,
        public string $uuid                     = '',
        public string $belgeNumarasi            = '',
        public string $tarih                    = '',
        public string $saat                     = '',
        public string $sehir                    = '',
        public string $websitesi                = '',
        public array  $malHizmetTable           = [],
        public string $not                      = '',
        public string $teslimTarihi             = '',
        public float  $malHizmetToplamTutari    = 0,
        public float  $vergilerDahilToplamTutar = 0,
        public float  $odenecekTutar            = 0,
    ) {
        parent::__constuct();
        
        if ($this->teslimTarihi && !FormatValidator::date($this->teslimTarihi))
            throw new InvalidFormatException('Teslim tarihi geçerli formatta değil.', $this);
    }

    /**
     * addItem
     *
     * @param  ProducerReceiptItemModel ...$items
     * @return self
     */
    public function addItem(ProducerReceiptItemModel ...$items): self
    {
        $this->setItems(...$items);
        return $this;
    }

    /**
     * calculateTotals
     *
     * @return void
     */
    protected function calculateTotals(): void
    {
        // Mal/hizmet toplam
        $this->malHizmetToplamTutari = array_column_sum($this->getItems(), 'malHizmetTutari');

        // Vergiler dahil toplam
        $this->vergilerDahilToplamTutar = $this->malHizmetToplamTutari;

        // Ödenecek tutar (Vergiler dahil toplam - Vergiler toplam)
        $this->odenecekTutar = $this->vergilerDahilToplamTutar - array_column_sum($this->getTaxes(), 'amount');
    }

    /**
     * getTotals
     *
     * @return array
     */
    public function getTotals(): array
    {
        return [
            'malHizmetToplamTutari'    => amount_format($this->malHizmetToplamTutari),
            'vergilerDahilToplamTutar' => amount_format($this->vergilerDahilToplamTutar),
            'odenecekTutar'            => amount_format($this->odenecekTutar),
        ];
    }

    /**
     * export
     *
     * @return array
     */
    public function export(): array
    {
        return $this->keyMapper(
            array_merge($this->toArray(), $this->getTotals(), [
                'malHizmetTable' => $this->getItems(true),
            ])
        );
    }

    /**
     * keyMap
     *
     * @return array
     */
    protected function keyMap(): array
    {
        return [
            'teslimTarihi'          => 'teslimTarih',
            'malHizmetTable'        => 'mustahsilTable',
            'malHizmetToplamTutari' => 'malhizmetToplamTutari',
        ];
    }
}