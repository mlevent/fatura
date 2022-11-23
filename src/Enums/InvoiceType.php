<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum InvoiceType: string
{
    case Satis        = 'SATIS';
    case Iade         = 'IADE';
    case Tevkifat     = 'TEVKIFAT';
    case Istisna      = 'ISTISNA';
    case OzelMatrah   = 'OZELMATRAH';
    case IhracKayitli = 'IHRACKAYITLI';

    /**
     * alias
     *
     * @return string
     */
    public function alias(): string
    {
        return match ($this) {
            self::Satis        => 'Satış',
            self::Iade         => 'İade',
            self::Tevkifat     => 'Tevkifat',
            self::Istisna      => 'İstisna',
            self::OzelMatrah   => 'Özel Matrah',
            self::IhracKayitli => 'İhraç Kayıtlı',
        };
    }

    /**
     * reasons
     *
     * @return array
     */
    public function reasons(): array
    {
        return match ($this) {
            self::OzelMatrah => [
                801 => 'Milli Piyango, Spor Toto vb. Oyunlar',
                802 => 'At yarışları ve diğer müşterek bahis ve talih oyunları',
                803 => 'Profesyonel Sanatçıların Yer Aldığı Gösteriler, Konserler, Profesyonel Sporcuların Katıldığı Sportif Faaliyetler, Maçlar, Yarışlar ve Yarışmalar',
                804 => 'Gümrük Depolarında ve Müzayede Mahallerinde Yapılan Satışla',
                805 => 'Altından Mamül veya Altın İçeren Ziynet Eşyaları İle Sikke Altınların Teslimi',
                806 => 'Tütün Mamülleri',
                807 => 'Muzır Neşriyat Kapsamındaki  Gazete, Dergi vb. Periyodik Yayınlar',
                808 => 'Gümüşten Mamul veya Gümüş İçeren Ziynet Eşyaları ile Sikke Gümüşlerin Teslimi',
                809 => 'Belediyeler taraf. yap. şehiriçi yolcu taşımacılığında kullanılan biletlerin ve kartların bayiler tarafından satışı',
                810 => 'Ön Ödemeli Elektronik Haberleşme Hizmetleri',
                811 => 'TŞOF Tarafından Araç Plakaları ile Sürücü Kurslarında Kullanılan Bir Kısım Evrakın Teslimi',
                812 => 'KDV Uygulanmadan Alınan İkinci El Motorlu Kara Taşıtı veya Taşınmaz Teslimi',
            ],
            default => [],
        };
    }
}