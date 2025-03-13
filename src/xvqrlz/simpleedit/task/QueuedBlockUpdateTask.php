<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\task;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
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
        while (!empty($this->blockPool)) {
            $blockData = array_pop($this->blockPool);
            $position = $blockData->getPosition();
            
            $currentBlock = $this->level->getBlock($position);
            $newBlock = Block::get($blockData->getId(), $blockData->getMeta());

            if ($newBlock->getId() === BlockIds::AIR && $currentBlock->getId() === BlockIds::AIR) {
                continue;
            }

            $this->level->setBlock($position, $newBlock);
            ($this->onTick)($blockData, $newBlock, $this->level, $this->blockPool);
            return;
        }

        ($this->onComplete)();
        $this->getHandler()?->cancel();
    }
}