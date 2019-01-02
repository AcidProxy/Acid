<?php

declare(strict_types=1);

namespace acidproxy\command\base;

use acidproxy\command\Command;
use acidproxy\command\CommandMap;
use acidproxy\command\sender\ConsoleCommandSender;
use acidproxy\command\sender\Sender;

/**
 * Class HelpCommand
 * @package proxy\command\base
 */
class HelpCommand extends Command{

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
        $msg = [str_repeat("§e#", 40)];

        $prefix = $sender instanceof ConsoleCommandSender ? "" : ".";

        /**
         * @var string $name
         * @var Command $object
         */
        foreach ($list as $name => $object) {
            $msg[] = "§e• §b{$prefix}{$name} §f{$object->getDescription()}";
        }
        $msg[] = $msg[0];

        $sender->sendMessage(implode(PHP_EOL, $msg));
        return true;
    }
}