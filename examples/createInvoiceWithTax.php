<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    //die();
    
    $invoice = InvoiceModel::new(
        faturaTipi      : InvoiceType::Satis,
        dovizKuru       : 18.65,
        paraBirimi      : Currency::USD,
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
    );

    $invoice->addItem(
        InvoiceItemModel::new(
            malHizmet    : 'Muhtelif Oyuncak',
            miktar       : 2,
            birim        : Unit::Adet,
            birimFiyat   : 309.87,
            kdvOrani     : 18,
            iskontoTipi  : true,
            iskontoOrani : 12,
        )->addTax(Tax::Damga, 21)
         ->addTax(Tax::OTV1Liste, 0, 52.33),
        InvoiceItemModel::new(
            malHizmet    : 'Muhtelif Kırtasiye',
            miktar       : 7,
            birim        : Unit::Adet,
            birimFiyat   : 36.43,
            kdvOrani     : 8,
            iskontoOrani : 22,
        )->addTax(Tax::OTV1Liste, 0, 11.22)
         ->addTax(Tax::MeraFonu, 14)
    );

    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}