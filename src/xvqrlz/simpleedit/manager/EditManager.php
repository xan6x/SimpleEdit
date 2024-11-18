<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\manager;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\level\Position;
use xvqrlz\simpleedit\data\BlockData;
use xvqrlz\simpleedit\data\BlockStorage;
use xvqrlz\simpleedit\task\QueuedBlockUpdateTask;

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

        $minX = min($pos1->getFloorX(), $pos2->getFloorX());
        $minY = min($pos1->getFloorY(), $pos2->getFloorY());
        $minZ = min($pos1->getFloorZ(), $pos2->getFloorZ());
        $maxX = max($pos1->getFloorX(), $pos2->getFloorX());
        $maxY = max($pos1->getFloorY(), $pos2->getFloorY());
        $maxZ = max($pos1->getFloorZ(), $pos2->getFloorZ());

        $blockStorage = new BlockStorage(
            $player->getLevel(),
            $minX, $minY, $minZ,
            $maxX, $maxY, $maxZ
        );

        $this->history[$name][] = $blockStorage;

        $blockPool = array_map(
            fn(BlockData $blockData) => new BlockData(
                $blockId,
                $meta,
                $blockData->getPosition()
            ),
            $blockStorage->getStorage()
        );

        $task = new QueuedBlockUpdateTask(
            $player->getLevel(),
            $blockPool,
            fn() => $player->sendPopup("§eBlocks replacing..."),
            fn() => $player->sendMessage("§aRegion set complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
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

        $minX = min($pos1->getFloorX(), $pos2->getFloorX());
        $minY = min($pos1->getFloorY(), $pos2->getFloorY());
        $minZ = min($pos1->getFloorZ(), $pos2->getFloorZ());
        $maxX = max($pos1->getFloorX(), $pos2->getFloorX());
        $maxY = max($pos1->getFloorY(), $pos2->getFloorY());
        $maxZ = max($pos1->getFloorZ(), $pos2->getFloorZ());

        $blockStorage = new BlockStorage(
            $player->getLevel(),
            $minX, $minY, $minZ,
            $maxX, $maxY, $maxZ
        );

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

        $minX = $origin->getFloorX();
        $minY = $origin->getFloorY();
        $minZ = $origin->getFloorZ();
        $maxX = $origin->getFloorX();
        $maxY = $origin->getFloorY();
        $maxZ = $origin->getFloorZ();

        foreach ($clipboardData as $blockData) {
            $relativePosition = $blockData->getPosition()->subtract($origin);
            $newPosition = $target->add($relativePosition->x, $relativePosition->y, $relativePosition->z);
            $blockPool[] = new BlockData($blockData->getId(), $blockData->getMeta(), $newPosition);

            $maxX = max($maxX, $newPosition->getFloorX());
            $maxY = max($maxY, $newPosition->getFloorY());
            $maxZ = max($maxZ, $newPosition->getFloorZ());
        }

        $task = new QueuedBlockUpdateTask(
            $player->getLevel(),
            $blockPool,
            fn() => $player->sendPopup("§aPasting blocks..."),
            fn() => $player->sendMessage("§aPaste complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
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

        $minX = min($pos1->getFloorX(), $pos2->getFloorX());
        $minY = min($pos1->getFloorY(), $pos2->getFloorY());
        $minZ = min($pos1->getFloorZ(), $pos2->getFloorZ());
        $maxX = max($pos1->getFloorX(), $pos2->getFloorX());
        $maxY = max($pos1->getFloorY(), $pos2->getFloorY());
        $maxZ = max($pos1->getFloorZ(), $pos2->getFloorZ());

        $blockStorage = new BlockStorage(
            $player->getLevel(),
            $minX, $minY, $minZ,
            $maxX, $maxY, $maxZ
        );

        $this->history[$name][] = $blockStorage;

        $blockPool = array_map(
            fn(BlockData $blockData) => $blockData->getId() === $oldBlockId && $blockData->getMeta() === $oldMeta
                ? new BlockData($newBlockId, $newMeta, $blockData->getPosition())
                : $blockData,
            $blockStorage->getStorage()
        );

        $task = new QueuedBlockUpdateTask(
            $player->getLevel(),
            $blockPool,
            fn() => $player->sendPopup("§eReplacing blocks..."),
            fn() => $player->sendMessage("§aReplace complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
    }

    public function undo(Player $player): void
    {
        $startTime = microtime(true);

        $name = strtolower($player->getName());

        if (!isset($this->history[$name]) || empty($this->history[$name])) {
            $player->sendMessage("§cNo edits to undo.");
            return;
        }

        $blockStorage = array_pop($this->history[$name]);
        $blockPool = $blockStorage->getStorage();

        $task = new QueuedBlockUpdateTask(
            $player->getLevel(),
            $blockPool,
            fn() => $player->sendPopup("§eUndoing changes..."),
            fn() => $player->sendMessage("§aUndo complete in " . round((microtime(true) - $startTime) * 1000, 2) . " ms.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
    }
}