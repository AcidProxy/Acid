<?php

declare(strict_types=1);

namespace acidproxy\command\base;

use acidproxy\Client;
use acidproxy\command\Command;
use acidproxy\command\CommandMap;
use acidproxy\command\sender\Sender;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;

/**
 * Class GamemodeCommand
 * @package proxy\command\base
 */
class BoundingBoxCommand extends Command {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /**
     * GamemodeCommand constructor
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        parent::__construct("bb", "Edit entities bounding boxes");
        $this->commandMap = $commandMap;
    }

    /**
     * @param Sender $sender
     * @param array $args
     * @return bool
     */
    public function execute(Sender $sender, array $args): bool {
        if (!$sender instanceof Client) {
            $sender->sendMessage("§cThis command can be used only in game.");
            return true;
        }
        if(!isset($args[0]))  {
            $sender->sendMessage("§cUsage: §7.bb <size>");
            return true;
        }

        $scale = (int)$args[0];

        $batch = new BatchPacket();
        $count = 0;

        foreach ($this->commandMap->getProxy()->downstreamConnection->spawnedEntities as $id) {
            if(is_int($id)) {
                $pk = new SetEntityDataPacket();
                $pk->entityRuntimeId = $id;
                $pk->metadata = [
                    Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT ,0.63*$scale],
                    Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 1.8*$scale],
                    Entity::DATA_SCALE => [Entity::DATA_TYPE_INT, $scale]
                ];
                $pk->encode();
                $batch->addPacket($pk);
                $count++;
            }
        }

        if($count == 0) {
            $sender->sendMessage("§cThere aren't any players.");
            return false;
        }

        $batch->setCompressionLevel(7);
        $sender->dataPacket($batch);

        $sender->dataPacket($pk);

        $sender->sendMessage("§aBounding box updated!");
        return true;
    }
}