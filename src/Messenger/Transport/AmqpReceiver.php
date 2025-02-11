<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Transport;

use AMQPConnectionException;
use AMQPEnvelope;
use AMQPException;
use AMQPQueue;
use AMQPQueueException;
use MusicPlayground\AmqpTransport\Amqp\AmqpChannelFactory;
use LogicException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final readonly class AmqpReceiver implements ReceiverInterface, QueueReceiverInterface
{
    private SerializerInterface $serializer;

    public function __construct(
        private AmqpChannelFactory $channelFactory,
        private ?string $queueName = null,
        ?SerializerInterface $serializer = null
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(): iterable
    {
        if ($this->queueName === null) {
            throw new TransportException('No default queue provided');
        }

        yield from $this->getFromQueue($this->queueName);
    }

    public function getFromQueues(array $queueNames): iterable
    {
        foreach ($queueNames as $queueName) {
            yield from $this->getFromQueue($queueName);
        }
    }

    public function ack(Envelope $envelope): void
    {
        /** @var AmqpReceivedStamp|null $stamp */
        $stamp = $envelope->last(AmqpReceivedStamp::class);

        if ($stamp === null) {
            throw new LogicException('Envelope does not has AmqpReceivedStamp');
        }

        try {
            /** @phpstan-ignore argument.type */
            $this->getQueue($stamp->getQueueName())->ack($stamp->getAmqpEnvelope()->getDeliveryTag());
        } catch (AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    public function reject(Envelope $envelope): void
    {
        /** @var AmqpReceivedStamp|null $stamp */
        $stamp = $envelope->last(AmqpReceivedStamp::class);

        if ($stamp === null) {
            throw new LogicException('Envelope does not has AmqpReceivedStamp');
        }

        try {
            $this->getQueue($stamp->getQueueName())->nack(
                /** @phpstan-ignore argument.type */
                $stamp->getAmqpEnvelope()->getDeliveryTag(),
                $this->getNackFlag($stamp->getAmqpEnvelope())
            );
        } catch (AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    private function getFromQueue(string $queueName): iterable
    {
        try {
            $queue = $this->getQueue($queueName);
            $message = $queue->get();

            if ($message instanceof AMQPEnvelope === false) {
                return;
            }

            /** @var string|null $body */
            $body = $message->getBody();

            try {
                $envelope = $this->serializer->decode([
                    'body' => $body === false ? '' : $body,
                    'headers' => $message->getHeaders(),
                ]);
            } catch (MessageDecodingFailedException $exception) {
                /** @phpstan-ignore argument.type */
                $queue->nack($message->getDeliveryTag(), $this->getNackFlag($message));

                throw $exception;
            }

            yield $envelope->with(new AmqpReceivedStamp($message, $queueName));
        } catch (AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @throws AMQPConnectionException
     * @throws AMQPQueueException
     */
    private function getQueue(string $queueName): AMQPQueue
    {
        $queue = new AMQPQueue($this->channelFactory->pick());

        $queue->setName($queueName);

        return $queue;
    }

    private function getNackFlag(AMQPEnvelope $envelope): int
    {
        return ($envelope->getHeaders()['requeue'] ?? false) === true ? AMQP_REQUEUE : AMQP_NOPARAM;
    }
}