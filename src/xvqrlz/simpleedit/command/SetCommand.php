<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xvqrlz\simpleedit\Loader;

class SetCommand extends Command
{

    public function __construct(private Loader $plugin)
    {
        parent::__construct("set", "Sets blocks in the selected region", "/set <block_id>[:<meta>]", []);
        $this->setPermission("simpleedit.command.set");
    }

    public function execute(CommandSender $sender, $label, array $args): bool
    {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . "Usage: /set <block_id>[:<meta>]");
            return false;
        }

        $blockData = explode(":", $args[0]);
        $blockId = (int)$blockData[0];
        $meta = isset($blockData[1]) ? (int)$blockData[1] : 0;

        $this->plugin->getEditManager()->setRegion($sender, $blockId, $meta);

        return true;
    }
}