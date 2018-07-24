<?php

declare(strict_types=1);

namespace proxy\command\base;

use proxy\command\Command;
use proxy\command\CommandMap;
use proxy\command\sender\Sender;
use proxy\hosts\ProxyClient;

/**
 * Class GamemodeCommand
 * @package proxy\command\base
 */
class FlyCommand extends Command
{

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * GamemodeCommand constructor
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap)
    {
        parent::__construct("fly", "Switch flying mode");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool
    {
        if (!$sender instanceof ProxyClient) {
            $sender->sendMessage("§cThis command can be used only in game.");
            return true;
        }
        $sender->setAllowFly(!$sender->getAllowFly(), true);
        $sender->sendMessage("§aFlying mode switched!");
        return true;
    }
}