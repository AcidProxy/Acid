<?php

namespace proxy\command;

use proxy\command\sender\Sender;
use proxy\hosts\ProxyClient;

/**
 * Class Command
 * @package proxy\command
 */
abstract class Command {

    /** @var string $name */
    private $name;

    /** @var string $usageMessage */
    private $usageMessage;

    /** @var string $description */
    private $description;

    /**
     * Command constructor.
     * @param string $name
     * @param string|null $usageMessage
     */
    public function __construct(string $name, string $usageMessage = null) {
        $this->name = $name;
        $this->usageMessage = is_null($this->usageMessage) ? "" : $this->usageMessage;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription() : string{
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description){
        $this->description = $description;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    abstract public function execute(Sender $sender, array $args) : bool;

}