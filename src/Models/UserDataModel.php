<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\ImportableTrait;
use Mlevent\Fatura\Traits\MapableTrait;
use Mlevent\Fatura\Traits\NewableTrait;

class UserDataModel
{
    use ArrayableTrait,
        ImportableTrait,
        MapableTrait, 
        NewableTrait;

    public function __construct(
        public string $vknTckn         = '',
        public string $unvan           = '',
        public string $ad              = '',
        public string $soyad           = '',
        public string $cadde           = '',
        public string $apartmanAdi     = '',
        public string $apartmanNo      = '',
        public string $kapiNo          = '',
        public string $kasaba          = '',
        public string $ilce            = '',
        public string $il              = '',
        public string $postaKodu       = '',
        public string $ulke            = '',
        public string $telNo           = '',
        public string $faksNo          = '',
        public string $ePostaAdresi    = '',
        public string $webSitesiAdresi = '',
        public string $vergiDairesi    = '',
        public string $sicilNo         = '',
        public string $isMerkezi       = '',
        public string $mersisNo        = ''
    ){}

    /**
     * export
     *
     * @return array
     */
    public function export(): array
    {
        return $this->keyMapper($this->toArray());
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