<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Exceptions\InvalidArgumentException;

class SelfEmployedReceiptModel extends AbstractModel
{
    public function __construct(
        public string   $vknTckn,
        public string   $aliciAdi,
        public string   $aliciSoyadi,
        public string   $ulke,
        public string   $uuid              = '',
        public string   $belgeNumarasi     = '',
        public string   $tarih             = '',
        public string   $saat              = '',
        public Currency $paraBirimi        = Currency::TRY,
        public float    $dovizKuru         = 0,
        public string   $aliciUnvan        = '',
        public string   $adres             = '',
        public string   $binaAdi           = '',
        public string   $binaNo            = '',
        public string   $kapiNo            = '',
        public string   $kasabaKoy         = '',
        public string   $mahalleSemtIlce   = '',
        public string   $sehir             = '',
        public string   $postaKodu         = '',
        public string   $vergiDairesi      = '',
        public string   $aciklama          = '',
        public bool     $kdvTahakkukIcin   = false,
        public array    $malHizmetListe    = [],
        public float    $brutUcret         = 0,
        public float    $gvStopajTutari    = 0,
        public float    $netUcretTutari    = 0,
        public float    $kdvTutari         = 0,
        public float    $kdvTevkifatTutari = 0,
        public float    $tahsilEdilenKdv   = 0,
        public float    $netAlinanToplam   = 0,
        public float    $xxx               = 0,
    ) {
        parent::__constuct();
        
        if ($this->paraBirimi != Currency::TRY && !$this->dovizKuru)
            throw new InvalidArgumentException('Kur bilgisi belirtilmedi.', $this);
    }

    /**
     * addItem
     *
     * @param  SelfEmployedReceiptItemModel ...$items
     * @return self
     */
    public function addItem(SelfEmployedReceiptItemModel ...$items): self
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
        // Toplamlar
        $this->brutUcret         = array_column_sum($this->getItems(), 'brutUcret');
        $this->gvStopajTutari    = array_column_sum($this->getItems(), 'gvStopajTutari');
        $this->netUcretTutari    = array_column_sum($this->getItems(), 'netUcret');
        $this->kdvTutari         = array_column_sum($this->getItems(), 'kdvTutari');
        $this->kdvTevkifatTutari = array_column_sum($this->getItems(), 'kdvTevkifatTutari');
        $this->netAlinanToplam   = array_column_sum($this->getItems(), 'netAlinan');

        // Tahsil edilen kdv (KDV tutari - KDV tevkifat tutari)
        $this->tahsilEdilenKdv = $this->kdvTutari - $this->kdvTevkifatTutari;
    }

    /**
     * getTotals
     *
     * @return array
     */
    public function getTotals(): array
    {
        return [
            'brutUcret'         => amount_format($this->brutUcret),
            'netUcretTutari'    => amount_format($this->netUcretTutari),
            'gvStopajTutari'    => amount_format($this->gvStopajTutari),
            'kdvTutari'         => amount_format($this->kdvTutari),
            'kdvTevkifatTutari' => amount_format($this->kdvTevkifatTutari),
            'tahsilEdilenKdv'   => amount_format($this->tahsilEdilenKdv),
            'netAlinanToplam'   => amount_format($this->netAlinanToplam),
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
                'paraBirimi'     => $this->paraBirimi->name,
                'malHizmetListe' => $this->getItems(true),
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
            'uuid'              => 'ettn',
            'aliciAdi'          => 'adi',
            'aliciSoyadi'       => 'soyadi',
            'aliciUnvan'        => 'unvan',
            'adres'             => 'bulvarCaddeSokak',
            'malHizmetListe'    => 'serbestTable',
            'dovizKuru'         => 'kur',
            'brutUcret'         => 'brtUcret',
            'gvStopajTutari'    => 'gvStpjTtari',
            'netUcretTutari'    => 'netUcretTtr',
            'kdvTutari'         => 'kdvTtri',
            'kdvTevkifatTutari' => 'kdvTvkftTtri',
            'tahsilEdilenKdv'   => 'thsilEdilenKdv',
        ];
    }
}