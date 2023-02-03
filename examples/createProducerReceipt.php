<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\ProducerReceiptItemModel;
use Mlevent\Fatura\Models\ProducerReceiptModel;

try {
    
    $invoice = new ProducerReceiptModel(
        vknTckn    : '32669381008',
        aliciAdi   : 'Mert',
        aliciSoyadi: 'Levent',
    );

    $invoice->addItem(
        new ProducerReceiptItemModel(
            malHizmet    : 'Muhtelif Oyuncak',
            miktar       : 1,
            birimFiyat   : 100,
            gvStopajOrani: 10
        )
    );

    $gib = (new Gib(DocumentType::ProducerReceipt))
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