<?php

declare(strict_types=1);

namespace xvqrlz\simpleedit;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
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
use xvqrlz\simpleedit\command\CylinderCommand;
use xvqrlz\simpleedit\command\SpiralCommand;
use xvqrlz\simpleedit\command\PyramidCommand;
use xvqrlz\simpleedit\command\WallsCommand;
use xvqrlz\simpleedit\command\RotateCommand;
use xvqrlz\simpleedit\manager\EditManager;
use xvqrlz\simpleedit\translation\Translator;

final class Loader extends PluginBase
{
    use SingletonTrait;

    public function onEnable(): void
    {
        self::setInstance($this);

        Translator::initialize($this);
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
            new SphereCommand($this),
            new CylinderCommand($this),
            new SpiralCommand($this),
            new PyramidCommand($this),
            new WallsCommand($this),
            new RotateCommand($this)
        ];

        $this->getServer()->getCommandMap()->registerAll("simpleedit", $commands);
    }
}