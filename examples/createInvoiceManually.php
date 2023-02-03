<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    // Fatura Detayları
    $invoice = [
        'tarih'                    => date('d/m/Y'),
        'saat'                     => date('H:m:s'),
        'vknTckn'                  => '11111111111',
        'vergiDairesi'             => 'Çekirge VD',
        'aliciUnvan'               => 'Levent İnşaat Malzemeleri San. Tic. Ltd. Şti.',
        'aliciAdi'                 => 'Mert',
        'aliciSoyadi'              => 'Levent',
        'adres'                    => 'İzmir Yolu Cd. No:212/B',
        'mahalleSemtIlce'          => 'Nilüfer',
        'sehir'                    => 'Bursa',
        'ulke'                     => 'Türkiye',
        'matrah'                   => 180,
        'malhizmetToplamTutari'    => 200,
        'toplamIskonto'            => 20,
        'hesaplananKdv'            => 32.4,
        'vergilerToplami'          => 32.4,
        'vergilerDahilToplamTutar' => 212.4,
        'odenecekTutar'            => 212.4,
    ];
    
    // Mal/Hizmet Detayları
    $invoice['malHizmetListe'] = [
        [
            'malHizmet'       => 'Muhtelif Oyuncak',
            'birimFiyat'      => 100,
            'miktar'          => 2,
            'kdvOrani'        => 18,
            'fiyat'           => 200,
            'iskontoOrani'    => 10,
            'iskontoTutari'   => 20,  
            'malHizmetTutari' => 180,
            'kdvTutari'       => 32.4,
        ]
    ];

    // Veriyi Modele Aktar (Toplamlar Hesaplanmaz)
    $invoice = InvoiceModel::import($invoice);

    // Gib Portal Bağlantısı
    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    // Faturayı Oluştur
    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
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