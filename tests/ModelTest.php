<?php

use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\ProducerReceiptItemModel;
use Mlevent\Fatura\Models\ProducerReceiptModel;
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

    public function testInvoice()
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
            (new InvoiceItemModel(
                malHizmet    : '',
                miktar       : 444,
                birimFiyat   : 0.1261,
                kdvOrani     : 8,
                iskontoOrani : 15,
                iskontoTipi  : 'Arttırım',
            ))->addTax(Tax::EnerjiFonu, 12)
        );

        $invoice->addItem(
            (new InvoiceItemModel(
                malHizmet    : '',
                miktar       : 123,
                birimFiyat   : 1.2352,
                kdvOrani     : 18,
                iskontoOrani : 7,
            ))->addTax(Tax::Damga,      5)
              ->addTax(Tax::EnerjiFonu, 9)
        );

        $invoice = $invoice->export();

        $this->assertEquals($invoice['matrah'],                   205.68);
        $this->assertEquals($invoice['malhizmetToplamTutari'],    207.92);
        $this->assertEquals($invoice['toplamIskonto'],            2.24);
        $this->assertEquals($invoice['hesaplanankdv'],            33.49);
        $this->assertEquals($invoice['vergilerToplami'],          61);
        $this->assertEquals($invoice['vergilerDahilToplamTutar'], 266.68);
        $this->assertEquals($invoice['odenecekTutar'],            266.68);
    }

    public function testProducerReceipt()
    {
        $invoice = ProducerReceiptModel::new(
            vknTckn         : '',
            aliciAdi        : '',
            aliciSoyadi     : '',
        );

        $invoice->addItem(
            (new ProducerReceiptItemModel(
                malHizmet     : '',
                miktar        : 21,
                birimFiyat    : 0.2541,
                gvStopajOrani : 20,
            ))->addTax(Tax::MeraFonu, 12)
        );

        $invoice->addItem(
            (new ProducerReceiptItemModel(
                malHizmet     : '',
                miktar        : 111,
                birimFiyat    : 12.221,
                gvStopajOrani : 10,
            ))->addTax(Tax::MeraFonu,    8)
              ->addTax(Tax::BorsaTescil, 11)
        );

        $invoice = $invoice->export();

        $this->assertEquals($invoice['malhizmetToplamTutari'],    1361.87);
        $this->assertEquals($invoice['vergilerDahilToplamTutar'], 1361.87);
        $this->assertEquals($invoice['odenecekTutar'],            993.63);
    }
}