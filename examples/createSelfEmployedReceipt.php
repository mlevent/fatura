<?php declare(strict_types=1); error_reporting(E_ALL); require dirname(__DIR__).'/vendor/autoload.php';

use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Exceptions\FaturaException;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\SelfEmployedReceiptItemModel;
use Mlevent\Fatura\Models\SelfEmployedReceiptModel;

try {

    //die();
    
    $invoice = SelfEmployedReceiptModel::new(
        tarih          : '20/10/2022',  // Opsiyonel @string (dd/mm/yyyy) @default=now
        saat           : '14:25:34',    // Opsiyonel @string (hh/mm/ss)   @default=now
        paraBirimi     : Currency::USD, // Opsiyonel @Currency            @default=Currency::TRY
        dovizKuru      : 18.56,         // Opsiyonel @float               @default=0
        vknTckn        : '32669381008', // Zorunlu   @string                
        aliciAdi       : 'Mert',        // Zorunlu   @string
        aliciSoyadi    : 'Levent',      // Zorunlu   @string
        aliciUnvan     : '',            // Opsiyonel @string
        adres          : '',            // Opsiyonel @string
        binaAdi        : '',            // Opsiyonel @string
        binaNo         : '',            // Opsiyonel @string
        kapiNo         : '',            // Opsiyonel @string
        kasabaKoy      : '',            // Opsiyonel @string
        mahalleSemtIlce: '',            // Opsiyonel @string
        sehir          : '',            // Opsiyonel @string
        postaKodu      : '',            // Opsiyonel @string
        ulke           : 'Türkiye',     // Zorunlu   @string
        vergiDairesi   : '',            // Opsiyonel @string
        aciklama       : '',            // Opsiyonel @string
        kdvTahakkukIcin: false,         // Opsiyonel @boolean             @default=false
    );

    $invoice->addItem(
        SelfEmployedReceiptItemModel::new(
            neIcinAlindigi  : 'Dava Vekilliği', // Zorunlu   @string
            brutUcret       : 100,              // Zorunlu   @float
            kdvOrani        : 18,               // Zorunlu   @int
            gvStopajOrani   : 20,               // Opsiyonel @int
            kdvTevkifatOrani: 40,               // Opsiyonel @int
        )
    );

    $gib = (new Gib(DocumentType::SelfEmployedReceipt))
            ->setTestCredentials('33333310', '1')
            ->login();

    if ($gib->createDraft($invoice)) {
        echo $invoice->getUuid();
    }

} catch(FaturaException $e){
    
    dd($e->getMessage(), false);
    dd($e->getResponse(), false);
    dd($e->getRequest(), false);
}