<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Enums;

enum Unit: string
{
    case Gun   = 'DAY';
    case Ay    = 'MON';
    case Yil   = 'ANN';
    case Saat  = 'HUR';
    case Dk    = 'D61';
    case Sn    = 'D62';
    case Adet  = 'C62';
    case Pk    = 'PA';
    case Kutu  = 'BX';
    case Mgm   = 'MGM';
    case Grm   = 'GRM';
    case Kgm   = 'KGM';
    case Ltr   = 'LTR';
    case Ton   = 'TNE';
    case Nt    = 'NT';
    case Gt    = 'GT';
    case Mmt   = 'MMT';
    case Cmt   = 'CMT';
    case Mtr   = 'MTR';
    case Ktm   = 'KTM';
    case Mlt   = 'MLT';
    case Mm3   = 'MMQ';
    case Cm2   = 'CMK';
    case Cmq   = 'CMQ';
    case M2    = 'MTK';
    case M3    = 'MTQ';
    case Kjo   = 'KJO';
    case Clt   = 'CLT';
    case Ct    = 'CT';
    case Kwh   = 'KWH';
    case Mwh   = 'MWH';
    case Cct   = 'CCT';
    case Gkj   = 'D30';
    case Klt   = 'D40';
    case Lpa   = 'LPA';
    case Kgm2  = 'B32';
    case Ncl   = 'NCL';
    case Pr    = 'PR';
    case Kmt   = 'R9';
    case Set   = 'SET';
    case T3    = 'T3';
    case Scm   = 'Q37';
    case Ncm   = 'Q39';
    case Mmbtu = 'J39';
    case Cm3   = 'G52';
    case Dzn   = 'DZN';
    case Dm2   = 'DMK';
    case Dmt   = 'DMT';
    case Har   = 'HAR';
    case Lm    = 'LM';

    /**
     * alias
     *
     * @return string
     */
    public function alias(): string
    {
        return match ($this) {
            self::Gun   => 'Gün',
            self::Ay    => 'Ay',
            self::Yil   => 'Yıl',
            self::Saat  => 'Saat',
            self::Dk    => 'Dakika',
            self::Sn    => 'Saniye',
            self::Adet  => 'Adet',
            self::Pk    => 'Paket',
            self::Kutu  => 'Kutu',
            self::Mgm   => 'Mg',
            self::Grm   => 'Gram',
            self::Kgm   => 'Kg',
            self::Ltr   => 'Lt',
            self::Ton   => 'Ton',
            self::Nt    => 'Net Ton',
            self::Gt    => 'Gross ton',
            self::Mmt   => 'Mm',
            self::Cmt   => 'Cm',
            self::Mtr   => 'M',
            self::Ktm   => 'Km',
            self::Mlt   => 'Ml',
            self::Mm3   => 'Mm3',
            self::Cm2   => 'Cm2',
            self::Cmq   => 'Cm3',
            self::M2    => 'M2',
            self::M3    => 'M3',
            self::Kjo   => 'Kj',
            self::Clt   => 'Cl',
            self::Ct    => 'Karat', 
            self::Kwh   => 'Kwh',
            self::Mwh   => 'Mwh',
            self::Cct   => 'Ton Başına Taşıma Kapasitesi',
            self::Gkj   => 'Brüt Kalori',
            self::Klt   => '1000 Lt',
            self::Lpa   => 'Saf Alkol Lt',
            self::Kgm2  => 'Kg M2',
            self::Ncl   => 'Hücre Adet',
            self::Pr    => 'Çift',
            self::Kmt   => '1000 M3',
            self::Set   => 'Set',
            self::T3    => '1000 Adet', 
            self::Scm   => 'Scm',
            self::Ncm   => 'Ncm',
            self::Mmbtu => 'Mmbtu',
            self::Cm3   => 'Cm³',
            self::Dzn   => 'Düzine',
            self::Dm2   => 'Dm2',
            self::Dmt   => 'Dm',
            self::Har   => 'Ha',
            self::Lm    => 'Metretül (LM)',
        };
    }
}