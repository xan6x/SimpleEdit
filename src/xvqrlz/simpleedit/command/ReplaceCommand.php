<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\translation\Translator;
use xvqrlz\simpleedit\Loader;

class ReplaceCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("/replace", "Replaces blocks in the selected region", "//replace <old_block_id>[:<old_meta>] <new_block_id>[:<new_meta>]", []);
        $this->setPermission("simpleedit.command.replace");
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
            $sender->sendMessage(Translator::translate("invalid.usage.replace", $sender));
            return false;
        }

        $oldBlockData = explode(":", $args[0]);
        $oldBlockId = (int)$oldBlockData[0];
        $oldMeta = isset($oldBlockData[1]) ? (int)$oldBlockData[1] : 0;

        $newBlockData = explode(":", $args[1]);
        $newBlockId = (int)$newBlockData[0];
        $newMeta = isset($newBlockData[1]) ? (int)$newBlockData[1] : 0;

        EditManager::getInstance()->replace($sender, $oldBlockId, $newBlockId, $oldMeta, $newMeta);

        return true;
    }
}