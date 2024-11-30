<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\trait;

use pocketmine\Player;
use pocketmine\level\Position;
use xvqrlz\simpleedit\translation\Translator;

trait PositionTrait
{
    private array $selections = [];

    public function setPosition(Player $player, Position $position, int $pos): void
    {
        $name = strtolower($player->getName());
        $this->selections[$name] ??= [null, null];
        $this->selections[$name][$pos - 1] = $position;
        $player->sendMessage(Translator::translate(
            "position.set",
            $player,
            [
                $pos,
                $position->getFloorX(),
                $position->getFloorY(),
                $position->getFloorZ()
            ]
        ));
    }

    public function getPosition(Player $player, int $pos): ?Position
    {
        $name = strtolower($player->getName());
        return $this->selections[$name][$pos - 1] ?? null;
    }
}