<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;

try {

    $gib = new Gib;
    $gib->testMode()->login('33333310', '1');

    $documents = $gib->onlyUnsigned()                                     // imzalanmamış belgeler
                     //->onlySigned()                                     // imzalanmış belgeler
                     //->onlyDeleted()                                    // silinmiş belgeler
                     //->onlyInvoice()                                    // faturalar
                     //->onlyProducerReceipt()                            // müstahsil makbuzları
                     //->onlySelfEmployedReceipt()                        // serbest meslek makbuzları
                     //->findRecipientName('ali')                         // alıcı adı filtreleme
                     //->findRecipientId('11111111111')                   // tc vergi numarası filtreleme
                     //->findDocumentId('GIB2022000000003')               // belge nuamarası filtreleme
                     //->findEttn('e3cc188c-4c33-11ed-be7d-4ccc6ae28384') // belge id filtereleme
                     //->setLimit(10)                                     // limit
                     //->sortAsc()                                        // önce ilk
                     //->sortDesc()                                       // önce son
                     //->getAllIssuedToMe('01/11/2022', '30/11/2022')     // adınıza düzenlenen belgeler
                     ->getAll('01/11/2022', '30/11/2022');                // düzenlenen belgeler
   
    dd($documents);

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}