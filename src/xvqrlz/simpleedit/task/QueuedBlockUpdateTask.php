<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\task;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use xvqrlz\simpleedit\data\BlockData;

class QueuedBlockUpdateTask extends Task
{
    private Level $level;
    /** @var BlockData[] */
    private array $blockPool;
    private \Closure $onTick;
    private \Closure $onComplete;

    public function __construct(
        Level $level,
        array $blockPool,
        callable $onTick = null,
        callable $onComplete = null
    ) {
        $this->level = $level;
        $this->blockPool = array_reverse($blockPool);
        $this->onTick = $onTick ?? fn() => null;
        $this->onComplete = $onComplete ?? fn() => null;
    }

    public function onRun(int $currentTick): void
    {
        if (!empty($this->blockPool)) {
            $blockData = array_pop($this->blockPool);
            $block = Block::get($blockData->getId(), $blockData->getMeta());
            $this->level->setBlock($blockData->getPosition(), $block);
            ($this->onTick)($blockData, $block, $this->level, $this->blockPool);
        } else {
            ($this->onComplete)();
            $this->getHandler()?->cancel();
        }
    }
}