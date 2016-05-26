<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\AccessControl;

use Silex\Application;

class Profile
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

    /**
     * Determine if the given user has a public profile
     * Decides based on the configured permissions in the extension config
     *
     * @param array $user
     * @return bool
     */
    public function hasProfile(array $user)
    {
        if (!$this->config['profiles']['enabled']) {
            return false;
        }

        $permissionConfig = $this->config['profiles']['permissions'];

        $hasProfile = false;

        // check if user has at least one of the defined roles
        foreach ($permissionConfig['roles'] as $role) {
            if (in_array($role, array_values($user['roles']), true)) {
                $hasProfile = true;
            }
        }

        // user has none of the required roles, so we don't have to check the other things.
        if (!$hasProfile) {
            return false;
        }

        // check if username is one of the defined excluded usernames.
        // we return false because if user is on this list, he isn't allowed to have a profile at all.
        if (in_array($user['username'], $permissionConfig['excluded_usernames'], true)) {
            return false;
        }

        if ($permissionConfig['conditional_field']) {
            $hasProfile = $user[$permissionConfig['conditional_field']] ? true : false;
        }

        return $hasProfile;
    }
}