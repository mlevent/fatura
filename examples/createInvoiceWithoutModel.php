<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;
use Ramsey\Uuid\Uuid;

try {

    $invoice = [
        'tarih'                    => date('d/m/Y'),
        'saat'                     => date('H:m:s'),
        'vknTckn'                  => '11111111111',
        'vergiDairesi'             => 'Nilüfer',
        'faturaTipi'               => 'TEVKIFAT',
        'aliciAdi'                 => 'MERT',
        'aliciSoyadi'              => 'LEVENT',
        'adres'                    => 'Sancı Sk. Lalezar Apt. No:44/A',
        'mahalleSemtIlce'          => 'Nilüfer',
        'sehir'                    => 'Bursa',
        'ulke'                     => 'Türkiye',
        'iadeTable'                => [
            [
                'faturaNo'          =>  'GIB2022000000003',
                'duzenlenmeTarihi'  => '12/10/1988'
            ]
        ],
        'malHizmetTable'           => [
            [
                'malHizmet'         => 'Danışmanlık Ücreti',
                'miktar'            => 1,
                'birim'             => 'C62',
                'birimFiyat'        => 2500,
                'kdvOrani'          => 18,
            ]
        ]
    ];

    //$invoice['malHizmetTable'][0] = InvoiceItemModel::import($invoice['malHizmetTable'][0]);
    
    dd(InvoiceModel::use($invoice)->export());

    //dd(new invoiceModel(...$invoice));

    $gib = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice)) {
        echo $invoice['faturaUuid'];
    }

    dd($invoice);

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}