<?php

use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testTotalWithTaxes()
    {
        $invoice = InvoiceModel::new(
            vknTckn         : '',
            aliciAdi        : '',
            aliciSoyadi     : '',
            mahalleSemtIlce : '',
            sehir           : '',
            ulke            : '',
        );
    
        $invoice->addItem(
            InvoiceItemModel::new(
                malHizmet    : '',
                miktar       : 2,
                birim        : Unit::Adet,
                birimFiyat   : 10,
                kdvOrani     : 18,
                iskontoOrani : 10,
            )->eachWith(Tax::cases(), fn ($tax, $i) => $tax->addTax($i, 50))
        );

        $invoice = $invoice->export();

        $this->assertEquals($invoice['matrah'],                   18);
        $this->assertEquals($invoice['malhizmetToplamTutari'],    20);
        $this->assertEquals($invoice['hesaplanankdv'],            27.54);
        $this->assertEquals($invoice['vergilerToplami'],          261.54);
        $this->assertEquals($invoice['vergilerDahilToplamTutar'], 279.54);
        $this->assertEquals($invoice['odenecekTutar'],            211.77);
    }
}