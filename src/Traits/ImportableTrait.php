<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

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
     * import
     *
     * @param array $data
     */
    public static function import(array $data)
    {
        static::$isImported = true;

        $reflectionModel = new \ReflectionClass(get_called_class());

        $importedData        = $reflectionModel->newInstanceWithoutConstructor()->keyMapper($data, true);
        $constructParameters = $reflectionModel->getConstructor()->getParameters();

        $newData = [];
        foreach ($constructParameters as $param) {
            if (isset($importedData[$param->name])) {
                $newData[$param->name] = (
                    $param->isDefaultValueAvailable() && $param->getDefaultValue() instanceof \UnitEnum && !$importedData[$param->name] instanceof \UnitEnum
                        ? get_class($param->getDefaultValue())::from($importedData[$param->name]) 
                        : $importedData[$param->name]
                );
            } else {
                $newData[$param->name] = $param->getDefaultValue(); // içe aktarılan veride eksik olan parametreler
            }
        }
        return new (get_called_class())(...$newData);
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
}