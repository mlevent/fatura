<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Exceptions\InvalidArgumentException;
use Mlevent\Fatura\Interfaces\ItemModelInterface;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\ImportableTrait;
use Mlevent\Fatura\Traits\MapableTrait;
use Mlevent\Fatura\Traits\NewableTrait;

class SelfEmployedReceiptItemModel implements ItemModelInterface
{
    use ArrayableTrait,
        ImportableTrait,
        MapableTrait,
        NewableTrait;

    public float $gvStopajTutari    = 0;
    public float $kdvTutari         = 0;
    public float $kdvTevkifatTutari = 0;

    public function __construct(
        public string $neIcinAlindigi,
        public float  $brutUcret,
        public float  $kdvOrani,
        public int    $gvStopajOrani    = 0,
        public float  $netUcret         = 0,
        public int    $kdvTevkifatOrani = 0,
        public float  $netAlinan        = 0,
    ) {
        // KDV oranı
        if (!in_array($this->kdvOrani, [0,1,8,10,18,20])) {
            throw new InvalidArgumentException('Geçersiz KDV oranı.', $this);
        }

        // İçe aktarıldıysa hesaplama
        if (!$this->isImported()) {

            // Stopaj Tutarı
            $this->gvStopajTutari = $this->gvStopajTutari
                ?: percentage($this->brutUcret, $this->gvStopajOrani);

            // Net Ücret
            $this->netUcret = $this->netUcret 
                ?: $this->brutUcret - $this->gvStopajTutari;

            // Kdv Tutarı
            $this->kdvTutari = $this->kdvTutari
                ?: percentage($this->brutUcret, $this->kdvOrani);

            // Kdv Tevkifat Tutarı
            $this->kdvTevkifatTutari = $this->kdvTevkifatTutari
                ?: percentage($this->kdvTutari, $this->kdvTevkifatOrani);
            
            // Net Alınan
            $this->netAlinan = $this->netAlinan 
                ?: $this->netUcret + $this->kdvTutari - $this->kdvTevkifatTutari;
        }
    }

    /**
     * prepare
     *
     * @param  ModelInterface $parent
     * @return self
     */
    public function prepare(ModelInterface $parent): self
    {
        return $this;
    }

    /**
     * getTotals
     *
     * @return array
     */
    public function getTotals(): array
    {
        return map_with_amount_format([
            'brutUcret'         => $this->brutUcret,
            'netUcret'          => $this->netUcret,
            'gvStopajTutari'    => $this->gvStopajTutari,
            'kdvTutari'         => $this->kdvTutari,
            'kdvTevkifatTutari' => $this->kdvTevkifatTutari,
            'netAlinan'         => $this->netAlinan,
        ]);
    }

    /**
     * export
     *
     * @return array
     */
    public function export(): array
    {
        return $this->keyMapper(
            array_merge($this->toArray(), $this->getTotals(), [])
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
            'gvStopajOrani' => 'stopaj',
            'kdvOrani'      => 'kdv',
        ];
    }
}