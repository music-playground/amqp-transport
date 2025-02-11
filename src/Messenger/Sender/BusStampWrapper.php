<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Sender;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;

final readonly class BusStampWrapper implements QueueReceiverInterface
{
    public function __construct(
        private QueueReceiverInterface $receiver,
        private array $buses
    ) {
    }

    public function getFromQueues(array $queueNames): iterable
    {
        foreach ($this->receiver->getFromQueues($queueNames) as $message) {
            yield $this->attachBusStamp($message);
        }
    }

    public function get(): iterable
    {
        foreach ($this->receiver->get() as $message) {
            yield $this->attachBusStamp($message);
        }
    }

    public function ack(Envelope $envelope): void
    {
        $this->receiver->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->receiver->reject($envelope);
    }

    private function attachBusStamp(Envelope $message): Envelope
    {
        $class = $message->getMessage()::class;
        $bus = $this->buses[$class];

        return $bus !== null ? $message->with(new BusNameStamp($bus)) : $message;
    }
}