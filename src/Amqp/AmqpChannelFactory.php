<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Amqp;

use AMQPConnection;
use AMQPConnectionException;
use SensitiveParameter;

final class AmqpChannelFactory
{
    private ?AmqpChannel $channel = null;

    public function __construct(#[SensitiveParameter] private readonly array $credentials)
    {
    }

    /**
     * @throws AMQPConnectionException
     */
    public function pick(): AmqpChannel
    {
        if ($this->channel === null || $this->channel->getRestartFlag() === true) {
            $connection = new AMQPConnection($this->credentials);

            $connection->connect();

            $this->channel = new AmqpChannel($connection);
        }

        return $this->channel;
    }
}