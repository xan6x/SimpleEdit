<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\task\queue;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\task\QueuedBlockUpdateTask;

final class TaskQueue
{
    private static array $queue = [];

    public static function add(Player $player, array $blockPool, string $popupMessage, string $completionMessage): void
    {
        $name = strtolower($player->getName());
        if (!isset(self::$queue[$name])) {
            self::$queue[$name] = [
                'tasks' => [],
                'isProcessing' => false,
                'taskId' => 0
            ];
        }

        $taskId = ++self::$queue[$name]['taskId'];

        $task = new QueuedBlockUpdateTask(
            $player->getLevel(),
            $blockPool,
            fn() => $player->sendPopup($popupMessage),
            function() use ($name, $player, $completionMessage) {
                $player->sendMessage($completionMessage);
                self::complete($name);
            }
        );

        self::$queue[$name]['tasks'][] = $task;
        $player->sendMessage(TextFormat::GREEN . "Task #{$taskId} added to the queue.");

        if (!self::$queue[$name]['isProcessing']) {
            self::next($name);
        }
    }

    private static function next(string $name): void
    {
        if (!empty(self::$queue[$name]['tasks'])) {
            if (!self::$queue[$name]['isProcessing']) {
                self::$queue[$name]['isProcessing'] = true;

                $taskId = self::$queue[$name]['taskId'] - count(self::$queue[$name]['tasks']) + 1;

                $player = Server::getInstance()->getPlayerExact($name);

                if ($player) {
                    $player->sendMessage(TextFormat::YELLOW . "Task #{$taskId} is now starting.");
                }

                $task = array_shift(self::$queue[$name]['tasks']);
                Server::getInstance()->getScheduler()->scheduleRepeatingTask($task, 1);
            }
        } else {
            self::$queue[$name]['isProcessing'] = false;
        }
    }

    private static function complete(string $name): void
    {
        self::$queue[$name]['isProcessing'] = false;
        self::next($name);
    }

    public static function cancel(Player $player, Task $task): void
    {
        $name = strtolower($player->getName());
        if (isset(self::$queue[$name])) {
            $key = array_search($task, self::$queue[$name]['tasks'], true);
            if ($key !== false) {
                unset(self::$queue[$name]['tasks'][$key]);
                self::$queue[$name]['tasks'] = array_values(self::$queue[$name]['tasks']);
            }
            $task->getHandler()->cancel();
        }
    }

    public static function clear(Player $player): void
    {
        $name = strtolower($player->getName());
        if (isset(self::$queue[$name])) {
            foreach (self::$queue[$name]['tasks'] as $task) {
                self::cancel($player, $task);
            }
            self::$queue[$name]['tasks'] = [];
            self::$queue[$name]['isProcessing'] = false;
        }
    }

    public static function getAll(Player $player): array
    {
        $name = strtolower($player->getName());
        return self::$queue[$name]['tasks'] ?? [];
    }
}