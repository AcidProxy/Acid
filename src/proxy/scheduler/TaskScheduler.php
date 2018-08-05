<?php

namespace proxy\scheduler;


class TaskScheduler
{

    /**
     * @var Task[] $tasks
     */
    private $tasks = [];

    /**
     * @var bool $enabled
     */
    private $enabled;

    /**
     * @var int $ids
     */
    private $ids = 0;

    /**
     * TaskScheduler constructor.
     * @param bool $enabled
     */
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param Task $task
     */
    public function scheduleTask(Task $task){
        $this->tasks[$this->nextId()] = $task;
    }

    /**
     * @return array
     */
    public function getTaskList() : array{
        return $this->tasks;
    }

    /**
     * @return int
     */
    private function nextId() : int{
        return $this->ids++;
    }

}