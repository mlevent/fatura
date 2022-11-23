<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Interfaces;

interface ModelInterface
{
    public function getUuid(): string;
    public function getItems(): array;
    public function getTaxes(): array;
    public function getTotals(): array;
    public function addItem(): self;
    public function export(): array;
}