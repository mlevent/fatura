<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\UserDataModel;

try {

    // Gib Portal Bağlantısı
    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    // Kullanıcı Bilgilerini Gib Portal'dan Getir
    $user = $gib->getUserData();

    // Veriyi Modele Aktar
    $user = UserDataModel::import($user);

    // Güncellenecek Alanlar
    $user->apartmanAdi  = 'Lale Apartmanı';
    $user->kapiNo       = '12';
    $user->vergiDairesi = 'Bursa';

    // Kullanıcı Bilgilerini Güncelle
    if ($gib->updateUserData($user)) {
        echo 'Bilgiler başarıyla güncellendi.';
    }

    // Güncel Kullanıcı Bilgileri
    dd($gib->getUserData(), false);

    // Gib Portal Oturumunu Sonlandırma
    $gib->logout();

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}