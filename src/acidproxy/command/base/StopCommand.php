<?php

declare(strict_types=1);

namespace acidproxy\command\base;

use acidproxy\Client;
use acidproxy\command\Command;
use acidproxy\command\CommandMap;
use acidproxy\command\sender\Sender;

/**
 * Class StopCommand
 * @package proxy\command\base
 */
class StopCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * StopCommand constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        parent::__construct("stop", "Stops proxy");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        $sender->sendMessage("§aStopping the server...");
        $this->commandMap->getProxy()->stop();
        return true;
    }
}