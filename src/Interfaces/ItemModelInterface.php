<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Interfaces;

interface ItemModelInterface
{
    public function prepare(ModelInterface $parent): self;
    public function getTotals(): array;
    public function export(): array;
}