<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\manager;

use pocketmine\Player;
use pocketmine\block\BlockIds;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use xvqrlz\simpleedit\data\BlockData;
use xvqrlz\simpleedit\data\BlockStorage;
use xvqrlz\simpleedit\utils\Utils;
use xvqrlz\simpleedit\task\queue\TaskQueue;
use xvqrlz\simpleedit\trait\PositionTrait;
use xvqrlz\simpleedit\trait\ClipboardTrait;
use xvqrlz\simpleedit\trait\HistoryTrait;
use xvqrlz\simpleedit\translation\Translator;

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
            $player->sendMessage(Translator::translate("positions.not.set", $player));
            return;
        }

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);

        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->addHistory($name, $blockStorage);

        $blockPool = array_map(
            fn(BlockData $blockData) => new BlockData($blockId, $meta, $blockData->getPosition()),
            $blockStorage->getStorage()
        );

        TaskQueue::add($player, $blockPool, Translator::translate("blocks.replacing", $player), Translator::translate("region.set.complete", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function copy(Player $player): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
            return;
        }

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);
        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->copyClipboard($name, $blockStorage->getStorage());
        $player->sendMessage(Translator::translate("region.copied", $player));
    }

    public function paste(Player $player, Position $target): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($this->getClipboard($name) == null) {
            $player->sendMessage(Translator::translate("clipboard.empty", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("pasting.blocks", $player), Translator::translate("paste.complete", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function replace(Player $player, int $oldBlockId, int $newBlockId, int $oldMeta = 0, int $newMeta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("blocks.replacing", $player), Translator::translate("replace.complete", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function expand(Player $player, string $direction, int $amount): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
            return;
        }

        match (strtolower($direction)) {
            "up" => $pos2->y += $amount,
            "down" => $pos1->y -= $amount,
            "north" => $pos1->z -= $amount,
            "south" => $pos2->z += $amount,
            "west" => $pos1->x -= $amount,
            "east" => $pos2->x += $amount,
            default => $player->sendMessage(Translator::translate("invalid.direction", $player))
        };

        $this->setPosition($player, $pos1, 1);
        $this->setPosition($player, $pos2, 2);

        $player->sendMessage(Translator::translate("region.expanded", $player, [$amount, $direction]));
    }

    public function contract(Player $player, string $direction, int $amount): void
    {
        $name = strtolower($player->getName());
        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
            return;
        }

        match (strtolower($direction)) {
            "up" => $pos2->y -= $amount,
            "down" => $pos1->y += $amount,
            "north" => $pos1->z += $amount,
            "south" => $pos2->z -= $amount,
            "west" => $pos1->x += $amount,
            "east" => $pos2->x -= $amount,
            default => $player->sendMessage(Translator::translate("invalid.direction", $player))
        };

        $this->setPosition($player, $pos1, 1);
        $this->setPosition($player, $pos2, 2);

        $player->sendMessage(Translator::translate("region.contracted", $player, [$amount, $direction]));
    }

    public function generateCylinder(Player $player, Position $center, int $radius, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($radius <= 0 || $height <= 0) {
            $player->sendMessage(Translator::translate("radius.height.invalid", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("generating.cylinder", $player), Translator::translate("cylinder.generated", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function generatePyramid(Player $player, Position $center, int $baseWidth, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($baseWidth <= 0 || $height <= 0) {
            $player->sendMessage(Translator::translate("base.width.height.invalid", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("generating.pyramid", $player), Translator::translate("pyramid.generated", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function generateSpiral(Player $player, Position $center, int $radius, int $height, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if ($radius <= 0 || $height <= 0) {
            $player->sendMessage(Translator::translate("radius.height.invalid", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("generating.spiral", $player), Translator::translate("spiral.generated", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function generateWalls(Player $player, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("generating.walls", $player), Translator::translate("walls.generated", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function generateSphere(Player $player, Position $center, int $radius, int $blockId, int $meta = 0): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());
        
        if ($radius <= 0) {
            $player->sendMessage(Translator::translate("radius.invalid", $player));
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

        TaskQueue::add($player, $blockPool, Translator::translate("generating.sphere", $player), Translator::translate("sphere.generated", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function rotate(Player $player, int $angle): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        $pos1 = $this->getPosition($player, 1);
        $pos2 = $this->getPosition($player, 2);

        if ($pos1 === null || $pos2 === null) {
            $player->sendMessage(Translator::translate("positions.not.set", $player));
            return;
        }

        $centerX = ($pos1->x + $pos2->x) / 2;
        $centerY = ($pos1->y + $pos2->y) / 2;
        $centerZ = ($pos1->z + $pos2->z) / 2;

        $angleRad = deg2rad($angle);
        $sin = sin($angleRad);
        $cos = cos($angleRad);

        [$minX, $minY, $minZ, $maxX, $maxY, $maxZ] = Utils::calculateBounds($pos1, $pos2);

        $this->setRegion($player, BlockIds::AIR);

        $blockStorage = new BlockStorage($player->getLevel(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        $this->addHistory($name, $blockStorage);

        $blockPool = [];

        foreach ($blockStorage->getStorage() as $blockData) {
            $pos = $blockData->getPosition();
            $relativeX = $pos->x - $centerX;
            $relativeZ = $pos->z - $centerZ;

            $rotatedX = $relativeX * $cos - $relativeZ * $sin;
            $rotatedZ = $relativeX * $sin + $relativeZ * $cos;

            $newPosition = new Position(
                (int) round($centerX + $rotatedX),
                $pos->y,
                (int) round($centerZ + $rotatedZ),
                $player->getLevel()
            );

            $blockPool[] = new BlockData($blockData->getId(), $blockData->getMeta(), $newPosition);
        }

        TaskQueue::add($player, $blockPool, Translator::translate("rotating.blocks", $player), Translator::translate("rotation.complete", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }

    public function undo(Player $player): void
    {
        $startTime = microtime(true);
        $name = strtolower($player->getName());

        if (empty($this->getLastHistory($name))) {
            $player->sendMessage(Translator::translate("no.edits.undo", $player));
            return;
        }

        $blockStorage = $this->removeHistory($name);
        $blockPool = $blockStorage->getStorage();

        TaskQueue::add($player, $blockPool, Translator::translate("undoing.changes", $player), Translator::translate("undo.complete", $player, [round((microtime(true) - $startTime) * 1000, 2)]));
    }
}
