<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\manager\EditManager;
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
            $sender->sendMessage(TextFormat::RED . "Only in-game.");
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . "Invalid usage. Correct usage: /rotate <angle>");
            return false;
        }

        $angle = (int) $args[0];

        if ($angle === 0) {
            $sender->sendMessage(TextFormat::RED . "Angle must be non-zero.");
            return false;
        }

        EditManager::getInstance()->rotate($sender, $angle);
        return true;
    }
}