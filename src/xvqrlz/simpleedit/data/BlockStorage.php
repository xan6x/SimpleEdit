<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\data;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class BlockStorage
{
    /** @var BlockData[] */
    private array $storage = [];
    private Level $level;

    public function __construct(
        Level $level,
        int $minX, int $minY, int $minZ,
        int $maxX, int $maxY, int $maxZ
    ) {
        $this->level = $level;
        $this->initializeStorage($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
    }

    private function initializeStorage(
        int $minX, int $minY, int $minZ,
        int $maxX, int $maxY, int $maxZ
    ): void {
        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($y = $minY; $y <= $maxY; ++$y) {
                for ($z = $minZ; $z <= $maxZ; ++$z) {
                    $block = $this->level->getBlockAt($x, $y, $z);
                    $this->storage[] = new BlockData($block->getId(), $block->getDamage(), new Vector3($x, $y, $z));
                }
            }
        }
    }

    /**
     * @return BlockData[]
     */
    public function getAirBlocks(): array
    {
        return array_filter(
            $this->storage,
            fn($block) => $block->getId() === BlockIds::AIR
        );
    }

    /**
     * @return BlockData[]
     */
    public function getStorage(): array
    {
        return $this->storage;
    }

    public function clear(): void
    {
        $airBlock = Block::get(BlockIds::AIR);
        foreach ($this->storage as $block) {
            $this->level->setBlock($block->getPosition(), $airBlock);
        }
    }
}