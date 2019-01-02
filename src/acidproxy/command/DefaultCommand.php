<?php

namespace acidproxy\command;

use acidproxy\command\sender\Sender;

/**
 * Class DefaultCommand
 * @package acidproxy\commande
 */
class DefaultCommand extends Command {

    /**
     * DefaultCommand constructor.
     * @param $name
     * @param null $usageMessage
     */
    public function __construct($name, $usageMessage = null) {
        parent::__construct($name, $usageMessage);
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        return true;
    }

}