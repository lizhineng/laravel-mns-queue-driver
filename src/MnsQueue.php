<?php

namespace Zhineng\QueueMns;

use AliyunMNS\Client;
use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Model\QueueAttributes;
use AliyunMNS\Requests\SendMessageRequest;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;

class MnsQueue extends Queue implements QueueContract
{
    public function __construct(
        protected Client $mns,
        protected string $default,
        ?bool $dispatchAfterCommit = false
    ) {
        $this->dispatchAfterCommit = $dispatchAfterCommit;
    }

    /**
     * @inheritDoc
     */
    public function size($queue = null)
    {
        /** @var QueueAttributes $response */
        $response = $this->mns
            ->getQueueRef($this->getQueue($queue))
            ->getAttribute()
            ->getQueueAttributes();

        return (int) $response->getActiveMessages()
            + (int) $response->getInactiveMessages()
            + (int) $response->getDelayMessages();
    }

    /**
     * @inheritDoc
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue ?: $this->default, $data),
            $queue,
            null,
            function ($payload, $queue) {
                return $this->pushRaw($payload, $queue);
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->mns
            ->getQueueRef($this->getQueue($queue))
            ->sendMessage(new SendMessageRequest($payload))
            ->getMessageId();
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

    /**
     * Get the queue of return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying MNS instance.
     *
     * @return \AliyunMNS\Client
     */
    public function getMns(): Client
    {
        return $this->mns;
    }
}
