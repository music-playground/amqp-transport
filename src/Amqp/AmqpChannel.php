<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Amqp;

use AMQPConnection;

final class AmqpChannel extends \AMQPChannel
{
    private bool $restartFlag = false;

    public function __construct(AMQPConnection $connection)
    {
        parent::__construct($connection);
    }

    public function getRestartFlag(): bool
    {
        return $this->restartFlag;
    }

    public function setRestartFlag(bool $restartFlag): void
    {
        $this->restartFlag = $restartFlag;
    }
}