<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\Loader;

class CopyCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("copy", "Copy the selected region to clipboard", "/copy", []);
        $this->setPermission("simpleedit.command.copy");
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

        $this->plugin->getEditManager()->copy($sender);
        return true;
    }
}