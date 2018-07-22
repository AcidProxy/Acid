<?php

namespace proxy\plugin;


use pocketmine\network\mcpe\protocol\DataPacket;
use proxy\hosts\Client;
use proxy\hosts\ProxyClient;

interface PluginInterface
{

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

    public function isEnabled() : bool;

    public function setEnabled(bool $isEnabled, $callDisable = true) : void;

    public function handlePacketReceive(DataPacket $packet) : void;

    public function handlePacketSend(DataPacket $packet) : void;

    public function onCommand(ProxyClient $client, string $command, array $args) : void;



}