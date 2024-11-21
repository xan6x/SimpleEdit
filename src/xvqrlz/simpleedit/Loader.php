<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit;

use pocketmine\plugin\PluginBase;
use xvqrlz\simpleedit\command\SetCommand;
use xvqrlz\simpleedit\command\UndoCommand;
use xvqrlz\simpleedit\command\Pos1Command;
use xvqrlz\simpleedit\command\Pos2Command;
use xvqrlz\simpleedit\command\CopyCommand;
use xvqrlz\simpleedit\command\PasteCommand;
use xvqrlz\simpleedit\command\ReplaceCommand;
use xvqrlz\simpleedit\command\ExpandCommand;
use xvqrlz\simpleedit\command\ContractCommand;
use xvqrlz\simpleedit\command\SphereCommand;
use xvqrlz\simpleedit\manager\EditManager;

final class Loader extends PluginBase
{

    public function onEnable(): void
    {
        EditManager::setInstance(new EditManager($this));

        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $commands = [
            new SetCommand($this),
            new UndoCommand($this),
            new Pos1Command($this),
            new Pos2Command($this),
            new CopyCommand($this),
            new PasteCommand($this),
            new ReplaceCommand($this),
            new ExpandCommand($this),
            new ContractCommand($this),
            new SphereCommand($this)
        ];

        $this->getServer()->getCommandMap()->registerAll("simpleedit", $commands);
    }
}