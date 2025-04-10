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

class Pos1Command extends Command
{

    public function __construct(private Loader $plugin)
    {
        parent::__construct("/pos1", "Sets the first position for region selection", "//pos1", []);
        $this->setPermission("simpleedit.command.pos1");
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

        $position = $sender->getPosition();
        EditManager::getInstance()->setPosition($sender, $position, 1);

        return true;
    }
}