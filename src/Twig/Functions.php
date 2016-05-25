<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Twig;

use Silex\Application;

class Functions
{
    /**
     * @var Application
     */
    private $app;
    /**
     * @var array
     */
    private $config;

    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function avatar(array $user, $gravatar_size = 100, $fallback = null)
    {

    }

    public function profileLink(array $user)
    {

    }

    public function hasProfile(array $user)
    {

    }
}