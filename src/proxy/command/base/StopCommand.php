<?php

declare(strict_types=1);

namespace proxy\command\base;

use proxy\command\Command;
use proxy\command\CommandMap;
use proxy\command\sender\Sender;
use proxy\hosts\ProxyClient;

/**
 * Class StopCommand
 * @package proxy\command\base
 */
class StopCommand extends Command
{

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * StopCommand constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap)
    {
        parent::__construct("stop", "Stops proxy");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool
    {
        if ($sender instanceof ProxyClient) {
            $sender->sendMessage("§cThis command cannot be used in game.");
            return true;
        }
        $sender->sendMessage("§aStopping the server...");
        $this->commandMap->getProxy()->stop();
        return true;
    }
}