<?php

use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceItemModel;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\UserDataModel;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    public function testService()
    {
        $service = new Gib;
        
        // Set credentials
        $service->setCredentials('333333', '666666');
        $this->assertEquals($service->getCredentials(), ['username' => 333333, 'password' => 666666]);

        // Login with test user
        $service->setTestCredentials()->login();
        $this->assertIsString($service->getToken());

        // Logout
        $this->assertTrue($service->logout());
    }

    public function testGetAll()
    {
        $service = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

        // Documents
        $documents = $service->getAll('01/01/2022', '01/02/2022');
        $this->assertIsArray($documents);

        // Document Summary Keys
        $this->assertEquals(array_keys($documents[0]), [
            'belgeNumarasi', 
            'aliciVknTckn', 
            'aliciUnvanAdSoyad', 
            'belgeTarihi', 
            'belgeTuru', 
            'onayDurumu', 
            'ettn'
        ]);
    }

    public function testUpdateUserData()
    {
        $service = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

        $userData = UserDataModel::import($service->getUserData());

        $userData->apartmanAdi  = 'Lale Apartmanı';
        $userData->kapiNo       = '12';
        $userData->vergiDairesi = 'Bursa';

        $updateUserData = $service->updateUserData($userData);
        $getNewUserData = $service->getUserData();
        
        $this->assertTrue($updateUserData);
        $this->assertEquals($getNewUserData['apartmanAdi'], 'Lale Apartmanı');
    }

    public function testCreateInvoice()
    {
        $invoice = InvoiceModel::new(
            vknTckn         : '11111111111',
            vergiDairesi    : 'Çekirge VD',
            aliciUnvan      : 'Levent İnşaat Malzemeleri San. Tic. Ltd. Şti.',
            aliciAdi        : 'Mert',
            aliciSoyadi     : 'Levent',
            mahalleSemtIlce : 'Nilüfer',
            sehir           : 'Bursa',
            ulke            : 'Türkiye',
        );
    
        $invoice->addItem(
            InvoiceItemModel::new(
                malHizmet    : 'Muhtelif Oyuncak',
                miktar       : 12,
                birim        : Unit::Adet,
                birimFiyat   : 124.52,
                kdvOrani     : 18,
                iskontoOrani : 33,
            )
        );
    
        $invoice->addItem(
            InvoiceItemModel::new(
                malHizmet  : 'Muhtelif Kırtasiye',
                miktar     : 3,
                birim      : Unit::Adet,
                birimFiyat : 17.56,
                kdvOrani   : 8,
            )
        );

        $service = (new Gib())
            ->setTestCredentials('33333310', '1')
            ->login();

        $invoiceData = $invoice->export();

        $isCreated = $service->createDraft($invoiceData);
        $createdInvoice = $service->getDocument($invoice->getUuid());

        $this->assertTrue($isCreated);

        $this->assertEquals($createdInvoice['malhizmetToplamTutari'],    $invoiceData['malhizmetToplamTutari']);
        $this->assertEquals($createdInvoice['matrah'],                   $invoiceData['matrah']);
        $this->assertEquals($createdInvoice['toplamIskonto'],            $invoiceData['toplamIskonto']);
        $this->assertEquals($createdInvoice['vergilerDahilToplamTutar'], $invoiceData['vergilerDahilToplamTutar']);
        $this->assertEquals($createdInvoice['odenecekTutar'],            $invoiceData['odenecekTutar']);
    }
}