<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Models;

use Mlevent\Fatura\Exceptions\InvalidArgumentException;
use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Interfaces\ItemModelInterface;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Traits\ArrayableTrait;
use Mlevent\Fatura\Traits\EachableTrait;
use Mlevent\Fatura\Traits\ImportableTrait;
use Mlevent\Fatura\Traits\MapableTrait;
use Mlevent\Fatura\Traits\NewableTrait;
use Mlevent\Fatura\Utils\FormatValidator;
use Ramsey\Uuid\Nonstandard\Uuid;

abstract class AbstractModel implements ModelInterface
{
    use ArrayableTrait,
        EachableTrait,
        ImportableTrait,
        MapableTrait,
        NewableTrait;

    /**
     * @return void
     */
    abstract protected function calculateTotals(): void;

    /**
     * @var array
     */
    public array $malHizmetTable;

    /**
     * @var string
     */
    public string $uuid;

    /**
     * @var string
     */
    public string $tarih;

    /**
     * @var string
     */
    public string $saat;

    public function __constuct()
    {
        if ($this->uuid) {
            if (!Uuid::isValid($this->uuid)) {
                throw new InvalidArgumentException('Uuid geçerli formatta değil.');
            }
        } else {
            $this->uuid = Uuid::uuid1()->toString();
        }

        if ($this->tarih) {
            if (!FormatValidator::date($this->tarih)) {
                throw new InvalidFormatException('Tarih geçerli formatta değil.');
            }
        } else {
            $this->tarih = curdate('d/m/Y');
        }

        if ($this->saat) {
            if (!FormatValidator::time($this->saat)) {
                throw new InvalidFormatException('Saat geçerli formatta değil.');
            }
        } else {
            $this->saat = curdate('H:i:s');
        }

        if (!$this->isImportedFromApi() && $items = $this->getItems()) {
            $this->clearItems()->addItem(...array_map(function ($item) {
                $itemModelName = (substr(get_called_class(), 0, -5) . 'ItemModel');
                if ($this->isImportedFromUser()) {
                    return $itemModelName::import($item);
                }
                return new $itemModelName(...$item);
            }, $items));
        }
    }

    /**
     * getUuid
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }
    
    /**
     * getItems
     *
     * @param  boolean $toExport
     * @return array
     */
    public function getItems(bool $toExport = false): array
    {
        if ($toExport) {
            return array_map(fn($item) => $item instanceof ItemModelInterface ? $item->export() : $item, $this->malHizmetTable);
        }
        return $this->malHizmetTable;
    }

    /**
     * setItems
     *
     * @param  ItemModelInterface ...$items
     * @return void
     */
    protected function setItems(ItemModelInterface ...$items): void
    {
        foreach ($items as $item) {
            $this->malHizmetTable[] = $item->prepare($this);
        }

        // İçe aktarılan veriye yeni öğe eklendiyse yalnızca model ile oluşturulan öğeleri kullan
        if ($this->isImportedClean()) {
            $this->malHizmetTable = array_values(
                array_filter($this->getItems(), function ($item) {
                    return $item instanceof ItemModelInterface;
                })
            );
            self::$isImportedDirty = true;
        }

        // Veri içe aktarılmadıysa toplamları hesapla
        if (!$this->isImported() || $this->isImportedDirty()) {
            $this->calculateTotals();
        }
    }

    /**
     * clearItems
     *
     * @return self
     */
    protected function clearItems(): self
    {
        $this->malHizmetTable = [];
        return $this;
    }

    /**
     * getTaxes
     *
     * @return array
     */
    public function getTaxes(): array
    {
        $taxes = [];
        foreach ($this->getItems() as $item) {
            foreach ($item->getTaxes() as $tax) {
                $taxes[] = $tax;
            }
        }
        return $taxes;
    }
}