<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum Tax: string
{    
    case BankaMuameleleri  = '0021';
    case KKDFKesintisi     = '0061';
    case OTV1Liste         = '0071';
    case OTV2Liste         = '9077';
    case OTV3Liste         = '0073';
    case OTV4Liste         = '0074';
    case OTV3AListe        = '0075';
    case OTV3BListe        = '0076';
    case OTV3CListe        = '0077';
    case Damga             = '1047';
    case Damga5035         = '1048';
    case OzelIletisim      = '4080'; 
    case OzelIletisim5035  = '4081'; 
    case KDVTevkifat       = '9015'; // Tevkifat
    case BSMV4961          = '9021'; 
    case BorsaTescil       = '8001'; 
    case EnerjiFonu        = '8002'; 
    case ElkHavagazTuketim = '4071'; 
    case TRTPayi           = '8004'; 
    case ElkTuketim        = '8005'; 
    case TKKullanim        = '8006'; 
    case TKRuhsat          = '8007'; 
    case CevreTemizlik     = '8008'; 
    case GVStopaj          = '0003'; // Stopaj
    case KVStopaj          = '0011'; // Stopaj
    case MeraFonu          = '9040'; // Stopaj
    case OTV1ListeTevkifat = '4171'; // Tevkifat
    case BelOdHalRusum     = '9944'; 
    case Konaklama         = '0059'; 
    case SGKPrim           = 'SGK_PRIM'; // Müstahsil
    
    /**
     * alias
     *
     * @return string
     */
    public function alias(): string
    {
        return match ($this) {
            self::BankaMuameleleri  => 'Banka Muameleleri Vergisi',
            self::KKDFKesintisi     => 'KKDF Kesintisi',
            self::OTV1Liste         => 'ÖTV 1. Liste',
            self::OTV2Liste         => 'ÖTV 2. Liste',
            self::OTV3Liste         => 'ÖTV 3. Liste',
            self::OTV4Liste         => 'ÖTV 4. Liste',
            self::OTV3AListe        => 'ÖTV 3A Liste',
            self::OTV3BListe        => 'ÖTV 3B Liste',
            self::OTV3CListe        => 'ÖTV 3C Liste',
            self::Damga             => 'Damga Vergisi',
            self::Damga5035         => '5035 Sayılı Kanuna Göre Damga Vergisi',
            self::OzelIletisim      => 'Özel İletişim Vergisi',
            self::OzelIletisim5035  => '5035 Sayılı Kanuna Göre Özel İletişim Vergisi',
            self::KDVTevkifat       => 'KDV Tevkifat',
            self::BSMV4961          => 'Banka ve Sigorta Muameleleri Vergisi',
            self::BorsaTescil       => 'Borsa Tescil Ücreti',
            self::EnerjiFonu        => 'Enerji Fonu',
            self::ElkHavagazTuketim => 'Elektrik Havagaz Tüketim Vergisi',
            self::TRTPayi           => 'TRT Payı',
            self::ElkTuketim        => 'Elektrik Tüketim Vergisi',
            self::TKKullanim        => 'TK Kullanım',
            self::TKRuhsat          => 'TK Ruhsat',
            self::CevreTemizlik     => 'Çevre Temizlik Vergisi',
            self::GVStopaj          => 'Gelir Vergisi Stopajı',
            self::KVStopaj          => 'Kurumlar Vergisi Stopajı',
            self::MeraFonu          => 'Mera Fonu',
            self::OTV1ListeTevkifat => 'ÖTV 1. Liste Tevkifat',
            self::BelOdHalRusum     => 'Belediyelere Ödenen Hal Rüsumu',
            self::Konaklama         => 'Konaklama Vergisi',
            self::SGKPrim           => 'SGK Prim Kesintisi',
        };
    }

    /**
     * hasVat
     * kdvsi olan vergi kalemleri. otv1liste icin duzenleme gerekiyor.
     *
     * @return boolean
     */
    public function hasVat(): bool
    {
        return match ($this) {
            self::KKDFKesintisi     => true,
            self::OTV1Liste         => true,
            self::OTV2Liste         => true,
            self::OTV3Liste         => true,
            self::OTV4Liste         => true,
            self::OTV3AListe        => true,
            self::OTV3BListe        => true,
            self::OTV3CListe        => true,
            self::EnerjiFonu        => true,
            self::ElkHavagazTuketim => true,
            self::TRTPayi           => true,
            self::ElkTuketim        => true,
            self::OTV1ListeTevkifat => true,
            self::BelOdHalRusum     => true,
            default                 => false,
        };
    }

    /**
     * isStoppage
     *
     * @return boolean
     */
    public function isStoppage(): bool
    {
        return match ($this) {
            self::KDVTevkifat => true,
            self::GVStopaj    => true,
            self::KVStopaj    => true,
            self::MeraFonu    => true,
            self::SGKPrim     => true,
            default           => false,
        };
    }

    /**
     * isWithholding
     * tevkifat faturasinda kullanilan vergi kalemleri. otv1liste icin duzenleme gerekiyor.
     *
     * @return boolean
     */
    public function isWithholding(): bool
    {
        return match ($this) {
            self::KDVTevkifat       => true,
            self::OTV1ListeTevkifat => true,
            default                 => false,
        };
    }

    /**
     * hasDefaultRate 
     * vergi kaleminin varsayilan orana sahip olup olmadigini dogrular.
     * varsayilan orana sahip vergiler icin tutar manuel gonderilmek zorundadir.
     *
     * @return boolean
     */
    public function hasDefaultRate(): bool
    {
        return match ($this) {
            self::OTV1Liste         => true,
            self::OTV1ListeTevkifat => true,
            self::Konaklama         => true,
            default                 => false,
        };
    }

    /**
     * defaultRate
     *
     * @return integer
     */
    public function defaultRate(): int
    {
        return match ($this) {
            self::OTV1Liste         => 0,
            self::OTV1ListeTevkifat => 100,
            self::Konaklama         => 2,
        };
    }

    /**
     * getRate
     *
     * @param  integer         $code
     * @return integer|boolean
     */
    public function getRate(int $code): int|bool
    {
        if (array_key_exists($code, $this->codes())) {
            return $this->codes()[$code]['rate'];
        }
        return false;
    }

    /**
     * codes
     *
     * @return array
     */
    public function codes(): array
    {
        return match ($this) {
            self::KDVTevkifat => [
                601 => ['rate' => 40, 'name' => 'Yapım İşleri ile Bu İşlerle Birlikte İfa Edilen Mühendislik-Mimarlık ve Etüt-Proje Hizmetleri [KDVGUT-(I/C-2.1.3.2.1)]'],
                602 => ['rate' => 90, 'name' => 'Etüt, plan-proje, danışmanlık, denetim vb'],
                603 => ['rate' => 70, 'name' => 'Makine, Teçhizat, Demirbaş ve Taşıtlara Ait Tadil, Bakım ve Onarım Hizmetleri [KDVGUT- (I/C-2.1.3.2.3)]'],
                604 => ['rate' => 50, 'name' => 'Yemek servis hizmeti'],
                605 => ['rate' => 50, 'name' => 'Organizasyon hizmeti'],
                606 => ['rate' => 90, 'name' => 'İşgücü temin hizmetleri'],
                607 => ['rate' => 90, 'name' => 'Özel güvenlik hizmeti'],
                608 => ['rate' => 90, 'name' => 'Yapı denetim hizmetleri'],
                609 => ['rate' => 70, 'name' => 'Fason Olarak Yaptırılan Tekstil ve Konfeksiyon İşleri, Çanta ve Ayakkabı Dikim İşleri ve Bu İşlere Aracılık Hizmetleri [KDVGUT-(I/C-2.1.3.2.7)]'],
                610 => ['rate' => 90, 'name' => 'Turistik mağazalara verilen müşteri bulma/ götürme hizmetleri'],
                611 => ['rate' => 90, 'name' => 'Spor kulüplerinin yayın, reklam ve isim hakkı gelirlerine konu işlemleri'],
                612 => ['rate' => 90, 'name' => 'Temizlik Hizmeti [KDVGUT-(I/C-2.1.3.2.10)]'],
                613 => ['rate' => 90, 'name' => 'Çevre, Bahçe ve Bakım Hizmetleri [KDVGUT-(I/C-2.1.3.2.11)]'],
                614 => ['rate' => 50, 'name' => 'Servis taşımacıliğı'],
                615 => ['rate' => 70, 'name' => 'Her Türlü Baskı ve Basım Hizmetleri [KDVGUT-(I/C-2.1.3.2.12)]'],
                616 => ['rate' => 50, 'name' => 'Diğer Hizmetler [KDVGUT-(I/C-2.1.3.2.13)]'],
                617 => ['rate' => 70, 'name' => 'Hurda metalden elde edilen külçe teslimleri'],
                618 => ['rate' => 70, 'name' => 'Hurda Metalden Elde Edilenler Dışındaki Bakır, Çinko, Demir Çelik, Alüminyum ve Kurşun Külçe Teslimi [KDVGUT-(I/C-2.1.3.3.1)]'],
                619 => ['rate' => 70, 'name' => 'Bakir, çinko ve alüminyum ürünlerinin teslimi'],
                620 => ['rate' => 70, 'name' => 'istisnadan vazgeçenlerin hurda ve atık teslimi'],
                621 => ['rate' => 90, 'name' => 'Metal, plastik, lastik, kauçuk, kâğit ve cam hurda ve atıklardan elde edilen hammadde teslimi'],
                622 => ['rate' => 90, 'name' => 'Pamuk, tiftik, yün ve yapaği ile ham post ve deri teslimleri'],
                623 => ['rate' => 50, 'name' => 'Ağaç ve orman ürünleri teslimi'],
                624 => ['rate' => 20, 'name' => 'Yük Taşımacılığı Hizmeti [KDVGUT-(I/C-2.1.3.2.11)]'],
                625 => ['rate' => 30, 'name' => 'Ticari Reklam Hizmetleri [KDVGUT-(I/C-2.1.3.2.15)]'],
                626 => ['rate' => 20, 'name' => 'Diğer Teslimler [KDVGUT-(I/C-2.1.3.3.7.)]'],
                627 => ['rate' => 50, 'name' => 'Demir-Çelik Ürünlerinin Teslimi [KDVGUT-(I/C-2.1.3.3.8)]'],
                '627-Ex' => ['rate' => 40, 'name' => 'Demir-Çelik Ürünlerinin Teslimi [KDVGUT-(I/C-2.1.3.3.8)] (01/11/2022 tarihi öncesi)'],
                801 => ['rate' => 100, 'name' => '[Tam Tevkifat] Yapım İşleri ile Bu İşlerle Birlikte İfa Edilen Mühendislik-Mimarlık ve Etüt-Proje Hizmetleri[KDVGUT-(I/C-2.1.3.2.1)]'],
                802 => ['rate' => 100, 'name' => '[Tam Tevkifat] Etüt, Plan-Proje, Danışmanlık, Denetim ve Benzeri Hizmetler[KDVGUT-(I/C-2.1.3.2.2)]'],
                803 => ['rate' => 100, 'name' => '[Tam Tevkifat] Makine, Teçhizat, Demirbaş ve Taşıtlara Ait Tadil, Bakım ve Onarım Hizmetleri[KDVGUT- (I/C-2.1.3.2.3)]'],
                804 => ['rate' => 100, 'name' => '[Tam Tevkifat] Yemek Servis Hizmeti[KDVGUT-(I/C-2.1.3.2.4)]'],
                805 => ['rate' => 100, 'name' => '[Tam Tevkifat] Organizasyon Hizmeti[KDVGUT-(I/C-2.1.3.2.4)]'],
                806 => ['rate' => 100, 'name' => '[Tam Tevkifat] İşgücü Temin Hizmetleri[KDVGUT-(I/C-2.1.3.2.5)]'],
                807 => ['rate' => 100, 'name' => '[Tam Tevkifat] Özel Güvenlik Hizmeti[KDVGUT-(I/C-2.1.3.2.5)]'],
                808 => ['rate' => 100, 'name' => '[Tam Tevkifat] Yapı Denetim Hizmetleri[KDVGUT-(I/C-2.1.3.2.6)]'],
                809 => ['rate' => 100, 'name' => '[Tam Tevkifat] Fason Olarak Yaptırılan Tekstil ve Konfeksiyon İşleri, Çanta ve Ayakkabı Dikim İşleri ve Bu İşlere Aracılık Hizmetleri[KDVGUT-(I/C-2.1.3.2.7)]'],
                810 => ['rate' => 100, 'name' => '[Tam Tevkifat] Turistik Mağazalara Verilen Müşteri Bulma/ Götürme Hizmetleri[KDVGUT-(I/C-2.1.3.2.8)]'],
                811 => ['rate' => 100, 'name' => '[Tam Tevkifat] Spor Kulüplerinin Yayın, Reklâm ve İsim Hakkı Gelirlerine Konu İşlemleri[KDVGUT-(I/C-2.1.3.2.9)]'],
                812 => ['rate' => 100, 'name' => '[Tam Tevkifat] Temizlik Hizmeti[KDVGUT-(I/C-2.1.3.2.10)]'],
                813 => ['rate' => 100, 'name' => '[Tam Tevkifat] Çevreve Bahçe Bakım Hizmetleri[KDVGUT-(I/C-2.1.3.2.10)]'],
                814 => ['rate' => 100, 'name' => '[Tam Tevkifat] Servis Taşımacılığı Hizmeti[KDVGUT-(I/C-2.1.3.2.11)]'],
                815 => ['rate' => 100, 'name' => '[Tam Tevkifat] Her Türlü Baskı ve Basım Hizmetleri[KDVGUT-(I/C-2.1.3.2.12)]'],
                816 => ['rate' => 100, 'name' => '[Tam Tevkifat] Hurda Metalden Elde Edilen Külçe Teslimleri[KDVGUT-(I/C-2.1.3.3.1)]'],
                817 => ['rate' => 100, 'name' => '[Tam Tevkifat] Hurda Metalden Elde Edilenler Dışındaki Bakır, Çinko, Demir Çelik, Alüminyum ve Kurşun Külçe Teslimi [KDVGUT-(I/C-2.1.3.3.1)]'],
                818 => ['rate' => 100, 'name' => '[Tam Tevkifat] Bakır, Çinko, Alüminyum ve Kurşun Ürünlerinin Teslimi[KDVGUT-(I/C-2.1.3.3.2)]'],
                819 => ['rate' => 100, 'name' => '[Tam Tevkifat] İstisnadan Vazgeçenlerin Hurda ve Atık Teslimi[KDVGUT-(I/C-2.1.3.3.3)]'],
                820 => ['rate' => 100, 'name' => '[Tam Tevkifat] Metal, Plastik, Lastik, Kauçuk, Kâğıt ve Cam Hurda ve Atıklardan Elde Edilen Hammadde Teslimi[KDVGUT-(I/C-2.1.3.3.4)]'],
                821 => ['rate' => 100, 'name' => '[Tam Tevkifat] Pamuk, Tiftik, Yün ve Yapağı İle Ham Post ve Deri Teslimleri[KDVGUT-(I/C-2.1.3.3.5)]'],
                822 => ['rate' => 100, 'name' => '[Tam Tevkifat] Ağaç ve Orman Ürünleri Teslimi[KDVGUT-(I/C-2.1.3.3.6)]'],
                823 => ['rate' => 100, 'name' => '[Tam Tevkifat] Yük Taşımacılığı Hizmeti [KDVGUT-(I/C-2.1.3.2.11)]'],
                824 => ['rate' => 100, 'name' => '[Tam Tevkifat] Ticari Reklam Hizmetleri [KDVGUT-(I/C-2.1.3.2.15)]'],
                825 => ['rate' => 100, 'name' => '[Tam Tevkifat] Demir-Çelik Ürünlerinin Teslimi [KDVGUT-(I/C-2.1.3.3.8)]'],
            ],
            default => [],
        };
    }
}