<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\trait;

use xvqrlz\simpleedit\data\BlockStorage;

trait HistoryTrait
{
    private array $history = [];

    public function addHistory(string $name, BlockStorage $blockStorage): void
    {
        $this->history[$name][] = $blockStorage;
    }

    public function getLastHistory(string $name): ?BlockStorage
    {
        return $this->history[$name][count($this->history[$name]) - 1] ?? null;
    }

    public function removeHistory(string $name): ?BlockStorage
    {
        return array_pop($this->history[$name]) ?? null;
    }
}