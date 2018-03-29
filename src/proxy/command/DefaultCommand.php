<?php namespace proxy\command;


use proxy\hosts\Client;
use proxy\hosts\ProxyClient;

class DefaultCommand extends Command
{

    /**
     * DefaultCommand constructor.
     * @param $name
     * @param null $usageMessage
     */
    public function __construct($name, $usageMessage = null)
    {
        parent::__construct($name, $usageMessage);
    }

    /**
     * @param ProxyClient $sender
     * @param array $args
     * @return bool
     */
    public function execute(ProxyClient $sender, array $args): bool
    {
        return true;
    }

}