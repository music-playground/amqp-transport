<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Amqp;

use AMQPChannelException;
use AMQPConnectionException;
use AMQPExchange;
use AMQPExchangeException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;

final readonly class Publisher
{
    /**
     * @throws AMQPExchangeException
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function publish(
        AmqpExchange $exchange,
        string $body,
        array $headers,
        ?AmqpStamp $amqpStamp = null
    ): void {
        $attributes = $amqpStamp?->getAttributes() ?: [];

        try {
            $exchange->publish(
                $body,
                $amqpStamp?->getRoutingKey(),
                $amqpStamp?->getFlags(),
                [...$attributes, 'headers' => [...($attributes['headers'] ?? []), ...$headers]]
            );
        } catch (AMQPConnectionException|AMQPChannelException $exception) {
            $channel = $exchange->getChannel();

            if ($channel instanceof AmqpChannel === true) {
                $channel->setRestartFlag(true);
            }

            throw $exception;
        }
    }
}