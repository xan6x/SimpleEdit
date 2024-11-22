<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\manager;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use xvqrlz\simpleedit\data\BlockData;
use xvqrlz\simpleedit\data\BlockStorage;
use xvqrlz\simpleedit\utils\Utils;
use xvqrlz\simpleedit\trait\PositionTrait;
use xvqrlz\simpleedit\trait\ClipboardTrait;
use xvqrlz\simpleedit\trait\HistoryTrait;

final class EditManager
{
    use SingletonTrait;
    use PositionTrait;
    use ClipboardTrait;
    use HistoryTrait;

    public function __construct(private PluginBase $plugin) {}

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
        $this->addHistory($name, $blockStorage);

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
        $this->copyClipboard($name, $blockStorage->getStorage());
        $player->sendMessage("§aCopied region to clipboard.");
    }

    public function paste(Player $player, Position $target): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($this->getClipboard($name) == null) {
            $player->sendMessage("§cClipboard is empty.");
            return;
        }

        $clipboardData = $this->getClipboard($name);
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
        $this->addHistory($name, $blockStorage);

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

    public function generateCylinder(Player $player, Position $center, int $radius, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($radius <= 0 || $height <= 0) {
            $player->sendMessage("§cRadius and height must be greater than 0.");
            return;
        }

        $blockPool = [];
        $centerX = $center->getFloorX();
        $centerY = $center->getFloorY();
        $centerZ = $center->getFloorZ();

        for ($y = 0; $y < $height; $y++) {
            for ($x = -$radius; $x <= $radius; $x++) {
                for ($z = -$radius; $z <= $radius; $z++) {
                    if (sqrt($x * $x + $z * $z) <= $radius) {
                        $blockPool[] = new BlockData($blockId, $meta, new Position($centerX + $x, $centerY + $y, $centerZ + $z, $player->getLevel()));
                    }
                }
            }
        }

        $this->addHistory($name, new BlockStorage($player->getLevel(), $centerX - $radius, $centerY, $centerZ - $radius, $centerX + $radius, $centerY + $height, $centerZ + $radius));

        Utils::scheduleTask($player, $blockPool, "§eGenerating cylinder...", "§aCylinder generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function generatePyramid(Player $player, Position $center, int $baseWidth, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($baseWidth <= 0 || $height <= 0) {
            $player->sendMessage("§cBase width and height must be greater than 0.");
            return;
        }

        $blockPool = [];
        $centerX = $center->getFloorX();
        $centerY = $center->getFloorY();
        $centerZ = $center->getFloorZ();

        for ($y = 0; $y < $height; $y++) {
            $currentWidth = $baseWidth - $y * 2;
            if ($currentWidth <= 0) break;
            for ($x = -$currentWidth / 2; $x <= $currentWidth / 2; $x++) {
                for ($z = -$currentWidth / 2; $z <= $currentWidth / 2; $z++) {
                    $blockPool[] = new BlockData($blockId, $meta, new Position($centerX + $x, $centerY + $y, $centerZ + $z, $player->getLevel()));
                }
            }
        }

        $this->addHistory($name, new BlockStorage($player->getLevel(), $centerX - $baseWidth / 2, $centerY, $centerZ - $baseWidth / 2, $centerX + $baseWidth / 2, $centerY + $height, $centerZ + $baseWidth / 2));

        Utils::scheduleTask($player, $blockPool, "§eGenerating pyramid...", "§aPyramid generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function generateSpiral(Player $player, Position $center, int $radius, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($radius <= 0 || $height <= 0) {
            $player->sendMessage("§cRadius and height must be greater than 0.");
            return;
        }

        $blockPool = [];
        $centerX = $center->getFloorX();
        $centerY = $center->getFloorY();
        $centerZ = $center->getFloorZ();

        for ($y = 0; $y < $height; $y++) {
            $angle = $y * M_PI / 5;
            $x = (int)($radius * cos($angle));
            $z = (int)($radius * sin($angle));
            $blockPool[] = new BlockData($blockId, $meta, new Position($centerX + $x, $centerY + $y, $centerZ + $z, $player->getLevel()));
        }

        $this->addHistory($name, new BlockStorage($player->getLevel(), $centerX - $radius, $centerY, $centerZ - $radius, $centerX + $radius, $centerY + $height, $centerZ + $radius));

        Utils::scheduleTask($player, $blockPool, "§eGenerating spiral...", "§aSpiral generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function generateWalls(Player $player, int $blockId, int $meta = 0): void
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

        $blockPool = [];
        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $blockPool[] = new BlockData($blockId, $meta, new Position($x, $minY, $z, $player->getLevel()));
                $blockPool[] = new BlockData($blockId, $meta, new Position($x, $maxY, $z, $player->getLevel()));
            }
        }

        for ($y = $minY; $y <= $maxY; $y++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $blockPool[] = new BlockData($blockId, $meta, new Position($minX, $y, $z, $player->getLevel()));
                $blockPool[] = new BlockData($blockId, $meta, new Position($maxX, $y, $z, $player->getLevel()));
            }
        }

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($y = $minY; $y <= $maxY; $y++) {
                $blockPool[] = new BlockData($blockId, $meta, new Position($x, $y, $minZ, $player->getLevel()));
                $blockPool[] = new BlockData($blockId, $meta, new Position($x, $y, $maxZ, $player->getLevel()));
            }
        }

        $this->addHistory($name, new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ));

        Utils::scheduleTask($player, $blockPool, "§eGenerating walls...", "§aWalls generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
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

        $this->addHistory($name, new BlockStorage($player->getLevel(), $centerX - $radius, $centerY - $radius, $centerZ - $radius, $centerX + $radius, $centerY + $radius, $centerZ + $radius));

        Utils::scheduleTask($player, $blockPool, "§eGenerating sphere...", "§aSphere generated in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }

    public function undo(Player $player): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if (empty($this->getLastHistory($name))) {
            $player->sendMessage("§cNo edits to undo.");
            return;
        }

        $blockStorage = $this->removeHistory($name);
        $blockPool = $blockStorage->getStorage();

        Utils::scheduleTask($player, $blockPool, "§eUndoing changes...", "§aUndo complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.");
    }
}
