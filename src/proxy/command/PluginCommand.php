<?php namespace proxy\command;


use proxy\hosts\ProxyClient;
use proxy\plugin\PluginInterface;

class PluginCommand extends Command
{

    /**
     * @var PluginInterface $owningPlugin
     */
    private $owningPlugin;

    /**
     * PluginCommand constructor.
     * @param $name
     * @param null $usageMessage
     * @param PluginInterface $owningPlugin
     */
    public function __construct($name, $usageMessage = null, PluginInterface $owningPlugin)
    {
        $this->owningPlugin = $owningPlugin;
        parent::__construct($name, $usageMessage);
    }

    /**
     * @return PluginInterface
     */
    public function getOwningPlugin() : PluginInterface{
        return $this->owningPlugin;
    }

    /**
     * @param ProxyClient $sender
     * @param array $args
     * @return bool
     */
    public function execute(ProxyClient $sender, array $args): bool
    {
        $this->getOwningPlugin()->onCommand($sender, $this->getName(), $args);
        return true;
    }

}