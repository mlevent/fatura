<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    $invoiceDetails = [
        'faturaTipi'      => InvoiceType::Satis,
        'paraBirimi'      => Currency::TRY,
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
    
    $invoiceItems = [
        [
            'malHizmet'  => 'Muhtelif Oyuncak',
            'birim'      => Unit::Adet,
            'birimFiyat' => 124.52,
            'miktar'     => 1,
            'kdvOrani'   => 18,
        ],
        [
            'malHizmet'  => 'Muhtelif Kırtasiye',
            'birim'      => Unit::Adet,
            'birimFiyat' => 17.56,
            'miktar'     => 3,
            'kdvOrani'   => 8,
        ]
    ];

    $invoice = InvoiceModel::new($invoiceDetails);

    $invoice->addItem(...array_map(
        fn ($item) => InvoiceItemModel::new($item), 
    $invoiceItems));

    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

    dd($invoice->getTotals());

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}