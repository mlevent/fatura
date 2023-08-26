<?php

declare(strict_types=1);

use NumberToWords\NumberToWords;

if (!function_exists('create_uuid')) {

    /**
     * create_uuid
     *
     * @return string
     */
    function create_uuid(): string
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }
}

if (!function_exists('amount_format')) {

    /**
     * amount_format
     *
     * @param  float $value
     * @return float
     */
    function amount_format(float $value): float
    {
        return round($value, 2);
    }
}

if (!function_exists('map_with_amount_format')) {

    /**
     * map_with_amount_format
     *
     * @param  array $array
     * @return array
     */
    function map_with_amount_format(array $array): array
    {
        return array_map(fn ($item) => amount_format($item), $array);
    }
}

if (!function_exists('number_to_words')) {

    /**
     * number_to_words
     *
     * @param  integer|float $number
     * @return string
     */
    function number_to_words(int|float $number): string
    {
        return mb_strtoupper(strtr(NumberToWords::transformCurrency('tr', (int) number_format($number, 2, '', ''), 'TRY'), ['i' => 'İ', 'ı' => 'I']));
    }
}

if (!function_exists('array_column_sum')) {

    /**
     * array_column_sum
     *
     * @param  array         $array
     * @param  string        $key
     * @param  callable|null $callback
     * @param  boolean       $amountFormat
     * @return float
     */
    function array_column_sum(array $array, string $key, ?callable $callback = null, bool $amountFormat = false): float
    {
        $arrayColumn = array_column(($callback ? array_filter($array, $callback) : $array), $key);
        return array_sum($amountFormat 
            ? map_with_amount_format($arrayColumn) 
            : $arrayColumn);
    }
}

if (!function_exists('array_column_sum_with_amount_format')) {

    /**
     * array_column_sum_with_amount_format
     *
     * @param  array         $array
     * @param  string        $key
     * @param  callable|null $callback
     * @return float
     */
    function array_column_sum_with_amount_format(array $array, string $key, ?callable $callback = null): float
    {
        return array_column_sum($array, $key, $callback, true);
    }
}

if (!function_exists('percentage')) {

    /**
     * percentage
     *
     * @param  float         $amount
     * @param  integer|float $rate
     * @return float
     */
    function percentage(float $amount, int|float $rate): float
    {
        return ($amount * $rate) / 100;
    }
}

if (!function_exists('curdate')) {

    /**
     * curdate
     *
     * @param  string      $format
     * @param  string|null $modify
     * @return string
     */
    function curdate(string $format, string $modify = null): string
    {
        $date = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
        if ($modify) {
            $date->modify($modify);
        }
        return $date->format($format);
    }
}

if (!function_exists('dd')) {
    
    /**
     * dd
     *
     * @param  [type]  $var
     * @param  boolean $die
     * @return void
     */
    function dd($var, $die = true){
        echo '<pre style="background:#000; color:#52eb34;">'; print_r($var); echo '</pre>'; if($die) exit;
    }
}