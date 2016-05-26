<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Controller;

use Bolt\Controller\Zone;
use Bolt\Extension\Ohlandt\UserProfiles\AccessControl\Profile;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Frontend implements ControllerProviderInterface
{
    private $config;

    /**
     * Frontend controller constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $ctr ControllerCollection */
        $ctr = $app['controllers_factory'];
        $ctr->value(Zone::KEY, Zone::FRONTEND);

        $ctr->match($this->config['profiles']['prefix'] . '/{username}', [$this, 'profile'])
            ->bind('profilePublic')
            ->method(Request::METHOD_GET);

        return $ctr;
    }

    /**
     * Get the user based on the url parameter and
     * render the profile template
     *
     * @param Request $request
     * @param Application $app
     * @param $username
     * @return mixed
     */
    public function profile(Request $request, Application $app, $username)
    {
        $accessControl = New Profile($app, $this->config);

        $user = $app['users']->getUser($username);

        if (!$user || !$accessControl->hasProfile($user)) {
            throw new NotFoundHttpException;
        }


        return $app['twig']->render($this->config['profiles']['template'], [
            'user' => $user
        ]);
    }
}
