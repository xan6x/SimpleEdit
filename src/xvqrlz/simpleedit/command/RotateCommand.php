<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\translation\Translator;
use xvqrlz\simpleedit\Loader;

class RotateCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/rotate", "Rotate the selected region by a specified angle", "//rotate", []);
        $this->setPermission("simpleedit.command.rotate");
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
            $sender->sendMessage(Translator::translate("invalid.usage.rotate", $sender));
            return false;
        }

        $angle = (int)$args[0];

        if ($angle === 0) {
            $sender->sendMessage(Translator::translate("angle.must.be.non.zero", $sender));
            return false;
        }

        EditManager::getInstance()->rotate($sender, $angle);
        return true;
    }
}