<?php

declare(strict_types = 1);

namespace MusicPlayground\AmqpTransport\Messenger\Util;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;

final readonly class AmqpContentTypeMutator
{
    public function __construct(
        private array $message,
        private ?AmqpStamp $stamp
    ) {
    }

    public function mutate(): array
    {
        $amqpStamp = $this->stamp;
        $message = $this->message;

        if (isset($message['headers']['Content-Type'])) {
            $contentType = $message['headers']['Content-Type'];
            unset($message['headers']['Content-Type']);

            if (!$amqpStamp || !isset($amqpStamp->getAttributes()['content_type'])) {
                $amqpStamp = AmqpStamp::createWithAttributes(['content_type' => $contentType], $amqpStamp);
            }
        }

        return [$message, $amqpStamp];
    }
}