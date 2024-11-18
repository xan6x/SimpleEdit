<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\manager;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use xvqrlz\simpleedit\data\BlockData;
use xvqrlz\simpleedit\data\BlockStorage;
use xvqrlz\simpleedit\utils\Utils;

final class EditManager
{
    private array $selections = [];
    private array $history = [];
    private array $clipboard = [];

    public function __construct(private PluginBase $plugin) {}

    public function setPosition(Player $player, Position $position, int $pos): void
    {
        $name = strtolower($player->getName());
        $this->selections[$name] ??= [null, null];
        $this->selections[$name][$pos - 1] = $position;
        $player->sendMessage("§aPosition $pos set to ({$position->getFloorX()}, {$position->getFloorY()}, {$position->getFloorZ()}).");
    }

    public function getPosition(Player $player, int $pos): ?Position
    {
        $name = strtolower($player->getName());
        return $this->selections[$name][$pos - 1] ?? null;
    }

    public function setRegion(Player $player, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage("§cBoth positions must be set.");
            return;
        }

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);

        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->history[$name][] = $blockStorage;

        $blockPool = array_map(
            fn(BlockData $blockData) => new BlockData($blockId, $meta, $blockData->getPosition()),
            $blockStorage->getStorage()
        );

        Utils::scheduleTask($player, $blockPool, "§eBlocks replacing...", "§aRegion set complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function copy(Player $player): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage("§cBoth positions must be set.");
            return;
        }

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);
        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->clipboard[$name] = $blockStorage->getStorage();
        $player->sendMessage("§aCopied region to clipboard.");
    }

    public function paste(Player $player, Position $target): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if (!isset($this->clipboard[$name])) {
            $player->sendMessage("§cClipboard is empty.");
            return;
        }

        $clipboardData = $this->clipboard[$name];
        $origin = $clipboardData[0]->getPosition();
        $blockPool = [];

        foreach ($clipboardData as $blockData) {
            $relativePosition = $blockData->getPosition()->subtract($origin);
            $newPosition = $target->add($relativePosition->x, $relativePosition->y, $relativePosition->z);
            $blockPool[] = new BlockData($blockData->getId(), $blockData->getMeta(), $newPosition);
        }

        Utils::scheduleTask($player, $blockPool, "§aPasting blocks...", "§aPaste complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function replace(Player $player, int $oldBlockId, int $newBlockId, int $oldMeta = 0, int $newMeta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage("§cBoth positions must be set.");
            return;
        }

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);
        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->history[$name][] = $blockStorage;

        $blockPool = array_map(
            fn(BlockData $blockData) => $blockData->getId() === $oldBlockId && $blockData->getMeta() === $oldMeta
                ? new BlockData($newBlockId, $newMeta, $blockData->getPosition())
                : $blockData,
            $blockStorage->getStorage()
        );

        Utils::scheduleTask($player, $blockPool, "§eReplacing blocks...", "§aReplace complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function expand(Player $player, string $direction, int $amount): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage("§cBoth positions must be set.");
            return;
        }

        match (strtolower($direction)) {
            "up" => $pos2->y += $amount,
            "down" => $pos1->y -= $amount,
            "north" => $pos1->z -= $amount,
            "south" => $pos2->z += $amount,
            "west" => $pos1->x -= $amount,
            "east" => $pos2->x += $amount,
            default => $player->sendMessage("§cInvalid direction. Use correct: up, down, north, south, west, or east.")
        };

        $this->setPosition($player, $pos1, 1);
        $this->setPosition($player, $pos2, 2);

        $player->sendMessage("§aRegion expanded $amount blocks $direction.");
    }

    public function contract(Player $player, string $direction, int $amount): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage("§cBoth positions must be set.");
            return;
        }

        match (strtolower($direction)) {
            "up" => $pos2->y -= $amount,
            "down" => $pos1->y += $amount,
            "north" => $pos1->z += $amount,
            "south" => $pos2->z -= $amount,
            "west" => $pos1->x += $amount,
            "east" => $pos2->x -= $amount,
            default => $player->sendMessage("§cInvalid direction. Use correct: up, down, north, south, west, or east.")
        };

        $this->setPosition($player, $pos1, 1);
        $this->setPosition($player, $pos2, 2);

        $player->sendMessage("§aRegion contracted $amount blocks $direction.");
    }

    public function generateSphere(Player $player, Position $center, int $radius, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());
        
        if ($radius <= 0) {
            $player->sendMessage("§cRadius must be greater than 0.");
            return;
        }

        $blockPool = [];
        $centerX = $center->getFloorX();
        $centerY = $center->getFloorY();
        $centerZ = $center->getFloorZ();

        for ($x = -$radius; $x <= $radius; $x++) {
            for ($y = -$radius; $y <= $radius; $y++) {
                for ($z = -$radius; $z <= $radius; $z++) {
                    if (sqrt($x * $x + $y * $y + $z * $z) <= $radius) {
                        $blockPool[] = new BlockData($blockId, $meta, new Position($centerX + $x, $centerY + $y, $centerZ + $z, $player->getLevel()));
                    }
                }
            }
        }

        $this->history[$name][] = new BlockStorage($player->getLevel(), $centerX - $radius, $centerY - $radius, $centerZ - $radius, $centerX + $radius, $centerY + $radius, $centerZ + $radius);

        Utils::scheduleTask($player, $blockPool, "§eGenerating sphere...", "§aSphere generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function undo(Player $player): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if (empty($this->history[$name])) {
            $player->sendMessage("§cNo edits to undo.");
            return;
        }

        $blockStorage = array_pop($this->history[$name]);
        $blockPool = $blockStorage->getStorage();

        Utils::scheduleTask($player, $blockPool, "§eUndoing changes...", "§aUndo complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }
}
