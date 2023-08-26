<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;

try {

    // Gib Portal Bağlantısı
    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    // Güncellenecek Faturayı Gib Portal'dan Getir
    $invoice = $gib->getDocument('8f6fc032-a295-11ed-a6dc-62cb0e66eff6');

    // Veriyi Modele Aktar (Öğeleri Modelden Bağımsız İçe Aktarır)
    $invoice = InvoiceModel::importFromApi($invoice);

    // Güncellenecek Alanlar
    $invoice->aliciAdi    = 'Walter';
    $invoice->aliciSoyadi = 'Bishop';

    // Faturayı Güncelle
    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

    // Gib Portal Oturumunu Sonlandırma
    $gib->logout();

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}