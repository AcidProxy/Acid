<?php

declare(strict_types=1);

namespace proxy\plugin;

use pocketmine\network\mcpe\protocol\DataPacket;
use proxy\command\sender\Sender;

/**
 * Interface PluginInterface
 * @package proxy\plugin
 */
interface PluginInterface {

    /**
     * @return void
     */
    public function onEnable() : void;

    /**
     * @return void
     *
     * Not works!
     * TODO: Fix
     */
    public function onDisable() : void;

    /**
     * @return bool $isEnabled
     */
    public function isEnabled() : bool;

    /**
     * @param bool $isEnabled
     * @param bool $callDisable
     *
     * @return void
     */
    public function setEnabled(bool $isEnabled, $callDisable = true) : void;

    /**
     * @param DataPacket $packet
     *
     * @return void
     */
    public function handlePacketReceive(DataPacket $packet) : void;

    /**
     * @param DataPacket $packet
     *
     * @return void
     */
    public function handlePacketSend(DataPacket $packet) : void;

    /**
     * @param Sender $client
     * @param string $command
     * @param array $args
     *
     * @return void
     */
    public function onCommand(Sender $client, string $command, array $args) : void;
}