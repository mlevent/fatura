<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;

try {

    // Fatura Detayları
    $invoice = [
        'faturaUuid'               => create_uuid(),
        'belgeNumarasi'            => '',
        'faturaTarihi'             => date('d/m/Y'),
        'saat'                     => date('H:m:s'),
        'paraBirimi'               => 'TRY',
        'dovzTLkur'                => 0,
        'faturaTipi'               => 'SATIS',
        'hangiTip'                 => '5000/30000',
        'siparisNumarasi'          => '',
        'siparisTarihi'            => '',
        'irsaliyeNumarasi'         => '',
        'irsaliyeTarihi'           => '',
        'fisNo'                    => '',
        'fisTarihi'                => '',
        'fisSaati'                 => '',
        'fisTipi'                  => '',
        'zRaporNo'                 => '',
        'okcSeriNo'                => '',
        'vknTckn'                  => '11111111111',
        'aliciUnvan'               => '',
        'aliciAdi'                 => 'MERT',
        'aliciSoyadi'              => 'LEVENT',
        'bulvarcaddesokak'         => 'Sancı Sk. Lalezar Apt. No:44/A',
        'binaAdi'                  =>  '', 
        'binaNo'                   =>  '', 
        'kapiNo'                   =>  '', 
        'kasabaKoy'                =>  '', 
        'mahalleSemtIlce'          => 'Nilüfer',
        'sehir'                    => 'Bursa',
        'postaKodu'                => '',
        'ulke'                     => 'Türkiye',
        'tel'                      => '',
        'fax'                      => '',
        'eposta'                   => '',
        'websitesi'                => '',
        'vergiDairesi'             => '',
        'iadeTable'                => [],
        'malHizmetTable'           => [],
        'tip'                      => 'İskonto',
        'matrah'                   => 1500,
        'malhizmetToplamTutari'    => 1500,
        'toplamIskonto'            => 0,
        'hesaplanankdv'            => 270,
        'vergilerToplami'          => 270,
        'vergilerDahilToplamTutar' => 1770,
        'toplamMasraflar'          => 0,
        'odenecekTutar'            => 1770,
        'not'                      => '',
    ];

    // Mal/Hizmet Detayları
    $invoice['malHizmetTable'] = [
        [
            'malHizmet'         =>  'Danışmanlık Ücreti',
            'miktar'            =>  1,
            'birim'             =>  'HUR',
            'birimFiyat'        =>  1500,
            'fiyat'             =>  1500,
            'iskontoArttm'      =>  'İskonto',
            'iskontoOrani'      =>  0,
            'iskontoTutari'     =>  0,
            'iskontoNedeni'     =>  '',
            'malHizmetTutari'   =>  1500,
            'kdvOrani'          =>  18,
            'kdvTutari'         =>  270,
            'vergininKdvTutari' =>  0,
            'ozelMatrahTutari'  =>  0,
        ]
    ];

    // Gib Portal Bağlantısı
    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    // Faturayı Oluştur
    if ($gib->createDraft($invoice)) {
        echo $invoice['faturaUuid'];
    }

    // Oluşturulan Son Faturayı Gib Portal'dan Getir
    dd($gib->getLastDocument(), false);

    // Gib Portal Oturumunu Sonlandırma
    $gib->logout();

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}
