<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Twig;

use Bolt\Extension\Ohlandt\UserProfiles\AccessControl\Profile;
use Bolt\Extension\Ohlandt\UserProfiles\Avatar\UrlResolver;
use Silex\Application;
use Bolt\Twig\ArrayAccessSecurityProxy;
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

    /**
     * Functions constructor
     *
     * @param Application $app
     * @param array $config
     */
    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Twig helper function to get an avatar URL for a given user, based on the extension config
     *
     * @param ArrayAccessSecurityProxy $user
     * @param int $gravatar_size
     * @param null $fallback
     * @return bool|string
     */
    public function avatar(ArrayAccessSecurityProxy $user, $gravatar_size = 100, $fallback = null)
    {
        if ($fallback) {
            $this->config['avatars']['fallback_url'] = $fallback;
        }

        $resolver = new UrlResolver($this->app, $this->config);

        return $resolver->resolve($user, $gravatar_size);
    }

    /**
     * Twig helper function to get the profile URL for a given user
     *
     * @param ArrayAccessSecurityProxy $user
     * @return string
     */
    public function profileLink(ArrayAccessSecurityProxy $user)
    {
        if ($this->hasProfile($user)) {
            return '/' . $this->config['profiles']['prefix'] . '/' . $user['username'];
        }

        return '';
    }

    /**
     * Twig helper to determine if the given user has a public profile
     *
     * @param array $user
     * @return bool
     */
    public function hasProfile(array $user)
    {
        $accessControl = new Profile($this->app, $this->config);

        return $accessControl->hasProfile($user);
    }
}
