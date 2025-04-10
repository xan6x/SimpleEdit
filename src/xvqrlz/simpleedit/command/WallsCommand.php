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

class WallsCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/walls", "Generates walls between two positions", "//walls <block_id>[:<meta>]", []);
        $this->setPermission("simpleedit.command.walls");
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

        if (count($args) < 1) {
            $sender->sendMessage(Translator::translate("invalid.usage.walls", $sender));
            return false;
        }

        $blockData = explode(":", $args[0]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        EditManager::getInstance()->generateWalls($sender, $blockId, $meta);

        return true;
    }
}