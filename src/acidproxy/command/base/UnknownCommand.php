<?php

declare(strict_types=1);

namespace acidproxy\command\base;

use pocketmine\utils\Terminal;
use acidproxy\command\Command;
use acidproxy\command\sender\ConsoleCommandSender;
use acidproxy\command\sender\Sender;

/**
 * Class UnknownCommand
 * @package proxy\command\base
 */
class UnknownCommand extends Command {

    /**
     * @param Sender $sender
     * @param array $args
     *
     * @return bool $sendHasNotPerms
     */
    public function execute(Sender $sender, array $args): bool {
        if ($sender instanceof ConsoleCommandSender) {
            $sender->sendMessage(Terminal::$COLOR_RED . "Unknown command. Try 'help' to get list of all commands.");
            return true;
        }
        $sender->sendMessage("§cUnknown command. Try ./help to get list of all commands.");
        return true;
    }
}