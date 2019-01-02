<?php

declare(strict_types=1);

namespace acidproxy\scheduler;

use acidproxy\ProxyServer;

/**
 * Class Task
 * @package acidproxy\scheduler
 */
abstract class Task {

    /** @var int $period */
    private $period;

    /**
     * Task constructor.
     * @param int $period
     */
    public function __construct(int $period) {
        $this->period = $period;
    }

    /**
     * @return ProxyServer
     */
    public function getServer(): ProxyServer{
        return ProxyServer::getInstance();
    }

    /**
     * @return int
     */
    public function getPeriod(): int{
        return $this->period;
    }

    /**
     * @return bool
     */
    public function isRepeating(): bool{
        return $this->period > 0;
    }

    public function run(): void{}

}