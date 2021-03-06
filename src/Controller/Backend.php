<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Controller;

use Bolt\Controller\Zone;
use Bolt\Storage\Entity\Users;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Backend implements ControllerProviderInterface
{
    private $config;

    /**
     * Backend controller constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns routes to connect to the given application
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $ctr ControllerCollection */
        $ctr = $app['controllers_factory'];
        $ctr->value(Zone::KEY, Zone::BACKEND);

        $ctr->match('profile/extended-save', [$this, 'profileSave'])
            ->bind('profileExtendedSave')
            ->method(Request::METHOD_POST);

        return $ctr;
    }

    /**
     * Saves the user extended user information
     *
     * @param Request $request
     * @param Application $app
     * @return RedirectResponse
     */
    public function profileSave(Request $request, Application $app)
    {
        $user = $app['users']->getCurrentUser();

        $data = $this->cleanupPostData($request->request->all(), $user);

        foreach ($data as $key => $value) {
            $user[$key] = $value;
        }

        $user = new Users($user);

        $app['users']->saveUser($user);

        $this->updateUserInSession($app, $user);

        $app['logger.flash']->success("Extended profile information has been saved.");

        return new RedirectResponse($app["request"]->getBaseUrl() . $app['routes']->get('profile')->getPath());
    }

    /**
     * After the user was updated in the DB,
     * the object in the session has to be updated too
     *
     * @param Application $app
     * @param Users $user
     */
    private function updateUserInSession(Application $app, Users $user)
    {
        $token = $app['session']->get('authentication');
        $token->setUser($user);
        $app['session']->set('authentication', $token);
    }

    /**
     * Clean up POST data from extended user profile form
     * - Remove all Bolt reserved keys
     * - Remove all keys which are not in the user object to avoid mysql errors
     *
     * @param array $data
     * @param array $user
     * @return array
     */
    private function cleanupPostData(array $data, array $user)
    {
        $notallowedcolumns = array(
            'id',
            'username',
            'password',
            'email',
            'lastseen',
            'lastip',
            'displayname',
            'enabled',
            'stack',
            'roles',
        );

        // unset all bolt reserved columns.
        foreach (array_keys($data) as $key) {
            if (in_array($key, $notallowedcolumns)) {
                unset($data[$key]);
            }
        }

        $allowedcolumns = array_keys($user);

        // unset all not defined columns.
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedcolumns)) {
                unset($data[$key]);
            }
        }

        // handle weird weirdness with weird input fields like checkboxes
        foreach ($this->config['fields'] as $key => $field) {
            if ($field['type'] === "checkbox") {
                $data[$key] = $data[$key] ? 1 : 0;
            }
        }

        return $data;
    }
}
