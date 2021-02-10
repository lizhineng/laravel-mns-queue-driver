<?php

namespace Zhineng\QueueMns;

use AliyunMNS\Client;
use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Requests\SendMessageRequest;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;

class MnsQueue extends Queue implements QueueContract, ClearableQueue
{
    public function __construct(
        protected $mns,
        protected string $default,
        bool $dispatchAfterCommit = false
    ) {
        $this->dispatchAfterCommit = $dispatchAfterCommit;
    }

    public function clear($queue)
    {
        // TODO: Implement clear() method.
    }

    public function size($queue = null)
    {
        // TODO: Implement size() method.
    }

    public function push($job, $data = '', $queue = null)
    {
        // TODO: Implement push() method.
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        // TODO: Implement pushRaw() method.
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue ?: $this->default, $data),
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                return $this->mns
                    ->getQueueRef($this->getQueue($queue))
                    ->sendMessage(new SendMessageRequest($payload, $this->secondsUntil($delay)))
                    ->getMessageId();
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function pop($queue = null)
    {
        try {
            $response = $this->mns
            ->getQueueRef($queue = $this->getQueue($queue))
            ->receiveMessage();

            return new MnsJob(
                $this->container, $this->mns, $response,
                $this->connectionName, $queue
            );
        } catch (MessageNotExistException $e) {
            return;
        }
    }

    protected function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->default;
    }
}
