<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Interfaces\ItemModelInterface;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\ImportableTrait;
use Mlevent\Fatura\Traits\MapableTrait;
use Mlevent\Fatura\Traits\NewableTrait;
use Mlevent\Fatura\Traits\TaxableTrait;

class ProducerReceiptItemModel implements ItemModelInterface
{
    use ArrayableTrait,
        ImportableTrait,
        MapableTrait,
        NewableTrait,
        TaxableTrait;

    public function __construct(
        public string $malHizmet,
        public float  $miktar,
        public float  $birimFiyat,
        public Unit   $birim           = Unit::Adet,
        public float  $malHizmetTutari = 0,
        public int    $gvStopajOrani   = 0,
    ) {
        // İçe aktarıldıysa hesaplama
        if (!$this->isImported()) {

            // Tutar
            $this->malHizmetTutari = $this->malHizmetTutari 
                ?: ($this->miktar * $this->birimFiyat);

            // GVStopaj
            $this->addTax(Tax::GVStopaj, rate: $this->gvStopajOrani);
        }
    }

    /**
     * addTax
     *
     * @param  Tax     $tax
     * @param  integer $rate
     * @param  float   $amount
     * @return self
     */
    public function addTax(Tax $tax, int $rate, float $amount = 0): self
    {
        // Vergi tutarı
        $amount = $amount ?: (
            match ($tax) {
                Tax::BorsaTescil => fn() => percentage($this->malHizmetTutari - $this->totalTaxAmount(fn ($tax) => $tax['model'] != Tax::BorsaTescil), $rate),
                default          =>         percentage($this->malHizmetTutari, $rate),
            }
        );

        $this->setTax($tax, $rate, $amount, 0);
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
        return [
            'birimFiyat'      => amount_format($this->birimFiyat),
            'malHizmetTutari' => amount_format($this->malHizmetTutari),
        ];
    }

    /**
     * export
     *
     * @return array
     */
    public function export(): array
    {
        return $this->keyMapper(array_merge($this->toArray(), $this->getTotals(), $this->exportTaxes(true), [
            'birim' => $this->birim->value,
        ]));
    }

    /**
     * keyMap
     *
     * @return array
     */
    public function keyMap(): array
    {
        return [];
    }
}