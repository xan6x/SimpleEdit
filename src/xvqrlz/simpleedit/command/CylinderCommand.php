<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\Loader;

class CylinderCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/cylinder", "Generates a cylinder of blocks", "//cylinder <radius> <height> <block_id>[:<meta>]", []);
        $this->setPermission("simpleedit.command.cylinder");
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

        if (count($args) < 3) {
            $sender->sendMessage(TextFormat::RED . "Invalid usage. Correct usage: /cylinder <radius> <height> <block_id>[:<meta>]");
            return false;
        }

        $radius = (int)$args[0];
        $height = (int)$args[1];
        $blockData = explode(":", $args[2]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        $center = $sender->getPosition();

        EditManager::getInstance()->generateCylinder($sender, $center, $radius, $height, $blockId, $meta);

        return true;
    }
}