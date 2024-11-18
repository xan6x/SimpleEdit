<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\Loader;

class ContractCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/contract", "Contracts the selected region", "//contract <direction> <amount>", []);
        $this->setPermission("simpleedit.command.contract");
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
            $sender->sendMessage(TextFormat::RED . "Invalid usage. Correct usage: /contract <direction> <amount>");
            return false;
        }

        $direction = $args[0];
        $amount = (int)$args[1];

        $this->plugin->getEditManager()->contract($sender, $direction, $amount);

        return true;
    }
}