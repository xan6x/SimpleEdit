<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\Loader;

class ExpandCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/expand", "Expands the selected region", "//expand <direction> <amount>", []);
        $this->setPermission("simpleedit.command.expand");
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
            $sender->sendMessage(TextFormat::RED . "Invalid usage. Correct usage: /expand <direction> <amount>");
            return false;
        }

        $direction = $args[0];
        $amount = (int)$args[1];

        EditManager::getInstance()->expand($sender, $direction, $amount);

        return true;
    }
}