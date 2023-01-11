<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

use Mlevent\Fatura\Enums\Tax;

trait TaxableTrait
{
    /**
     * addTax
     *
     * @param  Tax     $tax
     * @param  integer $rate
     * @param  float   $amount
     * @return self
     */
    abstract public function addTax(Tax $tax, int $rate, float $amount): self;

    /**
     * taxes
     *
     * @var array
     */
    protected array $taxes = [];

    /**
     * addTaxFromArray
     *
     * @param  array $taxes
     * @return self
     */
    public function addTaxFromArray(array $taxes = []): self
    {
        if ($countTaxes = count($taxes)) {
            if ($countTaxes == count($taxes, COUNT_RECURSIVE)) $taxes = [$taxes];
            foreach ($taxes as $tax) {
                $this->addTax(...$tax);
            }
        }
        return $this;
    }

    /**
     * setTax
     *
     * @param  Tax            $tax
     * @param  integer        $rate
     * @param  float|callable $amount
     * @param  float          $vat
     * @return void
     */
    protected function setTax(Tax $tax, int $rate, float|callable $amount, float $vat): void
    {
        $this->taxes[$tax->name] = [
            'model'  => $tax,
            'rate'   => $rate,   // $tax->hasDefaultRate() ? $tax->defaultRate() : $rate
            'amount' => $amount,
            'vat'    => $vat,
        ];
    }

    /**
     * setTaxes
     *
     * @param  array $taxes
     * @return void
     */
    protected function setTaxes(array $taxes): void
    {
        $this->taxes = $taxes;
    }

    /**
     * getTaxes
     *
     * @return array
     */
    public function getTaxes(): array
    {
        return $this->taxes;
    }

    /**
     * taxExists
     *
     * @param  Tax     $taxes
     * @return boolean
     */
    protected function taxExists(Tax ...$taxes): bool
    {
        return array_filter($this->getTaxes(), fn ($tax) => in_array($tax['model'], $taxes)) ? true : false;
    }

    /**
     * calculateTaxes
     *
     * @return void
     */
    protected function calculateTaxes(): void
    {
        $this->setTaxes(array_map(function ($tax) {
            return array_map(fn ($i) => is_callable($i) ? $i() : $i, $tax);
        }, $this->getTaxes()));
    }

    /**
     * exportTaxes
     *
     * @param  boolean $lowerFirst
     * @return array
     */
    protected function exportTaxes($lowerFirst = false): array
    {
        $taxes = [];
        foreach($this->getTaxes() as $tax) {
            foreach ($this->taxKeyMapper($tax['model'], $lowerFirst) as $i => $v) {
                $taxes[$v] = amount_format($tax[$i]);
            }
        }
        return $taxes;
    }

    /**
     * taxKeyMapper
     *
     * @param  Tax     $tax
     * @param  boolean $lowerFirst
     * @return array
     */
    protected function taxKeyMapper(Tax $tax, $lowerFirst = false): array
    {
        $exportKeyMap = [
            'rate'   => 'V%sOrani', 
            'amount' => 'V%sTutari', 
            'vat'    => 'V%sKdvTutari',
        ];

        if (!$tax->hasVat()) unset($exportKeyMap['vat']);

        return array_map(function ($item) use ($tax, $lowerFirst) {
            return $lowerFirst ? lcfirst(sprintf($item, $tax->value)) : sprintf($item, $tax->value);
        }, $exportKeyMap);
    }

    /**
     * totalTaxAmount
     *
     * @param  callable|null $filterFn
     * @return float
     */
    public function totalTaxAmount(callable $filterFn = null): float
    {
        return array_column_sum($this->taxes, 'amount', $filterFn);
    }

    /**
     * totalTaxVat
     *
     * @param  callable|null $filterFn
     * @return float
     */
    public function totalTaxVat(callable $filterFn = null): float
    {
        return array_column_sum($this->taxes, 'vat', $filterFn);
    }
}