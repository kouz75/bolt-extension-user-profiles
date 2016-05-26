<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

use Bolt\Controller\Zone;
use Bolt\Events\SchemaEvent;
use Bolt\Events\SchemaEvents;
use Bolt\Extension\Ohlandt\UserProfiles\Controller\Backend;
use Bolt\Extension\Ohlandt\UserProfiles\Controller\Frontend;
use Bolt\Extension\Ohlandt\UserProfiles\Storage\Schema\Table\UsersTable;
use Bolt\Extension\Ohlandt\UserProfiles\Twig\Functions;
use Bolt\Extension\SimpleExtension;
use Bolt\Storage\Entity\Users;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User Profiles extension class.
 *
 * @author Phillipp Ohlandt <phillipp.ohlandt@googlemail.com>
 */
class UserProfilesExtension extends SimpleExtension
{
    private $twigFunctions;

    public function boot(Application $app)
    {
        parent::boot($app);
    }

    public function before(Request $request, Application $app)
    {
        //dump($app['users']->getCurrentUser());
        if(Zone::isBackend($request)){
            $this->checkIfUserSessionHasToBeUpdated();
        }
    }

    protected function registerServices(Application $app)
    {
        $this->registerUsersTableSchema($app);

        $app->before([$this, 'before']);
    }

    protected function registerAssets()
    {
        $userEditFormWidget = new \Bolt\Asset\Widget\Widget();
        $userEditFormWidget
            ->setZone('backend')
            ->setLocation('edituser_bottom')
            ->setCallback([$this, 'userEditFormWidgetCallback'])
            ->setCallbackArguments([])
            ->setDefer(false)
        ;

        return [ $userEditFormWidget ];
    }

    protected function registerBackendControllers()
    {
        return [
          '/' => new Backend($this->getConfig())
        ];
    }

    protected function registerFrontendControllers()
    {
        return [
            '/' => new Frontend($this->getConfig())
        ];
    }

    protected function registerTwigFunctions()
    {
        $this->twigFunctions = new Functions($this->getContainer(), $this->getConfig());

        return [
            'avatar' => 'avatarTwig',
            'profile_link' => 'profileLinkTwig',
            'has_profile' =>'hasProfileTwig',
        ];
    }

    public function avatarTwig(array $user, $gravatar_size = 100, $fallback = null)
    {
        return $this->twigFunctions->avatar($user, $gravatar_size, $fallback);
    }

    public function profileLinkTwig(array $user)
    {
        return $this->twigFunctions->profileLink($user);
    }

    public function hasProfileTwig(array $user)
    {
        return $this->twigFunctions->hasProfile($user);
    }

    public function userEditFormWidgetCallback()
    {
        return $this->renderTemplate('profile_extended.twig', [
            'user' => $this->getContainer()['users']->getCurrentUser(),
            'fields' => $this->getConfig()['fields'],
        ]);
    }

    private function registerUsersTableSchema(Application $app)
    {
        $config = $this->getConfig();

        $app['schema.base_tables'] = $app->extend(
            'schema.base_tables',
            function ($baseTables) use ($app, $config) {
                $platform = $app['db']->getDatabasePlatform();
                $prefix = $app['schema.prefix'];

                $baseTables['users'] = $app->share(function () use ($platform, $prefix, $config) {
                    return new UsersTable($platform, $prefix, $config);
                });

                return $baseTables;
            }
        );
    }

    private function checkIfUserSessionHasToBeUpdated()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        $fields = $config['fields'];
        $user = $app['users']->getCurrentUser();

        if(!$user){
            return;
        }

        $incomplete = false;
        foreach (array_keys($fields) as $key) {
            if (!isset($user[$key])) {
                $incomplete = true;
                break;
            }
        }

        if ($incomplete) {
            $token = $app['session']->get('authentication');
            $newUser = $app['users']->getUser($token->getUser()->id);
            $newUser = new Users($newUser);

            $token->setUser($newUser);
            $app['session']->set('authentication', $token);
        }
    }

    public function getDisplayName()
    {
        return "User Profiles";
    }
}
