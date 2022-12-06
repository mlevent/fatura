<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

trait NewableTrait
{    
    /**
     * new
     *
     * @param [type] ...$args
     */
    public static function new(...$args)
    {
        return new (get_called_class())(...(isset($args[0]) ? $args[0] : $args));
    }
}