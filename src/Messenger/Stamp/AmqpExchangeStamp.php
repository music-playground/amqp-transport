<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

final readonly class AmqpExchangeStamp implements StampInterface
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}