<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventSourcingServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    EventSourcingServiceProvider::class,
    FortifyServiceProvider::class,
];
