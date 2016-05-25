<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

use Bolt\Extension\Ohlandt\UserProfiles\Storage\Schema\Table\UsersTable;
use Bolt\Extension\SimpleExtension;
use Silex\Application;

/**
 * User Profiles extension class.
 *
 * @author Phillipp Ohlandt <phillipp.ohlandt@googlemail.com>
 */
class UserProfilesExtension extends SimpleExtension
{
    protected function registerServices(Application $app)
    {
        $this->registerUsersTableSchema($app);
    }

    private function registerUsersTableSchema(Application $app)
    {
        $app['schema.base_tables'] = $app->extend(
            'schema.base_tables',
            function ($baseTables) use ($app) {
                $platform = $app['db']->getDatabasePlatform();
                $prefix = $app['schema.prefix'];

                $baseTables['users'] = $app->share(function () use ($platform, $prefix) {
                    return new UsersTable($platform, $prefix);
                });

                return $baseTables;
            }
        );
    }

    public function getDisplayName()
    {
        return "User Profiles";
    }
}
