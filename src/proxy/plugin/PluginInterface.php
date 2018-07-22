<?php

declare(strict_types=1);

namespace proxy\plugin;

use pocketmine\network\mcpe\protocol\DataPacket;
use proxy\command\sender\Sender;

interface PluginInterface {

    /**
     * @return mixed
     */
    public function onEnable() : void;

    /**
     * @return mixed
     *
     * Not executed on proxy stop!
     */
    public function onDisable() : void;

    /**
     * @return bool
     */
    public function isEnabled() : bool;

    /**
     * @param bool $isEnabled
     * @param bool $callDisable
     */
    public function setEnabled(bool $isEnabled, $callDisable = true) : void;

    /**
     * @param DataPacket $packet
     */
    public function handlePacketReceive(DataPacket $packet) : void;

    /**
     * @param DataPacket $packet
     */
    public function handlePacketSend(DataPacket $packet) : void;

    /**
     * @param Sender $sender
     * @param string $command
     * @param array $args
     */
    public function onCommand(Sender $sender, string $command, array $args) : void;
}