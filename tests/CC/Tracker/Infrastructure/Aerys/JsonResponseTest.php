<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\Aerys;

use Aerys\Response;
use Amp\Promise;
use CC\Tracker\Infrastructure\Aerys\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class JsonResponseTest extends TestCase
{
    /** @var Promise|ObjectProphecy */
    private $promise;

    /** @var Response|ObjectProphecy $response */
    private $response;

    /** @test */
    public function it_adds_content_type_header()
    {
        JsonResponse::send($this->response->reveal(), []);

        $this->response->setHeader("Content-Type", "application/json")->shouldBeCalled();
    }

    /** @test */
    public function it_serializes_data_to_json_and_send_it()
    {
        JsonResponse::send($this->response->reveal(), ["test" => "test"]);

        $this->response->end("{\"test\":\"test\"}")->shouldBeCalled();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->promise = $this->prophesize(Promise::class);

        $this->response = $this->prophesize(Response::class);
        $this->response->setHeader(Argument::any(), Argument::any())->willReturn($this->response);
        $this->response->end(Argument::any())->willReturn($this->promise->reveal());
    }
}
