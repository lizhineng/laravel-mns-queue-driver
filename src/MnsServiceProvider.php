<?php

namespace Zhineng\QueueMns;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class MnsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->booting(function () {
            Queue::addConnector('mns', function () {
                return new MnsConnector;
            });
        });
    }
}
