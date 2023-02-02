<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

use Mlevent\Fatura\Exceptions\InvalidArgumentException;

trait ImportableTrait
{    
    /**
     * @var boolean
     */
    public static bool $isImported = false;

    /**
     * @var boolean
     */
    public static bool $isImportedDirty = false;

    /**
     * @var boolean
     */
    public static bool $isImportedFromUser = false;

    /**
     * modelMap
     *
     * @param array $data
     */
    public static function modelMap(array $data): array
    {
        $reflectionModel     = new \ReflectionClass(get_called_class());
        $importedData        = $reflectionModel->newInstanceWithoutConstructor()->keyMapper($data, true);
        $constructParameters = $reflectionModel->getConstructor()->getParameters();

        $newData = [];
        foreach ($constructParameters as $param) {

            // modelle uyuşan parametreler
            if (isset($importedData[$param->name])) {

                // enum type veriler kontrol ediliyor, veri string olarak geliyorsa enum olarak değiştiriliyor
                $newData[$param->name] = (
                    $param->isDefaultValueAvailable() && $param->getDefaultValue() instanceof \UnitEnum && !$importedData[$param->name] instanceof \UnitEnum
                        ? get_class($param->getDefaultValue())::from($importedData[$param->name])
                        : $importedData[$param->name]
                );
            
            // içe aktarılan veride eksik olan ancak modelde default bulunan parametreler
            } else {
                if (!$param->isDefaultValueAvailable()) {
                    throw new InvalidArgumentException('Modele eksik parametre iletildi.', $data, $constructParameters);
                }
                $newData[$param->name] = $param->getDefaultValue();
            }
        }
        return $newData;
    }

    /**
     * import
     *
     * @param array $data
     */
    public static function import(array $data)
    {
        static::$isImported = true;
        return new (get_called_class())(...self::modelMap($data));
    }

    /**
     * use
     *
     * @param array $data
     */
    public static function use(array $data)
    {
        static::$isImportedFromUser = true;
        return self::import($data);
    }

    /**
     * isImported
     *
     * @return boolean
     */
    public function isImported(): bool
    {
        return self::$isImported;
    }

    /**
     * isImportedDirty
     *
     * @return boolean
     */
    public function isImportedDirty(): bool
    {
        return self::$isImported && self::$isImportedDirty;
    }

    /**
     * isImportedClean
     *
     * @return boolean
     */
    public function isImportedClean(): bool
    {
        return self::$isImported && !self::$isImportedDirty;
    }

    /**
     * isImportedFromApi
     *
     * @return boolean
     */
    public function isImportedFromApi(): bool
    {
        return self::$isImported && !self::$isImportedFromUser;
    }

    /**
     * isImportedFromUser
     *
     * @return boolean
     */
    public function isImportedFromUser(): bool
    {
        return self::$isImportedFromUser;
    }
}