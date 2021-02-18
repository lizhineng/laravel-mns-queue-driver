<?php

namespace Zhineng\QueueMns\Tests;

use AliyunMNS\Client;
use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Model\QueueAttributes;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zhineng\QueueMns\MnsJob;
use Zhineng\QueueMns\MnsQueue;
use Zhineng\QueueMns\Tests\Fixtures\MockedReceiveMessageResponseModel;
use Zhineng\QueueMns\Tests\Fixtures\MockedSendMessageResponseModel;

class MnsQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function setUp(): void
    {
        $this->mns = m::mock(Client::class);

        $this->queueName = 'emails';

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedDelay = 10;
        $this->mockedMessageId = '5F290C926D472878-2-14D9529****-200000001';

        $this->mockedQueueAttributesModel = new QueueAttributes(
            '30',
            '65536',
            '65536',
            '60',
            '0',
            $this->queueName,
            '1250700999',
            '1250700999',
            '20',
            '0',
            '0',
            true
        );
    }

    public function testPopProperlyPopsJobOffOfMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer(m::mock(Container::class));
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('receiveMessage')->once()->andReturn(new MockedReceiveMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf(MnsJob::class, $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer(m::mock(Container::class));
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('receiveMessage')->once()->andThrow(MessageNotExistException::class, 404);
        $result = $queue->pop($this->queueName);
        $this->assertNull($result);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoMns()
    {
        $now = Carbon::now();
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn(new MockedSendMessageResponseModel);
        $id = $queue->later($now->addSeconds(5), $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testDelayedPushProperlyPushesJobOntoMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn(new MockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testPushProperlyPushesJobOntoMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn(new MockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testSizeProperlyReadsMnsQueueSize()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $this->mns->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('getAttribute->getQueueAttributes')->once()->andReturn($this->mockedQueueAttributesModel);
        $size = $queue->size($this->queueName);
        $this->assertEquals(20, $size);
    }
}
