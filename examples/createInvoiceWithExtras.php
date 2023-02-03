<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceReturnItemModel;

try {
    
    // Fatura Detayları
    $invoice = new InvoiceModel(
        faturaTipi      : InvoiceType::Iade,
        paraBirimi      : Currency::USD,
        dovizKuru       : 18.65,
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

    // Mal/Hizmet Detayları
    $invoice->addItem(
        (new InvoiceItemModel(
            malHizmet    : 'Muhtelif Oyuncak',
            miktar       : 2,
            birim        : Unit::Adet,
            birimFiyat   : 309.87,
            kdvOrani     : 18,
            iskontoOrani : 20,
        ))->addTax(Tax::Damga,    20)
          ->addTax(Tax::MeraFonu, 10)
    );

    // İade Faturası İçin İadeye Konu Faturalar
    $invoice->addReturnItem(
        new InvoiceReturnItemModel(
            faturaNo        : 'GIB2022000001416',
            duzenlenmeTarihi: '31/12/2022'
        )
    );

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