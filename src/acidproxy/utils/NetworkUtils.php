<?php

declare(strict_types=1);

namespace acidproxy\utils;

use mysql_xdevapi\Exception;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\Binary;
use acidproxy\network\AbstractConnection;
use acidproxy\ProxyServer;
use raklib\protocol\ACK;
use raklib\protocol\Datagram;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\NACK;

/**
 * Class NetworkUtils
 * @package acidproxy\utils
 */
class NetworkUtils {

    const MAX_SPLIT_SIZE = 128;
    const MAX_SPLIT_COUNT = 4;

    /** @var int $sendSeqNumber */
    public $sendSeqNumber = 0;

    /** @var \SplObjectStorage */
    private $recoveryQueue;

    /** @var ProxyServer $proxyServer */
    private $proxyServer;

    /** @var Datagram[][] */
    private $splitPackets = [];

    /** @var ACK[] $ACKs */
    private $ACKs = [];

    /** @var NACK[] $NACKs */
    private $NACKs = [];

    /** @var array $toSend */
    private $toSend = [];

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
     *
     * @return null|DataPacket
     */
    public function readDataPacket(string $buffer): ?DataPacket {
        $pid = ord($buffer{0});
        if (($pid & Datagram::BITFLAG_VALID) !== 0) {
            if ($pid & Datagram::BITFLAG_ACK) {
                $packet = new ACK($buffer);
                $this->ACKs[] = $packet;
            } elseif ($pid & Datagram::BITFLAG_NAK) {
                $packet = new NACK($buffer);
                $this->NACKs[] = $packet;
            } else {
                if (($datagram = new Datagram($buffer)) instanceof Datagram) {
                    $datagram->decode();
                    foreach ($datagram->packets as $packet) {
                        if ($packet->hasSplit) {
                            $split = $this->decodeSplit($packet);
                            if ($split !== null) {
                                $packet = $split;
                            }
                        }
                        if (($pk = self::decodeBatch($packet)) !== null) {
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
     *
     * @return ACK
     */
    public function forwardPacket(DataPacket $packet): ACK {
        $ack = new ACK();
        $ack->packets[] = $this->recoveryQueue[$packet];
        $ack->encode();
        return $ack;
    }

    public function decodeSplit(EncapsulatedPacket $packet): ?EncapsulatedPacket {
        if ($packet->splitCount >= static::MAX_SPLIT_SIZE or $packet->splitIndex >= static::MAX_SPLIT_SIZE or $packet->splitIndex < 0) {
            return null;
        }
        if (!isset($this->splitPackets[$packet->splitID])) {
            if (count($this->splitPackets) >= static::MAX_SPLIT_COUNT) {
                return null;
            }
            $this->splitPackets[$packet->splitID] = [$packet->splitIndex => $packet];
        } else {
            $this->splitPackets[$packet->splitID][$packet->splitIndex] = $packet;
        }
        if (count($this->splitPackets[$packet->splitID]) === $packet->splitCount) {
            $pk = new EncapsulatedPacket;
            $pk->buffer = "";
            $pk->reliability = $packet->reliability;
            $pk->messageIndex = $packet->messageIndex;
            $pk->sequenceIndex = $packet->sequenceIndex;
            $pk->orderIndex = $packet->orderIndex;
            $pk->orderChannel = $packet->orderChannel;
            for ($i = 0; $i < $packet->splitCount; ++$i) {
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
     * @param AbstractConnection $connection
     */
    public function writeDataPacket(DataPacket $packet, AbstractConnection $connection): void {
        try {
            $batch = null;
            if($packet instanceof BatchPacket) {
                $batch = $packet;
                $batch->setCompressionLevel(7);
                $batch->encode();
            }
            else {
                $batch = new BatchPacket();
                $batch->addPacket($packet);
                foreach ($this->toSend as $index => $pk) {
                    $batch->addPacket($pk);
                    unset($this->toSend[$index]);
                }
                $batch->setCompressionLevel(7);
                $batch->encode();
            }


            $encapsulated = new EncapsulatedPacket;
            $encapsulated->reliability = 0;
            $encapsulated->buffer = $batch->buffer;

            $offset = 3;
            $lTriad = substr($encapsulated->buffer, $offset, 3);
            if(is_string($lTriad)) {
                $encapsulated->orderIndex = Binary::readLTriad($lTriad);
                $offset += 3;
                $encapsulated->orderChannel = ord($encapsulated->buffer{$offset++});
                $encapsulated->messageIndex = Binary::readLTriad(substr($encapsulated->buffer, $offset, 3));
                $offset += 3;
                $encapsulated->sequenceIndex = Binary::readLTriad(substr($encapsulated->buffer, $offset, 3));
                $dataPacket = new Datagram;
                $dataPacket->seqNumber = $this->sendSeqNumber++;
                $dataPacket->sendTime = microtime(true);
                $dataPacket->packets = [$encapsulated];
                $dataPacket->encode();
                $this->writePacket($dataPacket->buffer, $connection);
            }
            else {
                $this->toSend[] = $batch;
            }

        }
        catch (\Exception $exception) {
            $this->proxyServer->getLogger()->error($exception->getMessage());
        }

    }

    /**
     * @param string $buffer
     * @param AbstractConnection $connection
     */
    public function writePacket(string $buffer, AbstractConnection $connection): void {
        $address = $connection->address;
        $this->proxyServer->getSocket()->send($buffer, $address->ip, $address->port);
    }


    /**
     * @param EncapsulatedPacket $encapsulatedPacket
     *
     * @return null|DataPacket
     */
    public function decodeBatch(EncapsulatedPacket $encapsulatedPacket) {
        if (($batch = PacketPool::getPacket($encapsulatedPacket->buffer)) instanceof BatchPacket) {
            /** @var BatchPacket $batch */
            @$batch->decode();
            if ($batch->payload !== "" && is_string($batch->payload)) {
                foreach ($batch->getPackets() as $buf) {
                    return PacketPool::getPacket($buf);
                }
            }
        }
        return null;
    }

}