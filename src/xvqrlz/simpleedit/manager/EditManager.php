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
            fn() => $player->sendPopup("§aBlock replaced."),
            fn() => $player->sendMessage("§aRegion set complete.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
    }

    public function undo(Player $player): void
    {
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
            fn() => $player->sendMessage("§aUndo complete.")
        );

        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
    }
}