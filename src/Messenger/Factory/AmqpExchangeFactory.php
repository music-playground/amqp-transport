<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Factory;

use AMQPConnectionException;
use AMQPExchange;
use AMQPExchangeException;
use MusicPlayground\AmqpTransport\Amqp\AmqpChannelFactory;
use MusicPlayground\AmqpTransport\Messenger\Stamp\AmqpExchangeStamp;
use Symfony\Component\Messenger\Envelope;

final readonly class AmqpExchangeFactory
{
    public function __construct(private AmqpChannelFactory $channelFactory)
    {
    }

    /**
     * @throws AMQPExchangeException
     * @throws AMQPConnectionException
     */
    public function create(Envelope $envelope): AmqpExchange
    {
        $amqpExchange = new AMQPExchange($this->channelFactory->pick());
        $exchangeStamp = $envelope->last(AmqpExchangeStamp::class);

        if ($exchangeStamp !== null) {
            $amqpExchange->setName($exchangeStamp->getName());
        }

        return $amqpExchange;
    }
}