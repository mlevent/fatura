<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    // Fatura Detayları
    $invoice = [
        'tarih'           => date('d/m/Y'),
        'saat'            => date('H:m:s'),
        'vknTckn'         => '11111111111',
        'vergiDairesi'    => 'Çekirge VD',
        'aliciUnvan'      => 'Levent İnşaat Malzemeleri San. Tic. Ltd. Şti.',
        'aliciAdi'        => 'Mert',
        'aliciSoyadi'     => 'Levent',
        'adres'           => 'İzmir Yolu Cd. No:212/B',
        'mahalleSemtIlce' => 'Nilüfer',
        'sehir'           => 'Bursa',
        'ulke'            => 'Türkiye',
    ];
    
    // Mal/Hizmet Detayları
    $invoice['malHizmetListe'] = [
        [
            'malHizmet'    => 'Muhtelif Oyuncak',
            'birimFiyat'   => 124.52,
            'miktar'       => 1,
            'kdvOrani'     => 18,
            'iskontoOrani' => 10,
        ],
        [
            'malHizmet'  => 'Muhtelif Kırtasiye',
            'birimFiyat' => 17.56,
            'miktar'     => 3,
            'kdvOrani'   => 8,
        ]
    ];

    // Veriyi Modele Aktar (Tüm Toplamlar Otomatik Hesaplanır)
    $invoice = InvoiceModel::new($invoice);

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