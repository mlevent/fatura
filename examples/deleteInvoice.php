<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;

try {

    $gib = (new Gib())->setTestCredentials('33333310', '1')
                      ->login();

    // Belge numaralarını portaldan getirin
    // $deleteIds = $gib->selectColumn('ettn')
    //                  ->getAll('12/12/2022', '12/12/2022');

    // Belge numaralarını kendiniz tanımlayın
    $deleteIds = [
        '90559052-8bd0-4f68-a733-12157cf53cfb',
        '521ce2b1-290c-45fa-8312-d455672289ef',
    ];

    if ($gib->deleteDraft($deleteIds)) {
        echo "{$gib->rowCount()} adet belge silindi!";
    }

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}