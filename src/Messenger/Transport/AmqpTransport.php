<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final readonly class AmqpTransport implements TransportInterface, QueueReceiverInterface
{
    public function __construct(
        private AmqpReceiver $receiver,
        private AmqpSender $sender
    ) {
    }

    public function get(): iterable
    {
        return $this->receiver->get();
    }

    public function ack(Envelope $envelope): void
    {
        $this->receiver->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->receiver->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->sender->send($envelope);
    }

    public function getFromQueues(array $queueNames): iterable
    {
        return $this->receiver->getFromQueues($queueNames);
    }
}