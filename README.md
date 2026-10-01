<h1 align="center">🧾 Fatura</h1>
<p align="center">GİB e-Arşiv portal üzerinde; e-Fatura, e-SMM, e-Müstahsil oluşturma, düzenleme, imzalama vb. işlemlere olanak tanır.</p>

<p align="center">
<img src="https://img.shields.io/packagist/dependency-v/mlevent/fatura/php?style=plastic"/>
<img src="https://img.shields.io/packagist/v/mlevent/fatura?style=plastic"/>
<img src="https://img.shields.io/github/last-commit/mlevent/fatura?style=plastic"/>
<img src="https://img.shields.io/github/issues/mlevent/fatura?style=plastic"/>
<img src="https://img.shields.io/packagist/dt/mlevent/fatura?style=plastic"/>
<img src="https://img.shields.io/github/stars/mlevent/fatura?style=plastic"/>
<img src="https://img.shields.io/github/forks/mlevent/fatura?style=plastic"/>
</p>

![Fatura](https://i.imgur.com/RzZTuev.png)

## Başlarken

Bu paket GİB'e tabi şahıs şirketi ya da şirket hesapları ile çalışır ve bu kişiler adına resmi fatura/makbuz oluşturur. GİB e-Arşiv portala tarayıcınızdan giriş yapmak için aşağıdaki linkleri kullanabilirsiniz;

- https://earsivportaltest.efatura.gov.tr/login.jsp
- https://earsivportal.efatura.gov.tr/intragiris.html

> Kullanıcı kodu ve parola bilgilerini muhasebecinizden ya da GİB - İnteraktif Vergi Dairesi'nden edinebilirsiniz.

Fatih Kadir Akın'ın [fatura.js](https://github.com/f/fatura/) paketinden faydalanılmıştır.

## Kurulum

🛠️ Paketi composer ile projenize dahil edin;

```bash
composer require mlevent/fatura
```

## 🎉 Özellikler

- [Api Bağlantısı](#api-bağlantısı)
- [Belge Oluşturma](#belge-oluşturma)
  - [e-Fatura](#e-fatura)
    - [Satış](#satış)
    - [İade](#i̇ade)
    - [Tevkifat](#tevkifat)
    - [İstisna](#i̇stisna)
    - [Özel Matrah](#özel-matrah)
  - [e-Müstahsil](#e-müstahsil)
  - [e-SMM](#e-smm)
- [Belge Güncelleme](#belge-günceleme)
- [Belge Silme](#belge-silme)
- [Belge İmzalama](#belge-i̇mzalama)
  - [Sms ile İmzalama](#sms-ile-i̇mzalama)
- [Belge Listeleme](#belge-listeleme)
  - [Düzenlenen Belgeler](#düzenlenen-belgeler)
  - [Adıma Düzenlenen Belgeler](#adıma-düzenlenen-belgeler)
  - [Belge Detayları](#belge-detayları)
  - [HTML Çıktı Alma](#html-çıktı-alma)
  - [Belge İndirme Adresi](#belge-i̇ndirme-adresi)
  - [Belgeyi Sunucuya Kaydetme](#belgeyi-sunucuya-kaydetme)
- [Vergiler](#vergi-ekleme)
  - [Vergi Ekleme](#vergi-ekleme)
  - [Vergi Listesi](#vergi-listesi)
  - [Vergiler ve Toplamlar](#vergiler-ve-toplamlar)
- [İptal/İtiraz Talepleri](#i̇ptali̇tiraz-talepleri)
- [GİB Profil Bilgileri](#gi̇b-profil-bilgileri)
- [Mükellef Sorgulama](#mükellef-bilgileri)
- [Birimler](#birimler)

## 🔗Api Bağlantısı

### Test Kullanıcısı

e-Arşiv portal üzerinden yeni bir test kullanıcısı oluşturmak ve token almak için;

```php
use Mlevent\Fatura\Gib;

$gib = (new Gib)->setTestCredentials()
                ->login();

echo $gib->getToken();
```

Token ve kullanıcı bilgilerine ulaşmak için;

```php
print_r($gib->getToken());
print_r($gib->getCredentials());
```

### Gerçek Kullanıcı

e-Arşiv portal kullanıcı bilgilerinizi `setCredentials` metodunu kullanarak tanımlayabilirsiniz.

```php
use Mlevent\Fatura\Gib;

$gib = (new Gib)->setCredentials('Kullanıcı Kodu', 'Parola')
                ->login();

echo $gib->getToken();
```

Bilgilerinizi doğrudan `login` metoduyla da tanımlayabilirsiniz.

```php
$gib->login('Kullanıcı Kodu', 'Parola')
```

> Not: Token değerini herhangi bir yerde kullanmanız gerekmeyecek.

### Oturum Sonlandırma

Herhangi bir kısıtlama veya oturum sorunu yaşamamak adına, işlemlerden sonra oturumu sonlandırabilir ya da `setToken` yöntemini kullanabilirsiniz.

```php
$gib->logout();
```

Bir kez token aldıktan sonra token süresi sonlanana kadar login/logout olmadan da işlem yapılabilir.

```php
$gib->setToken('f72b59eac1366d3115d80fa9dc971fc05daa7aaeea2c4715efce537c6d052e0cf0cdcd28db2f5928bf35d9590f6143f8e58bda5a5fb15ab67964905a4363daf0');
```

> Token süresi sonlandığında yeni token alınmalıdır.

## 📃Belge Oluşturma

Model kullanırken named arguments (adlandırılmış değişkenler) veya dizilerle çalışabilirsiniz. Oluşturulan belgeler, daha sonra imzalanmak üzere e-Arşiv portalda taslaklara kaydedilir.

> `faturaUuid` ve `belgeNumarasi` belirtildiyse; portalda bu bilgilerle eşleşen belge güncellenir, diğer durumda portal üzerinde yeni bir belge oluşturulur. [Belge Güncelleme](#belge-günceleme) sayfasını kontrol edin.

## e-Fatura

Kütüphaneyi kullanarak aşağıdaki fatura türleri ile çalışabilirsiniz;

- Satış
- İade
- Tevkifat
- İstisna
- Özel Matrah

> Döviz cinsinden fatura düzenlemek için modelde `paraBirimi` ve `dovizKuru` parametreleri kullanılmalıdır.

Belge oluştururken model kullanmak istemiyorsanız `/examples` klasörü altındaki [createInvoiceWithoutModel.php](https://github.com/mlevent/fatura/blob/master/examples/createInvoiceWithoutModel.php) dosyasındaki örneği inceleyebilirsiniz.

### Satış

Satış faturası oluşturabilmek için, **faturaTipi** `InvoiceType::Satis` gönderilmelidir.

```php
use Mlevent\Fatura\Enums\Currency;
use Mlevent\Fatura\Enums\InvoiceType;
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceItemModel;

// Fatura detayları
$invoice = new InvoiceModel(
    tarih            : '20/10/2022',       // ☑️ Opsiyonel @string      @default=(dd/mm/yyyy)
    saat             : '23:50:48',         // ☑️ Opsiyonel @string      @default=(hh/mm/ss)
    paraBirimi       : Currency::USD,      // ☑️ Opsiyonel @Currency    @default=Currency::TRY
    dovizKuru        : 18.56,              // ☑️ Opsiyonel @float       @default=0
    faturaTipi       : InvoiceType::Satis, // ☑️ Opsiyonel @InvoiceType @default=InvoiceType::Satis
    vknTckn          : '11111111111',      // ✴️ Zorunlu   @string
    vergiDairesi     : '',                 // ✅ Opsiyonel @string
    aliciUnvan       : '',                 // ✅ Opsiyonel @string
    aliciAdi         : 'Mert',             // ✴️ Zorunlu   @string
    aliciSoyadi      : 'Levent',           // ✴️ Zorunlu   @string
    mahalleSemtIlce  : 'Nilüfer',          // ✴️ Zorunlu   @string
    sehir            : 'Bursa',            // ✴️ Zorunlu   @string
    ulke             : 'Türkiye',          // ✴️ Zorunlu   @string
    adres            : '',                 // ✅ Opsiyonel @string
    siparisNumarasi  : '',                 // ✅ Opsiyonel @string
    siparisTarihi    : '',                 // ✅ Opsiyonel @string
    irsaliyeNumarasi : '',                 // ✅ Opsiyonel @string
    irsaliyeTarihi   : '',                 // ✅ Opsiyonel @string
    fisNo            : '',                 // ✅ Opsiyonel @string
    fisTarihi        : '',                 // ✅ Opsiyonel @string
    fisSaati         : '',                 // ✅ Opsiyonel @string
    fisTipi          : '',                 // ✅ Opsiyonel @string
    zRaporNo         : '',                 // ✅ Opsiyonel @string
    okcSeriNo        : '',                 // ✅ Opsiyonel @string
    binaAdi          : '',                 // ✅ Opsiyonel @string
    binaNo           : '',                 // ✅ Opsiyonel @string
    kapiNo           : '',                 // ✅ Opsiyonel @string
    kasabaKoy        : '',                 // ✅ Opsiyonel @string
    postaKodu        : '',                 // ✅ Opsiyonel @string
    tel              : '',                 // ✅ Opsiyonel @string
    fax              : '',                 // ✅ Opsiyonel @string
    eposta           : '',                 // ✅ Opsiyonel @string
    not              : '',                 // ✅ Opsiyonel @string
);

// Ürün/Hizmetler
$invoice->addItem(
    new InvoiceItemModel(
        malHizmet     : 'Çimento',  // ✴️ Zorunlu   @string
        miktar        : 3,          // ✴️ Zorunlu   @float
        birim         : Unit::M3,   // ☑️ Opsiyonel @Unit @default=Unit::Adet
        birimFiyat    : 1259,       // ✴️ Zorunlu   @float
        kdvOrani      : 18,         // ✴️ Zorunlu   @float
        iskontoOrani  : 25,         // ✅ Opsiyonel @float
        iskontoTipi   : 'Arttırım', // ☑️ Opsiyonel @string @default=İskonto
        iskontoNedeni : '',         // ✅ Opsiyonel @string
    )
);

$gib = (new Gib)->login('333333054', '******');

if ($gib->createDraft($invoice)) {
    echo $invoice->getUuid(); // 04e17398-468d-11ed-b3cb-4ccc6ae28384
}

$gib->logout();
```

Yeni taslaklarda ETTN, oluşturma yanıtından veya işlem öncesinde bulunmayan belgelerin
detayları karşılaştırılarak alınır. Aynı alıcının son belgesi doğrudan seçilmez.
Alıcı, tarih, tutar ve gönderilen saat, para birimi, sipariş/not ve satır bilgileri
eşleşmelidir. Liste üzerinden bulunan aday iki ardışık okumada doğrulanır.

`DraftPreflightException`, oluşturma isteği gönderilmeden önce ön kontrolün
başarısız olduğunu belirtir. `DraftCorrelationException` durumunda belge GİB'de
oluşmuş olabilir; aynı isteği tekrar göndermeden önce portal üzerinden uzlaştırın.
Başarısız yeni taslak eşleştirmesinde modelin UUID'si ve `lastId()` boş kalır.

Aynı makinedeki işlemler hesap bazında dosya kilidi kullanır. Farklı sunuculardan
veya portal üzerinden aynı hesapla eşzamanlı oluşturma yapılıyorsa ortak bir
kilit/kuyruk uygulama tarafından sağlanmalıdır. GİB'in ETTN döndürmediği yanıtlarda
aynı içerikli, henüz listelenmeyen dış işlemler için atomik eşleştirme garantisi yoktur.

### İade

İade faturası oluşturabilmek için, **faturaTipi** `InvoiceType::Iade` gönderilmeli; iadeye konu faturalar **addReturnItem** metoduyla faturaya eklenmelidir.

```php
// Fatura detayları
$invoice = new InvoiceModel(
    faturaTipi: InvoiceType::Iade,
    ...
);

// İade faturası için iadeye konu faturalar
$invoice->addReturnItem(
    new InvoiceReturnItemModel(
        faturaNo        : 'GIB2022000001416',
        duzenlenmeTarihi: '31/12/2022'
    )
);
```

### Tevkifat

Tevkifatlı fatura oluşturabilmek için, **faturaTipi** `InvoiceType::Tevkifat` gönderilmelidir.

```php
// Fatura detayları
$invoice = new InvoiceModel(
    faturaTipi: InvoiceType::Tevkifat,
    ...
);

// Ürün/Hizmetler
$invoice->addItem(
    new InvoiceItemModel(
        tevkifatKodu: 613, // 613 - Çevre, Bahçe ve Bakım Hizmetleri [KDVGUT-(I/C-2.1.3.2.11)]
        ...
    )
);
```

Tevkifat kodlarına ait liste çıktısını almak için;

```php
print_r(Tax::KDVTevkifat->codes());

Array
(
    [601] => Array
        (
            [rate] => 40
            [name] => Yapım İşleri ile Bu İşlerle Birlikte İfa Edilen Mühendislik-Mimarlık ve Etüt-Proje Hizmetleri [KDVGUT-(I/C-2.1.3.2.1)]
        )
    ...
```

### İstisna

İstisna fatura oluşturabilmek için, **faturaTipi** `InvoiceType::Istisna` gönderilmeli; 12 haneli **gtip** kodu faturaya ait ürün/hizmet eklenirken belirtilebilir.

```php
// Fatura detayları
$invoice = new InvoiceModel(
    faturaTipi: InvoiceType::Istisna,
    ...
);

// Ürün/Hizmetler
$invoice->addItem(
    new InvoiceItemModel(
        gtip: '080810100000',
        ...
    )
);
```

### Özel Matrah

Özel matrah fatura oluşturabilmek için, **faturaTipi** `InvoiceType::OzelMatrah` gönderilmeli; faturaya ait ürün/hizmet eklenirken **ozelMatrahNedeni** ve **ozelMatrahTutari** belirtilmelidir.

```php
// Fatura detayları
$invoice = new InvoiceModel(
    faturaTipi: InvoiceType::OzelMatrah,
    ...
);

// Ürün/Hizmetler
$invoice->addItem(
    new InvoiceItemModel(
        ...
        ozelMatrahNedeni: 805, // 805 - Altından Mamül veya Altın İçeren Ziynet Eşyaları İle Sikke Altınların Teslimi
        ozelMatrahTutari: 1250,
    )
);
```

Özel matrah nedenlerine ait liste çıktısını almak için;

```php
print_r(InvoiceType::OzelMatrah->reasons());

Array
(
    [801] => Milli Piyango, Spor Toto vb. Oyunlar
    [802] => At yarışları ve diğer müşterek bahis ve talih oyunları
    [803] => Profesyonel Sanatçıların Yer Aldığı Gösteriler, Konserler, Profesyonel Sporcuların Katıldığı Sportif Faaliyetler, Maçlar, Yarışlar ve Yarışmalar
    ...
)
```

## e-Müstahsil

Müstahsil makbuzu ile çalışılacaksa, Gib sınıfı başlatılırken `DocumentType::ProducerReceipt` başlangıç parametresi olarak gönderilmelidir.

```php
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\ProducerReceiptModel;
use Mlevent\Fatura\Models\ProducerReceiptItemModel;

// Müstahsil Makbuzu Detayları
$producerReceipt = new ProducerReceiptModel(
    tarih       : '20/10/2022',  // ☑️ Opsiyonel @string @default=(dd/mm/yyyy)
    saat        : '23:50:48',    // ☑️ Opsiyonel @string @default=(hh/mm/ss)
    vknTckn     : '11111111111', // ✴️ Zorunlu   @string
    aliciAdi    : 'Walter',      // ✴️ Zorunlu   @string
    aliciSoyadi : 'Bishop',      // ✴️ Zorunlu   @string
    sehir       : '',            // ✅ Opsiyonel @string
    websitesi   : '',            // ✅ Opsiyonel @string
    not         : '',            // ✅ Opsiyonel @string
    teslimTarihi: '',            // ✅ Opsiyonel @string
);

// Ürün/Hizmetler
$producerReceipt->addItem(
    new ProducerReceiptItemModel(
        malHizmet    : 'Yazılım Hizmeti', // ✴️ Zorunlu @string
        miktar       : 3,                 // ✴️ Zorunlu @float
        birim        : Unit::Saat,        // ✴️ Zorunlu @Unit
        birimFiyat   : 100,               // ✴️ Zorunlu @float
        gvStopajOrani: 20                 // ✴️ Zorunlu @int
    )
);

$service = (new Gib(DocumentType::ProducerReceipt))->login('333333054', '******');

if ($service->createDraft($producerReceipt)) {
    echo $producerReceipt->getUuid(); // 04e17398-468d-11ed-b3cb-4ccc6ae28384
}

$service->logout();
```

## e-SMM

Serbest meslek makbuzu ile çalışılacaksa, Gib sınıfı başlatılırken `DocumentType::SelfEmployedReceipt` başlangıç parametresi olarak gönderilmelidir.

```php
use Mlevent\Fatura\Enums\Unit;
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\SelfEmployedReceiptModel;
use Mlevent\Fatura\Models\SelfEmployedReceiptItemModel;

// Serbest Meslek Makbuzu
$selfEmployedReceipt = new SelfEmployedReceiptModel(
    tarih          : '20/10/2022',  // ☑️ Opsiyonel @string   @default=(dd/mm/yyyy)
    saat           : '14:25:34',    // ☑️ Opsiyonel @string   @default=(hh/mm/ss)
    paraBirimi     : Currency::USD, // ☑️ Opsiyonel @Currency @default=Currency::TRY
    dovizKuru      : 18.56,         // ☑️ Opsiyonel @float    @default=0
    vknTckn        : '11111111111', // ✴️ Zorunlu   @string
    aliciAdi       : 'Walter',      // ✴️ Zorunlu   @string
    aliciSoyadi    : 'Bishop',      // ✴️ Zorunlu   @string
    aliciUnvan     : '',            // ✅ Opsiyonel @string
    adres          : '',            // ✅ Opsiyonel @string
    binaAdi        : '',            // ✅ Opsiyonel @string
    binaNo         : '',            // ✅ Opsiyonel @string
    kapiNo         : '',            // ✅ Opsiyonel @string
    kasabaKoy      : '',            // ✅ Opsiyonel @string
    mahalleSemtIlce: '',            // ✅ Opsiyonel @string
    sehir          : '',            // ✅ Opsiyonel @string
    postaKodu      : '',            // ✅ Opsiyonel @string
    ulke           : 'Türkiye',     // ✴️ Zorunlu   @string
    vergiDairesi   : '',            // ✅ Opsiyonel @string
    aciklama       : '',            // ✅ Opsiyonel @string
    kdvTahakkukIcin: false,         // ☑️ Opsiyonel @boolean  @default=false
);

$selfEmployedReceipt->addItem(
    new SelfEmployedReceiptItemModel(
        neIcinAlindigi  : 'Dava Vekilliği', // ✴️ Zorunlu   @string
        brutUcret       : 100,              // ✴️ Zorunlu   @float
        kdvOrani        : 18,               // ✴️ Zorunlu   @float
        gvStopajOrani   : 0,                // ✅ Opsiyonel @int
        kdvTevkifatOrani: 0,                // ✅ Opsiyonel @int
    )
);

$service = (new Gib(DocumentType::SelfEmployedReceipt))->login('333333054', '******');

if ($service->createDraft($producerReceipt)) {
    echo $producerReceipt->getUuid(); // 04e17398-468d-11ed-b3cb-4ccc6ae28384
}

$service->logout();
```

## Not Ekleme

Belgelere not eklemek için, `setNote` metodunu kullanabilirsiniz. Not eklenmemiş belgelere otomatik olarak, "yazı ile toplam ödenecek tutar" not olarak eklenir.

```php
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceItemModel;

$invoice = new InvoiceModel(...);

$invoice->addItem(
    new InvoiceItemModel(...),
    new InvoiceItemModel(...),
);

$invoice->setNote(number_to_words($invoice->getPaymentTotal())); // ALTI YÜZ OTUZ DÖRT TÜRK LİRASI ALTMIŞ BİR KURUŞ
```

## 💸Vergi Ekleme

Belgedeki hizmetlere `addTax` metodunu kullanarak vergi ekleyebilirsiniz. Vergiler doğrudan belgeye eklenemez, yalnızca öğe modeli üzerinden her bir öğeye ayrı ayrı eklenebilir.

```php
use Mlevent\Fatura\Enums\Tax;
use Mlevent\Fatura\Models\InvoiceItemModel;

$invoiceItem = new InvoiceItemModel(
    malHizmet  : 'Çimento',
    birim      : Unit::Ton,
    miktar     : 3,
    birimFiyat : 1259,
    kdvOrani   : 18,
);

// Hizmete vergi ekleme
$invoiceItem->addTax(Tax::Damga,    15)  // %15 damga vergisi
            ->addTax(Tax::GVStopaj, 25); // %25 gelir vergisi

// Vergi kodu kullanarak vergi ekleme
$invoiceItem->addTax(Tax::from(1047), 15); // %15 damga vergisi
```

### Vergi Listesi

Vergi listesine ulaşmak için `cases` statik metodunu kullanabilirsiniz;

```php
use Mlevent\Fatura\Enums\Tax;

// Vergiler
foreach (Tax::cases() as $tax) {
    echo $tax->value;   // 4071
    echo $tax->name;    // ElkHavagazTuketim
    echo $tax->alias(); // Elektrik Havagaz Tüketim Vergisi
}
```

### Vergiler ve Toplamlar

Belgeye eklenen öğelere ulaşmak için `getItems` metodunu kullanabilirsiniz;

```php
use Mlevent\Fatura\Models\InvoiceModel;
use Mlevent\Fatura\Models\InvoiceItemModel;

$invoice = new InvoiceModel(...);

$invoice->addItem(
    new InvoiceItemModel(...),
    new InvoiceItemModel(...),
);

// Her bir öğeye ait vergiler
foreach ($invoice->getItems() as $item) {

    // Öğeye eklenen vergiler toplamı
    print_r($item->totalTaxAmount());

    // Öğeye eklenen vergilere ait kdv toplamı
    print_r($item->totalTaxVat());

    // Öğeye eklenen vergiler
    print_r($item->getTaxes());

    // Öğeye ait toplamlar
    print_r($item->getTotals());
}

// Belgeye ait vergiler
print_r($invoice->getTaxes());

// Belgeye ait toplamlar
print_r($invoice->getTotals());
```

## Belge Günceleme

Fatura oluşturulurken `faturaUuid` ve `belgeNumarasi` belirtildiyse; portalda bu bilgilerle eşleşen belge güncellenir, diğer durumda portal üzerinde yeni bir belge oluşturulur.

```php
// Fatura detayları
$invoice = new InvoiceModel(
    uuid          : '04e17398-468d-11ed-b3cb-4ccc6ae28384',
    belgeNumarasi : 'GIB2022000000003',
    ...
);
```

Belgeleri güncellemek için bir diğer yöntem; önce düzenlenecek belgeyi getirmek ve daha sonra düzenlenecek alanlarla birlikte güncelleme isteği göndermektir. Örneğin oluşturulan faturada yalnızca alıcı bilgilerini güncellemek isteyebilirsiniz;

```php
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\InvoiceModel;

$gib = (new Gib)->login('333333054', '******');

$invoice = InvoiceModel::importFromApi(
    $gib->getDocument('c4e9e0a2-4788-11ed-bbd4-4ccc6ae28384')
);

$invoice->aliciAdi    = 'Nureddin';
$invoice->aliciSoyadi = 'Nebati';
$invoice->adres       = 'Bankalar Cd. Faiz Sk. No:128/A';

// Faturayı güncelle
if ($gib->createDraft($invoice)) {
    echo $invoice->getUuid(); // c4e9e0a2-4788-11ed-bbd4-4ccc6ae28384
}
```

> `addItem` metodunun kullanılması durumunda içe aktarılmış öğeler silinecektir.

## Belge Silme

Taslak belgeleri silmek için `deleteDraft` metodu kullanılmalıdır. Metod bir dizi kabul eder. Gönderilecek dizi içerisinde silinecek belge veya belgelere ait `uuid` bilgisi bulunması gerekir.

Aşağıdaki örnek, bilinen bir tarih aralığındaki tüm taslak belgeleri siler.

```php
$fetchToDelete = $gib->selectColumn('ettn')
                     ->onlyUnsigned()
                     ->getAll('10/10/2022', '10/15/2022');

if ($gib->deleteDraft($fetchToDelete)) {
    echo "{$gib->rowCount()} adet belge silindi!"; // x adet belge silindi
}
```

Belirsiz bir tarih aralığındaki taslak belgeler silinmek isteniyorsa;

```php
$setToDelete = [
    '90559052-8bd0-4f68-a733-12157cf53cfb',
    '521ce2b1-290c-45fa-8312-d455672289ef',
];

if ($gib->deleteDraft($setToDelete)) {
    echo "{$gib->rowCount()} adet belge silindi!"; // 2 adet belge silindi
}
```

## Belge İmzalama

☢️ Belge imzalama, faturanın/makbuzun kesilmesi işlemidir ve vergi sisteminde mali veri oluşturur. Belge imzalandıktan sonra üzerinde değişiklik yapılamaz ve silinemez. Bu nedenle dikkatli kullanınız.

### 📲SMS ile İmzalama

> SMS doğrulama işlemleri test hesaplarıyla gerçekleştirilemez.

SMS doğrulamasına başlamak için `startSmsVerification` yöntemi kullanılmalıdır. Yöntem portalda kayıtlı gsm numarasına bir doğrulama kodu gönderecek ve imzalama işlemi için daha sonra kullanacağınız bir ID döndürecektir.

```php
$operationId = $gib->startSmsVerification();
```

Doğrulama işlemini tamamlamak için kullanılacak `completeSmsVerification` yöntemine; SMS ile gelen doğrulama kodu, SMS doğrulaması başlatılırken alınan Operasyon ID'si ve onaylanacak belgelere ait UUID değerlerinin bulunduğu bir dizi olmak üzere 3 farklı parametre gönderilmelidir.

```php
// Portaldan belirli bir tarih aralığındaki tüm onaysız belgeleri getir
$setToSign = $gib->selectColumn('ettn')
                 ->onlyUnsigned()
                 ->getAll('01/10/2022', '15/10/2022');

// Onaylanacak belgelere ait UUID'leri kendiniz de belirtebilirsiniz
$setToSign = [
    '2e989428-63ca-11ed-b617-4ccc6ae28384',
    '54c5df01-038b-4e01-973d-cd31e4a547f3',
];

// Belgeleri onayla
if ($gib->completeSmsVerification($smsCode, $operationId, $setToSign)) {
    echo "{$gib->rowCount()} adet belge onaylandı!"; // x adet belge onaylandı
}
```

## Belge Listeleme

Oluşturulan taslak belgeleri, tarih aralığı belirtmek koşuluyla farklı şekillerde listeleyebilirsiniz.

`getAll`, `getAllIssuedToMe` ve `getRequests`, uzun aralıkları GİB'in kabul ettiği
en fazla 7 günlük tarih farkına sahip sorgulara böler. Filtre, sıralama ve limit
birleştirilmiş sonuca uygulanır. Aralıklardan biri başarısızsa eksik liste dönmez.

### Düzenlenen Belgeler

```php
$documents = $gib->getAll('01/09/2022', '15/09/2022');
```

Bu örnek, aşağıdaki şu diziyi döndürecektir;

```php
Array
(
    [0] => Array
        (
            [belgeNumarasi] => GIB2022000000356
            [aliciVknTckn] => 11111111111
            [aliciUnvanAdSoyad] => Mert Levent
            [belgeTarihi] => 09-10-2022
            [belgeTuru] => FATURA
            [onayDurumu] => Onaylanmadı
            [ettn] => c4e9e0a2-4788-11ed-bbd4-4ccc6ae28384
        )
    ...
)
```

### Adıma Düzenlenen Belgeler

> Bu metod artık stabil çalışmıyor gibi görünüyor. Belli bir tarihten sonra adınıza düzenlenmiş belgeler bir süredir GİB Portal'dan alınamıyor.

İki tarih arasındaki gelen faturaları (GİB portaldaki adıyla Adıma Düzenlenen Belgeler) listeler.

```php
$documents = $gib->getAllIssuedToMe('01/09/2022', '15/09/2022');
```

### Belge Detayları

Portal üzerinde kayıtlı belge detaylarına ulaşmak için;

```php
$gib->getDocument('6115993e-3e77-473c-8ea5-c24036b4106c');
```

### Oluşturulan Son Belge

Portal üzerinde en son oluşturulan belgeye ait detaylara ulaşmak için;

```php
$gib->getLastDocument();
```

### HTML Çıktı Alma

Portal üzerinde kayıtlı belgeye ait HTML çıktıya ulaşmak için;

```php
$gib->getHtml('1d78ef40-6491-11ed-a280-4ccc6ae28384');
```

### Belge İndirme Adresi

> Belge indirme adresine yalnızca token oluşturduğunuz IP adresi erişebilir. Token sunucuda oluşturulduysa, belgeyi indirmeye yarayan bu link yalnızca sunucuda çalışacaktır. Farklı bir IP adresinden erişilmesi durumunda `Oturum geçersiz, token yok` hatası dönecektir. Belgeyi sunucunuza indirmek için [saveToDisk](#belgeyi-sunucuya-kaydetme) metodonu kullanabilirsiniz. 

Portal üzerinde kayıtlı belgeye ait indirme adresine ulaşmak için;

```php
$gib->getDownloadURL('44ba5b87-81a3-4474-bd0f-27d771fb4064');
```
### Belgeyi Sunucuya Kaydetme

Portal üzerinde kayıtlı belgeyi sunucuya kaydetmek için;

```php
$gib->saveToDisk('44ba5b87-81a3-4474-bd0f-27d771fb4064');
```

## Belge Filtreleme

🔍 Kayıtları zincirleme metodlar kullanarak kolayca filtreleyebilirsiniz.

```php
$documents = $gib->onlyUnsigned()
                 ->findRecipientName('mehmet')
                 ->getAll('01/09/2022', '15/09/2022');
```

> Alıcı adında `mehmet` ifadesi geçen imzalanmamış kayıtlar döner.

---

### Kullanılabilir Filtreleme Yöntemleri

| Metod                       | Açıklama                                |
| :-------------------------- | :-------------------------------------- |
| `onlyInvoice()`             | Faturalar.                              |
| `onlyProducerReceipt()`     | Müstahsil makbuzları.                   |
| `onlySelfEmployedReceipt()` | Serbest meslek makbuzları.              |
| `onlySigned()`              | İmzalanmış belgeler.                    |
| `onlyUnSigned()`            | İmzalanmamış belgeler.                  |
| `onlyDeleted()`             | Silinmiş belgeler.                      |
| `findRecipientName($value)` | Alıcı adına göre filtreleme.            |
| `findRecipientId($value)`   | Alıcı vergi numarasına göre filtreleme. |
| `findDocumentId($value)`    | Belge numarasına göre filtreleme.       |
| `findEttn($value)`          | Uuid numarasına göre filtreleme.        |
| `setLimit($limit, $offset)` | Sonuçlar için limit belirleme.          |
| `sortAsc()`                 | Önce ilk kayıtlar.                      |
| `sortDesc()`                | (Varsayılan) Önce son kayıtlar.         |

## İptal/İtiraz Talepleri

GİB Portalda kayıtlı İptal/İtiraz taleplerine ulaşmak için `getRequests` metodunu kullanabilirsiniz. Sonuç bir dizi şeklinde döner.

```php
$requests = $gib->getRequests('07/12/2020', '07/11/2022');
```

Yeni iptal/itiraz talebi oluşturmak için `objectionRequest` ve `cancellationRequest` metodlarını kullanabilirsiniz.

```php
use Mlevent\Fatura\Enums\ObjectionMethod;
use Mlevent\Fatura\Gib;

// Portal Bağlantısı
$gib = (new Gib)->setTestCredentials()
                ->login();

// İtiraz Talebi
$gib->objectionRequest(
    objectionMethod : ObjectionMethod::Kep,
    uuid            : '94d0d436-d91d-40c0-a238-e335f29b8275',
    documentId      : 'GIB2020000000218',
    documentDate    : '23/11/2020',
    explanation     : 'Hatalı İşlem'
);

// İptal Talebi
$gib->cancellationRequest(
    uuid        : '94d0d436-d91d-40c0-a238-e335f29b8275',
    explanation : 'Hatalı İşlem'
);
```

## GİB Profil Bilgileri

Kayıtlı kullanıcı bilgilerine ulaşmak için `getUserData` metodunu kullanabilirsiniz. Sonuç bir dizi şeklinde döner.

```php
$userData = $gib->getUserData();
```

Önce portaldan profil bilgilerini okuyup daha sonra modele import ederek yalnızca belirli alanları güncelleyebilirsiniz.

```php
use Mlevent\Fatura\Gib;
use Mlevent\Fatura\Models\UserDataModel;

$gib = (new Gib)->setTestCredentials()
                ->login();

$userData = UserDataModel::import($gib->getUserData());

$userData->apartmanAdi = 'Lale Apartmanı';
$userData->kapiNo      = '12';

if ($gib->updateUserData($userData)) {
    // Bilgileriniz başarıyla güncellendi.
}
```

## Mükellef Sorgulama

TC Kimlik Numarası ya da Vergi Numarası ile mükellef sorgulamaya yarar. Fatura oluşturma aşamasında vergi numarasının doğruluğunu sorgulamak için kullanılabilir. **Test ortamında sonuç boş döner.**

```php
$recipientData = $gib->getRecipientData('2920084496');
```

Bu örnek, aşağıdaki şu diziyi döndürecektir;

```php
Array
(
    [unvan] => DENİZBANK ANONİM ŞİRKETİ
    [adi] =>
    [soyadi] =>
    [vergiDairesi] => Büyük Mükellefler VD. BAŞKANLIĞI
)
```

## Birimler

[https://www.php.net/manual/en/language.types.enumerations.php](https://www.php.net/manual/en/language.types.enumerations.php) sayfasını kontrol edin.

### Ürün/Hizmet Birimleri

```php
use Mlevent\Fatura\Enums\Unit;

foreach (Unit::cases() as $unit) {
    echo $unit->name;    // Dk
    echo $unit->value;   // D61
    echo $unit->alias(); // Dakika
}
```

### Vergi Birimleri

```php
use Mlevent\Fatura\Enums\Tax;

foreach (Tax::cases() as $tax) {
    echo $tax->name;    // BankaMuameleleri
    echo $tax->value;   // 0021
    echo $tax->alias(); // Banka Muameleleri Vergisi
}
```

### Para Birimleri

```php
use Mlevent\Fatura\Enums\Currency;

foreach (Currency::cases() as $currency) {
    echo $currency->name;    // TRY
    echo $currency->value;   // TRY
    echo $currency->alias(); // Türk Lirası
}
```

## 📧İletişim

İletişim için ghergedan@gmail.com adresine e-posta gönderin.

<a href="https://www.buymeacoffee.com/mlevent" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>

---

☢️ Bu paket vergiye tabi olan mali veri oluşturur. Bu paket nedeniyle oluşabilecek sorunlardan bu paket sorumlu tutulamaz, risk kullanana aittir. Riskli görüyorsanız kullanmayınız.
