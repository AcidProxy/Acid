<?php

declare(strict_types=1);

namespace proxy\command;

use proxy\command\sender\Sender;
use proxy\plugin\PluginInterface;

/**
 * Class PluginCommand
 * @package proxy\command
 */
class PluginCommand extends Command {

    /** @var PluginInterface $owningPlugin */
    private $owningPlugin;

    /**
     * PluginCommand constructor.
     * @param $name
     * @param null $usageMessage
     * @param PluginInterface $owningPlugin
     */
    public function __construct($name, $usageMessage = null, PluginInterface $owningPlugin) {
        $this->owningPlugin = $owningPlugin;
        parent::__construct($name, $usageMessage);
    }

    /**
     * @return PluginInterface $plugin
     */
    public function getOwningPlugin(): PluginInterface{
        return $this->owningPlugin;
    }

    /**
     * @param Sender $sender
     * @param array $args
     *
     * @return bool
     * why bool? o_O
     */
    public function execute(Sender $sender, array $args): bool {
        $this->getOwningPlugin()->onCommand($sender, $this->getName(), $args);
        return true;
    }

}