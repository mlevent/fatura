<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Type;
use Mlevent\Fatura\Exceptions\InvalidArgumentException;

class InvoiceModel extends AbstractModel
{
    public function __construct(
        public string      $vknTckn,
        public string      $aliciAdi,
        public string      $aliciSoyadi,
        public string      $mahalleSemtIlce,
        public string      $sehir,
        public string      $ulke,
        public Type        $hangiTip                 = Type::eArsiv,
        public string      $uuid                     = '',
        public string      $belgeNumarasi            = '',
        public string      $tarih                    = '',
        public string      $saat                     = '',
        public Currency    $paraBirimi               = Currency::TRY,
        public float       $dovizKuru                = 0,
        public InvoiceType $faturaTipi               = InvoiceType::Satis,
        public string      $siparisNumarasi          = '',
        public string      $siparisTarihi            = '',
        public string      $irsaliyeNumarasi         = '',
        public string      $irsaliyeTarihi           = '',
        public string      $fisNo                    = '',
        public string      $fisTarihi                = '',
        public string      $fisSaati                 = '',
        public string      $fisTipi                  = '',
        public string      $zRaporNo                 = '',
        public string      $okcSeriNo                = '',
        public string      $aliciUnvan               = '',
        public string      $adres                    = '',
        public string      $binaAdi                  = '',
        public string      $binaNo                   = '',
        public string      $kapiNo                   = '',
        public string      $kasabaKoy                = '',
        public string      $postaKodu                = '',
        public string      $tel                      = '',
        public string      $fax                      = '',
        public string      $eposta                   = '',
        public string      $websitesi                = '',
        public string      $vergiDairesi             = '',
        public array       $iadeTable                = [],
        public array       $malHizmetTable           = [],
        public string      $not                      = '',
        public float       $matrah                   = 0,
        public float       $malHizmetToplamTutari    = 0,
        public float       $toplamIskonto            = 0,
        public float       $hesaplananKdv            = 0,
        public float       $vergilerToplami          = 0,
        public float       $vergilerDahilToplamTutar = 0,
        public float       $toplamMasraflar          = 0,
        public float       $odenecekTutar            = 0,
    )
    {
        parent::__constuct();

        if ($this->paraBirimi != Currency::TRY && !$this->dovizKuru)
            throw new InvalidArgumentException('Kur bilgisi belirtilmedi.', $this);
    }
    
    /**
     * addItem
     *
     * @param  InvoiceItemModel ...$items
     * @return self
     */
    public function addItem(InvoiceItemModel ...$items): self
    {
        $this->setItems(...$items);
        return $this;
    }

    /**
     * addReturnItem
     *
     * @param  InvoiceReturnItemModel ...$items
     * @return self
     */
    public function addReturnItem(InvoiceReturnItemModel ...$items): self
    {
        if ($this->faturaTipi == InvoiceType::Iade) {
            foreach ($items as $item) {
                $this->iadeTable[] = $item->toArray(); 
            }
        }
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
        $this->toplamIskonto         = array_column_sum($this->getItems(), 'iskontoTutari');
        $this->malHizmetToplamTutari = array_column_sum($this->getItems(), 'fiyat');
        $this->matrah                = array_column_sum($this->getItems(), 'malHizmetTutari');
        $this->hesaplananKdv         = array_column_sum($this->getItems(), 'kdvTutari');

        // Vergiler toplamı (KDV + stopaj vergiler hariç vergi toplami)
        $this->vergilerToplami = $this->hesaplananKdv + array_column_sum($this->getTaxes(), 'amount', 
            fn ($tax) => !$tax['model']->isStoppage()
        );

        // Vergiler dahil toplam
        $this->vergilerDahilToplamTutar = $this->matrah + $this->vergilerToplami;

        // Ödenecek tutar (Vergiler dahil toplam - stopaj vergiler toplami)
        $this->odenecekTutar = $this->vergilerDahilToplamTutar - array_column_sum($this->getTaxes(), 'amount', 
            fn ($tax) => $tax['model']->isStoppage() || $tax['model']->isWithholding()
        );
    }

    /**
     * getTotals
     *
     * @return array
     */
    public function getTotals(): array
    {
        return [
            'matrah'                   => amount_format($this->matrah),
            'malHizmetToplamTutari'    => amount_format($this->malHizmetToplamTutari),
            'toplamIskonto'            => amount_format($this->toplamIskonto),
            'hesaplananKdv'            => amount_format($this->hesaplananKdv),
            'vergilerToplami'          => amount_format($this->vergilerToplami),
            'vergilerDahilToplamTutar' => amount_format($this->vergilerDahilToplamTutar),
            'toplamMasraflar'          => amount_format($this->toplamMasraflar),
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
        // İskonto
        $this->toplamIskonto = abs(
            array_column_sum($this->getItems(), 'iskontoTutari', fn($item) => !$item->iskontoTipi)
            -
            array_column_sum($this->getItems(), 'iskontoTutari', fn($item) => $item->iskontoTipi)
        );
        
        return $this->keyMapper(
            array_merge($this->toArray(), $this->getTotals(), [
                'hangiTip'       => $this->hangiTip->value,
                'faturaTipi'     => $this->faturaTipi->value,
                'paraBirimi'     => $this->paraBirimi->name,
                'malHizmetTable' => $this->getItems(true),
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
            'uuid'                  => 'faturaUuid',
            'tarih'                 => 'faturaTarihi',
            'dovizKuru'             => 'dovzTLkur',
            'adres'                 => 'bulvarcaddesokak',
            'hesaplananKdv'         => 'hesaplanankdv',
            'malHizmetToplamTutari' => 'malhizmetToplamTutari',
        ];
    }
}