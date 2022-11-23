<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Utils;

class FormatValidator
{
    protected const FormatValidationPatterns = [
        'invoiceNumber' => '/^(GIB)[a-zA-Z0-9]{13}$/',
        'gtipCode'      => '/^[0-9]{12}$/',
        'date'          => '/(0[1-9]|1[0-9]|2[0-9]|3(0|1))\/(0[1-9]|1[0-2])\/\d{4}/',
        'time'          => '/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/',
    ];

    /**
     * __callStatic
     *
     * @param  [type]  $name
     * @param  [type]  $args
     * @return boolean
     */
    public static function __callStatic($name, $args): bool
    {
        if (array_key_exists($name, self::FormatValidationPatterns)) {
            return preg_match(self::FormatValidationPatterns[$name], $args[0]) ? true : false;
        }
        return false;
    }
}