<?php

namespace proxy\utils;


use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\Binary;
use proxy\hosts\BaseHost;
use proxy\ProxyServer;
use raklib\protocol\ACK;
use raklib\protocol\Datagram;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\NACK;
use raklib\server\Session;

class PacketSession {

    const MAX_SPLIT_SIZE = 128;
    const MAX_SPLIT_COUNT = 4;

    /**
     * @var int
     */
    public $sendSeqNumber = 0;

    /**
     * @var \SplObjectStorage
     */
    private $recoveryQueue;


    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /** @var Datagram[][] */
    private $splitPackets = [];

    /**
     * @var ACK[] $ACKs
     */
    private $ACKs = [];

    /**
     * @var NACK[] $NACKs
     */
    private $NACKs = [];



    /**
     * Pool constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer) {
        $this->proxyServer = $proxyServer;
        $this->sendSeqNumber = 0;
        $this->recoveryQueue = new \SplObjectStorage;
    }

    /**
     * @param string $buffer
     * @return null|DataPacket
     */
    public function readDataPacket(string $buffer) : ?DataPacket{
        $pid = ord($buffer{0});
        if(($pid & Datagram::BITFLAG_VALID) !== 0){
            if($pid & Datagram::BITFLAG_ACK){
                $packet = new ACK($buffer);
                $this->ACKs[] = $packet;
            }elseif($pid & Datagram::BITFLAG_NAK){
                $packet = new NACK($buffer);
                $this->NACKs[] = $packet;
            }else{
                if(($datagram = new Datagram($buffer)) instanceof Datagram){
                    $datagram->decode();
                    $this->sendSeqNumber = $datagram->seqNumber;
                    foreach($datagram->packets as $packet){
                        if($packet->hasSplit){
                            $split = $this->decodeSplit($packet);
                            if($split !== null){
                                $packet = $split;
                            }
                        }
                        if(($pk = self::decodeBatch($packet)) !== null){
                            $this->recoveryQueue[$pk] = $datagram->seqNumber;
                            return $pk;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param DataPacket $packet
     * @return ACK
     */
    public function forwardPacket(DataPacket $packet) : ACK{
        $ack = new ACK();
        $ack->packets[] = $this->recoveryQueue[$packet];
        $ack->encode();
        return $ack;
    }

    public function decodeSplit(EncapsulatedPacket $packet) : ?EncapsulatedPacket{
        if($packet->splitCount >= static::MAX_SPLIT_SIZE or $packet->splitIndex >= static::MAX_SPLIT_SIZE or $packet->splitIndex < 0){
            return null;
        }
        if(!isset($this->splitPackets[$packet->splitID])){
            if(count($this->splitPackets) >= static::MAX_SPLIT_COUNT){
                return null;
            }
            $this->splitPackets[$packet->splitID] = [$packet->splitIndex => $packet];
        }else{
            $this->splitPackets[$packet->splitID][$packet->splitIndex] = $packet;
        }
        if(count($this->splitPackets[$packet->splitID]) === $packet->splitCount){
            $pk = new EncapsulatedPacket;
            $pk->buffer = "";
            $pk->reliability = $packet->reliability;
            $pk->messageIndex = $packet->messageIndex;
            $pk->sequenceIndex = $packet->sequenceIndex;
            $pk->orderIndex = $packet->orderIndex;
            $pk->orderChannel = $packet->orderChannel;
            var_dump($pk->orderIndex);
            for($i = 0; $i < $packet->splitCount; ++$i){
                $pk->buffer .= $this->splitPackets[$packet->splitID][$i]->buffer;
            }

            $pk->length = strlen($pk->buffer);
            unset($this->splitPackets[$packet->splitID]);
            return $pk;
        }
        return null;
    }


    /**
     * @param DataPacket $packet
     * @param BaseHost $baseHost
     */
    public  function writeDataPacket(DataPacket $packet, BaseHost $baseHost) : void{
        $batch = new BatchPacket();
        $batch->addPacket($packet);
        $batch->setCompressionLevel(7);
        $batch->encode();
        $encapsulated = new EncapsulatedPacket;
        $encapsulated->reliability = 0;
        $encapsulated->buffer = $batch->buffer;
        $offset = 3;
        $encapsulated->orderIndex = Binary::readLTriad(substr($encapsulated->buffer, $offset, 3));
        $offset += 3;
        $encapsulated->orderChannel = ord($encapsulated->buffer{$offset++});
        $dataPacket = new Datagram;
        $dataPacket->seqNumber = $this->sendSeqNumber++;
        $dataPacket->sendTime = microtime(true);
        $dataPacket->packets = [$encapsulated];
        $dataPacket->encode();
        $this->proxyServer->writePacket($dataPacket->buffer, $baseHost->getAddress()->ip, $baseHost->getAddress()->port);
    }



    /**
     * @param EncapsulatedPacket $encapsulatedPacket
     * @return null|DataPacket
     */
    public function decodeBatch(EncapsulatedPacket $encapsulatedPacket){
        if(($batch = PacketPool::getPacket($encapsulatedPacket->buffer)) instanceof BatchPacket){
            @$batch->decode();
            if($batch->payload !== "" && is_string($batch->payload)){
                foreach($batch->getPackets() as $buf){
                    return PacketPool::getPacket($buf);
                }
            }
        }
        return null;
    }

}
