<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\Loader;

class SphereCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/sphere", "Generates a sphere of blocks", "//sphere <radius> <block_id>[:<meta>]", []);
        $this->setPermission("simpleedit.command.sphere");
    }

    public function execute(CommandSender $sender, $label, array $args): bool
    {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Only in-game");
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Invalid usage. Correct usage: /sphere <radius> <block_id>[:<meta>]");
            return false;
        }

        $radius = (int)$args[0];
        $blockData = explode(":", $args[1]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        $center = $sender->getPosition();

        $this->plugin->getEditManager()->generateSphere($sender, $center, $radius, $blockId, $meta);

        return true;
    }
}