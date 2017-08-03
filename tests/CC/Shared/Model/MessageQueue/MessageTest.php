<?php

declare(strict_types=1);

namespace Tests\CC\Shared\Model\MessageQueue;

use CC\Shared\Model\MessageQueue\Message;
use Ds\Map;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /** @test */
    public function it_creates_new_instance_on_adding_metadata()
    {
        $message = new Message("Test");

        $this->assertCount(0, $message->metadata());

        $messageWithMetadata = $message->withMetadata(new Map(["test" => "test"]));

        $this->assertCount(0, $message->metadata());
        $this->assertCount(1, $messageWithMetadata->metadata());
    }
}
