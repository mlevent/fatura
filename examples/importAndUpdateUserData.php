<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\UserDataModel;

try {

    $gib = new Gib;
    $gib->setTestCredentials('33333310', '1')->login();

    $userData = UserDataModel::import($gib->getUserData());

    $userData->apartmanAdi  = 'Lale Apartmanı';
    $userData->kapiNo       = '12';
    $userData->vergiDairesi = 'Bursa';

    if ($gib->updateUserData($userData)) {
        echo 'Bilgiler başarıyla güncellendi.';
    }

    dd($gib->getUserData());

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}