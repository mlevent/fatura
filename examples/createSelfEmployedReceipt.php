<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\SelfEmployedReceiptItemModel;
use Mlevent\Fatura\Models\SelfEmployedReceiptModel;

try {
    
    $invoice = new SelfEmployedReceiptModel(
        vknTckn    : '32669381008',
        aliciAdi   : 'Mert',
        aliciSoyadi: 'Levent',
        ulke       : 'Türkiye',
    );

    $invoice->addItem(
        new SelfEmployedReceiptItemModel(
            neIcinAlindigi  : 'Dava Vekilliği',
            brutUcret       : 100,
            kdvOrani        : 18,
            gvStopajOrani   : 20,
            kdvTevkifatOrani: 40,
        )
    );

    $gib = (new Gib(DocumentType::SelfEmployedReceipt))
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

    $gib->logout();

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}