<?php

declare(strict_types=1);

namespace proxy\command\base;

use proxy\command\Command;
use proxy\command\CommandMap;
use proxy\command\sender\ConsoleCommandSender;
use proxy\command\sender\Sender;

/**
 * Class HelpCommand
 * @package proxy\command\base
 */
class HelpCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * HelpCommand constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        parent::__construct("help", "Shows all registered commands");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        $list = $this->commandMap->getCommands();
        $msg = ["§f--- All AcidProxy commands: ---"];

        $prefix = $sender instanceof ConsoleCommandSender ? "" : "*/";

        /**
         * @var string $name
         * @var Command $object
         */
        foreach ($list as $name => $object) {
            $msg[] = "§2{$prefix}{$name} §f{$object->getDescription()}";
        }

        $sender->sendMessage(implode(PHP_EOL, $msg));
        return true;
    }
}