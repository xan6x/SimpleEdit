<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\translation\Translator;
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
            $sender->sendMessage(Translator::translate("only.in.game", $sender));
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(Translator::translate("invalid.usage.sphere", $sender));
            return false;
        }

        $radius = (int)$args[0];
        $blockData = explode(":", $args[1]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        $center = $sender->getPosition();

        EditManager::getInstance()->generateSphere($sender, $center, $radius, $blockId, $meta);

        return true;
    }
}