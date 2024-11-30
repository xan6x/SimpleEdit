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

class PasteCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/paste", "Paste the copied region at your current position", "//paste", []);
        $this->setPermission("simpleedit.command.paste");
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
        EditManager::getInstance()->paste($sender, $position);
        return true;
    }
}