<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\command\SetCommand;
use xvqrlz\simpleedit\command\UndoCommand;
use xvqrlz\simpleedit\command\Pos1Command;
use xvqrlz\simpleedit\command\Pos2Command;
use xvqrlz\simpleedit\command\CopyCommand;
use xvqrlz\simpleedit\command\PasteCommand;

final class Loader extends PluginBase
{
    private static self $instance;
    private EditManager $editManager;

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->editManager = new EditManager($this);

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
            new PasteCommand($this)
        ];

        foreach ($commands as $command) {
            $this->registerCommand($command);
        }
    }

    private function registerCommand(Command $command): void
    {
        $this->getServer()->getCommandMap()->register($command->getName(), $command);
    }

    public function getEditManager(): EditManager
    {
        return $this->editManager;
    }
}