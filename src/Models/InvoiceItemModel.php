<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\InvalidArgumentException;
use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Interfaces\ItemModelInterface;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\EachableTrait;
use Mlevent\Fatura\Traits\ImportableTrait;
use Mlevent\Fatura\Traits\MapableTrait;
use Mlevent\Fatura\Traits\NewableTrait;
use Mlevent\Fatura\Traits\TaxableTrait;
use Mlevent\Fatura\Utils\FormatValidator;

class InvoiceItemModel implements ItemModelInterface
{
    use ArrayableTrait,
        EachableTrait,
        ImportableTrait,
        MapableTrait, 
        NewableTrait,
        TaxableTrait;

    public function __construct(
        public string $malHizmet,
        public float  $miktar,
        public float  $birimFiyat,
        public float  $kdvOrani,
        public Unit   $birim            = Unit::Adet,
        public float  $fiyat            = 0,
        public string $iskontoTipi      = 'İskonto', // İskonto veya Arttırım 
        public float  $iskontoOrani     = 0,
        public float  $iskontoTutari    = 0,
        public string $iskontoNedeni    = '',
        public float  $malHizmetTutari  = 0,
        public float  $kdvTutari        = 0,
        public int    $tevkifatKodu     = 0,
        public int    $ozelMatrahNedeni = 0,
        public float  $ozelMatrahTutari = 0,
        public string $gtip             = '',
    ) {
        // KDV oranı
        if (!in_array($this->kdvOrani, [0,1,8,10,18,20])) {
            throw new InvalidArgumentException('Geçersiz KDV oranı.', $this);
        }

        // İskonto Tipi
        if (!in_array($this->iskontoTipi, ['İskonto', 'Arttırım'])) {
            throw new InvalidArgumentException('Geçersiz iskonto tipi.', $this);
        }

        // İçe aktarıldıysa hesaplama
        if (!$this->isImported()) {

            // Fiyat
            $this->fiyat = $this->fiyat 
                ?: ($this->miktar * $this->birimFiyat);

            // İskonto tutarı
            if ($this->iskontoOrani && !$this->iskontoTutari) {
                $this->iskontoTutari = percentage($this->fiyat, $this->iskontoOrani);
            }

            // İskonto sonrası yeni tutar
            if (!$this->malHizmetTutari) {
                if (!$this->iskontoTutari) {
                    $this->malHizmetTutari = $this->fiyat;
                } else {
                    $this->malHizmetTutari = ($this->iskontoTipi == 'İskonto' 
                        ? $this->fiyat - $this->iskontoTutari 
                        : $this->fiyat + $this->iskontoTutari);
                }
            }

            // KDV tutarı
            $this->kdvTutari = $this->kdvTutari 
                ?: percentage($this->malHizmetTutari, $this->kdvOrani);
        }
    }

    /**
     * addTax
     *
     * @param  Tax     $tax
     * @param  integer $rate
     * @param  float   $amount
     * @param  float   $vat
     * @return self
     */
    public function addTax(Tax $tax, int $rate, float $amount = 0, float $vat = 0): self
    {
        // Vergi tutarı
        $amount = $amount ?: (
            match ($tax) {
                Tax::KDVTevkifat => fn() => percentage($this->kdvTutari, $rate),
                default          =>         percentage($this->malHizmetTutari, $rate),
            }
        );

        // Ötv1ListeTevkifat
        if (Tax::OTV1ListeTevkifat == $tax) {
            $amount *= $this->miktar;
        }

        // Verginin KDV tutarı
        $vat = $vat ?: (
            $tax->hasVat() ? percentage($amount, $this->kdvOrani) : $vat
        );

        $this->setTax($tax, $rate, $amount, $vat);
        return $this;
    }

    /**
     * prepare
     *
     * @param  ModelInterface $parent
     * @return self
     */
    public function prepare(ModelInterface $parent): self
    {
        // Yeni toplam KDV tutarı
        $this->kdvTutari += $this->totalTaxVat();
        
        // Tevkifat
        if (InvoiceType::Tevkifat == $parent->faturaTipi) {
            if ($this->tevkifatKodu) {
                if (!array_key_exists($this->tevkifatKodu, Tax::KDVTevkifat->codes())) {
                    throw new InvalidArgumentException('Geçerli bir Tevkifat Kodu belirtilmeli.', $this);
                }
                $this->addTax(Tax::KDVTevkifat, Tax::KDVTevkifat->getRate($this->tevkifatKodu));
            }
        }

        // Özel Matrah
        if (InvoiceType::OzelMatrah == $parent->faturaTipi) {
            if ($this->ozelMatrahNedeni) {
                if (!array_key_exists($this->ozelMatrahNedeni, InvoiceType::OzelMatrah->reasons())) {
                    throw new InvalidArgumentException('Geçerli bir Özel Matrah nedeni belirtilmeli.', $this);
                }
                $this->kdvTutari = percentage($this->ozelMatrahTutari, $this->kdvOrani) + $this->totalTaxVat();
            }
        }

        // İstisna
        if (InvoiceType::Istisna == $parent->faturaTipi) {
            if ($this->gtip && !FormatValidator::gtipCode($this->gtip)) {
                throw new InvalidFormatException('GTIP 12 hane olmak zorunda.', $this);
            }
        }

        // Eklenen vergileri hesapla
        $this->calculateTaxes();
        return $this;
    }

    /**
     * getTotals
     *
     * @return array
     */
    public function getTotals(): array
    {
        return array_merge(['birimFiyat' => $this->birimFiyat], map_with_amount_format([
            'fiyat'            => $this->fiyat,
            'iskontoTutari'    => $this->iskontoTutari,
            'malHizmetTutari'  => $this->malHizmetTutari,
            'kdvTutari'        => $this->kdvTutari,
            'ozelMatrahTutari' => $this->ozelMatrahTutari,
        ]));
    }

    /**
     * export
     *
     * @return array
     */
    public function export(): array
    {
        return $this->keyMapper(
            array_merge($this->toArray(), $this->getTotals(), map_with_amount_format($this->exportTaxes()), [
                'birim' => $this->birim->value,
            ]
        ));
    }

    /**
     * keyMap
     *
     * @return array
     */
    protected function keyMap(): array
    {
        return [
            'iskontoTipi' => 'iskontoArttm',
        ];
    }
}