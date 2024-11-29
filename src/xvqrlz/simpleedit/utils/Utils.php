<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\utils;

use pocketmine\level\Position;

final class Utils
{
    public static function calculateBounds(Position $pos1, Position $pos2): array
    {
        return [
            min($pos1->getFloorX(), $pos2->getFloorX()),
            min($pos1->getFloorY(), $pos2->getFloorY()),
            min($pos1->getFloorZ(), $pos2->getFloorZ()),
            max($pos1->getFloorX(), $pos2->getFloorX()),
            max($pos1->getFloorY(), $pos2->getFloorY()),
            max($pos1->getFloorZ(), $pos2->getFloorZ()),
        ];
    }
}