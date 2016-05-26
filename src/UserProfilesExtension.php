<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

use Bolt\Controller\Zone;
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

    /**
     * @inheritdoc
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        parent::boot($app);
    }

    /**
     * @inheritdoc
     *
     * @param Request $request
     * @param Application $app
     */
    public function before(Request $request, Application $app)
    {
        //dump($app['users']->getCurrentUser());
        if (Zone::isBackend($request)) {
            $this->checkIfUserSessionHasToBeUpdated();
        }
    }

    /**
     * @inheritdoc
     *
     * @param Application $app
     */
    protected function registerServices(Application $app)
    {
        $this->registerUsersTableSchema($app);

        $app->before([$this, 'before']);
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function registerAssets()
    {
        $userEditFormWidget = new \Bolt\Asset\Widget\Widget();
        $userEditFormWidget
            ->setZone('backend')
            ->setLocation('edituser_bottom')
            ->setCallback([$this, 'userEditFormWidgetCallback'])
            ->setCallbackArguments([])
            ->setDefer(false);

        return [$userEditFormWidget];
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function registerBackendControllers()
    {
        return [
            '/' => new Backend($this->getConfig())
        ];
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function registerFrontendControllers()
    {
        return [
            '/' => new Frontend($this->getConfig())
        ];
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function registerTwigFunctions()
    {
        $this->twigFunctions = new Functions($this->getContainer(), $this->getConfig());

        return [
            'avatar' => 'avatarTwig',
            'profile_link' => 'profileLinkTwig',
            'has_profile' => 'hasProfileTwig',
        ];
    }

    /**
     * Router for the twig function
     * to pass the data to the real implementation
     *
     * @param array $user
     * @param int $gravatar_size
     * @param null $fallback
     * @return mixed
     */
    public function avatarTwig(array $user, $gravatar_size = 100, $fallback = null)
    {
        return $this->twigFunctions->avatar($user, $gravatar_size, $fallback);
    }

    /**
     * Router for the twig function
     * to pass the data to the real implementation
     *
     * @param array $user
     * @return mixed
     */
    public function profileLinkTwig(array $user)
    {
        return $this->twigFunctions->profileLink($user);
    }

    /**
     * Router for the twig function
     * to pass the data to the real implementation
     *
     * @param array $user
     * @return mixed
     */
    public function hasProfileTwig(array $user)
    {
        return $this->twigFunctions->hasProfile($user);
    }

    /**
     * Callback for the Widget on the user edit form
     *
     * @return string
     */
    public function userEditFormWidgetCallback()
    {
        return $this->renderTemplate('profile_extended.twig', [
            'user' => $this->getContainer()['users']->getCurrentUser(),
            'fields' => $this->getConfig()['fields'],
        ]);
    }

    /**
     * Register own table schema class for the users table
     * to add all custom fields
     *
     * @param Application $app
     */
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

    /**
     * After the users table was altered, the user object
     * in the session still has the old schema and needs
     * to be updated eventually.
     */
    private function checkIfUserSessionHasToBeUpdated()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        $fields = $config['fields'];
        $user = $app['users']->getCurrentUser();

        if (!$user) {
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

    /**
     * Such name, much pretty
     *
     * @return string
     */
    public function getDisplayName()
    {
        return "User Profiles";
    }
}

