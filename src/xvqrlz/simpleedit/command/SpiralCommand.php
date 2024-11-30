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

class SpiralCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/spiral", "Generates a spiral of blocks", "//spiral <radius> <height> <block_id>[:<meta>]", []);
        $this->setPermission("simpleedit.command.spiral");
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

        if (count($args) < 3) {
            $sender->sendMessage(Translator::translate("invalid.usage.spiral", $sender));
            return false;
        }

        $radius = (int)$args[0];
        $height = (int)$args[1];
        $blockData = explode(":", $args[2]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        $center = $sender->getPosition();

        EditManager::getInstance()->generateSpiral($sender, $center, $radius, $height, $blockId, $meta);

        return true;
    }
}