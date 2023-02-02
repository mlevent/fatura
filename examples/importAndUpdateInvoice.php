<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    $invoice = $gib->getDocument('39bff2da-5edc-11ed-b6bd-0050563e6fb2');

    $invoice = InvoiceModel::import(array_merge($invoice, [
        'aliciAdi' => 'Seymen',
        'adres'    => 'Muhabbet Sk.',
    ]));

    dd($invoice->export());

    $invoice->addItem(
        InvoiceItemModel::new(
            malHizmet  : 'Muhtelif Oyuncak',
            miktar     : 1,
            birim      : Unit::Adet,
            birimFiyat : 144.52,
            kdvOrani   : 18,
        ),
    );

    $invoice->addItem(
        InvoiceItemModel::new(
            malHizmet  : 'Muhtelif Oyuncak',
            miktar     : 2,
            birim      : Unit::Adet,
            birimFiyat : 12.52,
            kdvOrani   : 18,
        ),
    );

    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}