<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    //die();
    
    $invoice = InvoiceModel::new(
        faturaTipi      : InvoiceType::Satis,
        paraBirimi      : Currency::TRY,
        tarih           : date('d/m/Y'),
        saat            : date('H:m:s'),
        vknTckn         : '11111111111',
        vergiDairesi    : 'Çekirge VD',
        aliciUnvan      : 'Levent İnşaat Malzemeleri San. Tic. Ltd. Şti.',
        aliciAdi        : 'Mert',
        aliciSoyadi     : 'Levent',
        adres           : 'İzmir Yolu Cd. No:212/B',
        mahalleSemtIlce : 'Nilüfer',
        sehir           : 'Bursa',
        ulke            : 'Türkiye',
        not             : '33 milyon karşılığında alındı',
    );

    $invoice->addItem(
        InvoiceItemModel::new(
            malHizmet  : 'Muhtelif Oyuncak',
            miktar     : 1,
            birim      : Unit::Adet,
            birimFiyat : 124.52,
            kdvOrani   : 18,
        )
    );

    $invoice->addItem(
        InvoiceItemModel::new(
            malHizmet  : 'Muhtelif Kırtasiye',
            miktar     : 3,
            birim      : Unit::Adet,
            birimFiyat : 17.56,
            kdvOrani   : 8,
        )
    );

    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice->export())) {
        echo $invoice->getUuid();
    }

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}