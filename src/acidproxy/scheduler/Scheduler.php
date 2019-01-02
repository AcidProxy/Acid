<?php

declare(strict_types=1);

namespace acidproxy\scheduler;

use acidproxy\ProxyServer;

/**
 * Class ProxyScheduler
 * @package acidproxy\scheduler
 */
class Scheduler {

    /** @var ProxyServer $server */
    private $server;

    /** @var Task[][] $tasks */
    private $tasks = [];

    /** @var int $lastTick */
    private $lastTick = 0;

    /**
     * ProxyScheduler constructor.
     * @param ProxyServer $server
     */
    public function __construct(ProxyServer $server) {
        $this->server = $server;
    }

    /**
     * @param Task $task
     * @param int $period
     * @return int
     */
    public function scheduleRepeatingTask(Task $task): int {
        $this->tasks[$id = count($this->tasks)] = $task;
        return $id;
    }

    public function tick() {
        if(microtime(true) - \acidproxy\START_TIME >= $this->lastTick / 20) {

            /**
             * @var int $id
             * @var Task $task
             */
            foreach($this->tasks as $id => $task){
                if($this->lastTick % $task->getPeriod() === 0){
                    $task->run();
                }
            }
            $this->lastTick++;
        }
    }
}