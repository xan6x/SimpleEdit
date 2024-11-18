<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\Loader;

class Pos2Command extends Command
{

    public function __construct(private Loader $plugin)
    {
        parent::__construct("/pos2", "Sets the second position for region selection", "//pos2", []);
        $this->setPermission("simpleedit.command.pos2");
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

        $position = $sender->getPosition();
        $this->plugin->getEditManager()->setPosition($sender, $position, 2);

        return true;
    }
}