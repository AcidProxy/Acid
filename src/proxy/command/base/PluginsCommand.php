<?php

declare(strict_types=1);

namespace proxy\command\base;

use proxy\command\Command;
use proxy\command\CommandMap;
use proxy\command\sender\Sender;

/**
 * Class PluginsCommand
 * @package proxy\command\base
 */
class PluginsCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * PluginsCommand constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        $this->commandMap = $commandMap;
        parent::__construct("plugins", "Displays list of plugins");
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        $sender->sendMessage("§fPlugins: §a" . implode("§f, §a", array_keys($this->commandMap->getProxy()->getPluginManager()->getPlugins())));
        return true;
    }
}