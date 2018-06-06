<?php

namespace Reshadman\Committed;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CommittedServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        TransactionEventsSubscriber::class
    ];
}