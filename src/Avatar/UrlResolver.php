<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Avatar;

class UrlResolver
{
    protected $app;

    protected $config;

    /**
     * AvatarUrlResolver constructor.
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
     * Resolve the avatar URL for a given user
     *
     * @param array $user
     * @param int $gravatar_size
     * @return bool|string
     */
    public function resolve(array $user, $gravatar_size = 100)
    {
        if ($field = $this->config['avatars']['field']) {
            if ($user[$field] != '') {
                return $user[$field];
            }
        }

        return $this->getGravatarOrFallbackUrl($user, $gravatar_size);
    }

    /**
     * Get the Gravatar or fallback URL for a given user
     *
     * @param array $user
     * @param $gravatar_size
     * @return bool|string
     */
    protected function getGravatarOrFallbackUrl(array $user, $gravatar_size)
    {
        $config = $this->config;

        if ($url = $this->getGravatarUrl($user, $gravatar_size)) {
            return $url;
        }

        if ($url = $config['avatars']['fallback_url']) {
            return $url;
        }

        return '';
    }

    /**
     * Get the Gravatar URL for a given user
     *
     * @param array $user
     * @param $gravatar_size
     * @return bool|string
     */
    protected function getGravatarUrl(array $user, $gravatar_size)
    {
        $config = $this->config;

        if ($config['avatars']['gravatar_fallback']) {
            $parameter = array();

            $parameter['s'] = $gravatar_size;

            if ($config['avatars']['fallback_url']) {
                $parameter['d'] = ($config['avatars']['fallback_url']);
            }

            return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . '?' . http_build_query($parameter);
        }

        return false;
    }
}
