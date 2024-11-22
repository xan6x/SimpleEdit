<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\trait;

use xvqrlz\simpleedit\data\BlockStorage;

trait ClipboardTrait
{
    private array $clipboard = [];

    public function copyClipboard(string $name, BlockStorage $blockStorage): void
    {
        $this->clipboard[$name] = $blockStorage->getStorage();
    }

    public function getClipboard(string $name): ?array
    {
        return $this->clipboard[$name] ?? null;
    }

    public function clearClipboard(string $name): void
    {
        unset($this->clipboard[$name]);
    }
}