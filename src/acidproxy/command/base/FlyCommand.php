<?php

declare(strict_types=1);

namespace acidproxy\command\base;

use acidproxy\Client;
use acidproxy\command\Command;
use acidproxy\command\CommandMap;
use acidproxy\command\sender\Sender;

/**
 * Class GamemodeCommand
 * @package proxy\command\base
 */
class FlyCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * GamemodeCommand constructor
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        parent::__construct("fly", "Switch flying mode");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        if (!$sender instanceof Client) {
            $sender->sendMessage("§cThis command can be used only in game.");
            return true;
        }
        $sender->setAllowFly(!$sender->getAllowFly(), true);
        $sender->sendMessage("§aFlying mode switched!");
        return true;
    }
}