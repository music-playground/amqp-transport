<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Middleware;

use MusicPlayground\AmqpTransport\Messenger\Stamp\AmqpExchangeStamp;
use LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class AmqpExchangeStampMiddleware implements MiddlewareInterface
{
    public function __construct(private array $queues)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        $queue = $this->queues[$message::class] ?? null;

        if ($queue === null) {
            throw new LogicException('No queue for this message');
        }

        return $stack->next()->handle(
            $envelope->with(new AmqpExchangeStamp($queue)),
            $stack
        );
    }
}