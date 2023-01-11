<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Traits;

trait EachableTrait
{    
    /**
     * each
     *
     * @param  callable $fn
     * @return self
     */
    public function each(callable $fn): self
    {
        array_walk($this, function($i, $k) use($fn){
            $fn($i, $k, $this);
        });
        return $this;
    }

    /**
     * eachWith
     *
     * @param  iterable $data
     * @param  callable $fn
     * @return self
     */
    public function eachWith(iterable $data, callable $fn): self
    {
        array_walk($data, function($i, $k) use($fn){
            $fn($this, $i, $k);
        });
        return $this;
    }

    /**
     * map
     *
     * @param  callable $fn
     * @return self
     */
    public function map(callable $fn): self
    {
        array_walk($this, function(&$i, $k) use($fn){
            $i = $fn($i, $k, $this);
        });
        return $this;
    }
}