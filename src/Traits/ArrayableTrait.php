<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

trait ArrayableTrait
{
    /**
     * toArray
     *
     * @return array
     */
    public function toArray(): array
    {
        $return = new class {
            function getPublicVars($object) {
                return get_object_vars($object);
            }
        };
        return $return->getPublicVars($this);
    }
}