<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Transport;

use AMQPException;
use MusicPlayground\AmqpTransport\Amqp\Publisher;
use MusicPlayground\AmqpTransport\Messenger\Factory\AmqpExchangeFactory;
use MusicPlayground\AmqpTransport\Messenger\Util\AmqpContentTypeMutator;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final readonly class AmqpSender implements SenderInterface
{
    private SerializerInterface $serializer;

    public function __construct(
        private Publisher $publisher,
        private AmqpExchangeFactory $exchangeFactory,
        ?SerializerInterface $serializer = null
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * @throws TransportException
     */
    public function send(Envelope $envelope): Envelope
    {
        $message = $this->serializer->encode($envelope);
        $stamp = $envelope->last(AmqpStamp::class);

        try {
            [$message, $amqpStamp] = (new AmqpContentTypeMutator($message, $stamp))->mutate();
            $exchangeStamp = $this->exchangeFactory->create($envelope);

            $this->publisher->publish($exchangeStamp, $message['body'], $message['headers'] ?? [], $amqpStamp);
        } catch (AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $envelope;
    }
}