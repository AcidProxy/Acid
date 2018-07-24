<?php

declare(strict_types=1);

namespace proxy\command\base;

use proxy\command\Command;
use proxy\command\CommandMap;
use proxy\command\sender\ConsoleCommandSender;
use proxy\command\sender\Sender;

/**
 * Class GamemodeCommand
 * @package proxy\command\base
 */
class GamemodeCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * GamemodeCommand constructor
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        parent::__construct("gamemode", "Sets your game mode");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        if($sender instanceof ConsoleCommandSender) {
            $sender->sendMessage("§cThis command can be used only in game.");
            return true;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("§cInvalid args");
            return true;
        }
        if(!in_array($args[0], ["0", "1", "s", "c", "survival", "creative", "surv", "crea"])) {
            $sender->sendMessage("§cInvalid gamemode");
            return true;
        }
        switch ($args[0]) {
            case "0":
            case "s":
            case "surv":
            case "survival":
                $this->commandMap->getProxy()->getClient()->setGamemode(0);
                break;
            case "1":
            case "c":
            case "crea":
            case "creative":
                $this->commandMap->getProxy()->getClient()->setGamemode(1);
        }
        $sender->sendMessage("§aGamemode updated!");
        return true;
    }
}