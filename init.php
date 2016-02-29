<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

if (isset($app)) {
    $app['extensions']->register(new Extension($app));
}

