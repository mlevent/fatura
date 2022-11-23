<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

trait MapableTrait
{
    abstract protected function keyMap(): array;

    /**
     * keyMapper
     *
     * @param  array $data
     * @return array
     */
    protected function keyMapper(array $data, bool $flip = false): array
    {
        $keyMapData = $flip 
            ? array_flip($this->keyMap()) 
            : $this->keyMap();
        
        return array_combine(array_map(function ($item) use ($keyMapData) {
            return isset($keyMapData[$item]) 
                ? $keyMapData[$item] 
                : $item;
        }, array_keys($data)), array_values($data));
    }
}