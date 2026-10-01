<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Utils;

use Mlevent\Fatura\Enums\DocumentType;

final class DraftMatcher
{
    /** Eksik detay null, farklı içerik false, eşleşen içerik true döner. */
    public static function matches(array $document, array $payload, DocumentType $type, string $uuid): ?bool
    {
        foreach (['ettn', 'faturaUuid', 'uuid'] as $key) {
            if (!empty($document[$key]) && (!is_string($document[$key]) || strcasecmp($document[$key], $uuid) !== 0)) {
                return null;
            }
        }
        $dateKey = $type === DocumentType::Invoice ? 'faturaTarihi' : 'tarih';
        $totalKey = $type === DocumentType::SelfEmployedReceipt ? 'netAlinanToplam' : 'odenecekTutar';
        $missing = false;
        foreach (['vknTckn', $dateKey, $totalKey] as $key) {
            $expected = $payload[$key] ?? null;
            $actual = $document[$key] ?? null;
            if ($key === $totalKey) {
                $expected = self::number($expected);
                $actual = self::number($actual);
            } else {
                $expected = is_string($expected) ? trim($expected) : null;
                $actual = is_string($actual) ? trim($actual) : null;
            }
            if ($expected === null || $actual === null || $expected === '' || $actual === '') {
                $missing = true;
            } elseif ($expected !== $actual) {
                return false;
            }
        }
        foreach (['saat', 'paraBirimi', 'faturaTipi', 'siparisNumarasi', 'not', 'aciklama'] as $key) {
            if (!isset($payload[$key]) || $payload[$key] === '') continue;
            if (!isset($document[$key]) || !is_scalar($document[$key])) {
                $missing = true;
            } elseif (trim((string) $document[$key]) !== trim((string) $payload[$key])) {
                return false;
            }
        }
        $table = match ($type) {
            DocumentType::Invoice => 'malHizmetTable',
            DocumentType::ProducerReceipt => 'mustahsilTable',
            DocumentType::SelfEmployedReceipt => 'serbestTable',
        };
        if (!empty($payload[$table])) {
            if (!is_array($document[$table] ?? null)) return null;
            if (count($document[$table]) !== count($payload[$table])) return false;
            $actualRows = array_values($document[$table]);
            foreach (array_values($payload[$table]) as $index => $expectedRow) {
                $actualRow = $actualRows[$index];
                if (!is_array($actualRow) || !is_array($expectedRow)) return null;
                foreach (['malHizmet', 'miktar', 'birim', 'birimFiyat', 'kdvOrani', 'iskontoOrani', 'iskontoTutari'] as $key) {
                    if (!array_key_exists($key, $expectedRow)) continue;
                    if (!array_key_exists($key, $actualRow) || !is_scalar($actualRow[$key])) {
                        $missing = true;
                        continue;
                    }
                    $numeric = !in_array($key, ['malHizmet', 'birim'], true);
                    $expected = $numeric ? self::number($expectedRow[$key]) : trim((string) $expectedRow[$key]);
                    $actual = $numeric ? self::number($actualRow[$key]) : trim((string) $actualRow[$key]);
                    if ($expected === null || $actual === null) $missing = true;
                    elseif ($expected !== $actual) return false;
                }
            }
        }
        return $missing ? null : true;
    }

    private static function number(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            if (!is_finite((float) $value)) return null;
            $value = number_format($value, 10, '.', '');
        }
        if (!is_string($value)) return null;
        $value = trim($value);
        if (preg_match('/\A-?\d{1,3}(?:\.\d{3})+,\d+\z/', $value)) $value = str_replace('.', '', $value);
        if (!preg_match('/\A-?\d+(?:[.,]\d+)?\z/', $value)) return null;
        $value = str_replace(',', '.', $value);
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');
        return $whole . ($fraction !== '' ? '.' . $fraction : '');
    }
}
