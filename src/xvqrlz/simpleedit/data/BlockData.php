<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\data;

use pocketmine\math\Vector3;

class BlockData
{
    private int $blockId;
    private int $meta;
    private Vector3 $position;

    public function __construct(int $blockId, int $meta, Vector3 $position)
    {
        $this->blockId = $blockId;
        $this->meta = $meta;
        $this->position = $position;
    }

    public function getId(): int
    {
        return $this->blockId;
    }

    public function getMeta(): int
    {
        return $this->meta;
    }

    public function getPosition(): Vector3
    {
        return $this->position;
    }
}