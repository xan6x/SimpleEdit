<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\translation\Translator;
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
            $sender->sendMessage(Translator::translate("only.in.game", $sender));
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(Translator::translate("invalid.usage.contract", $sender));
            return false;
        }

        $direction = $args[0];
        $amount = (int)$args[1];

        EditManager::getInstance()->contract($sender, $direction, $amount);

        return true;
    }
}