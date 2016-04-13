<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

use Bolt\Application;
use Bolt\BaseExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Extension extends BaseExtension
{
    /**
     * Construct the Extension and register twig folder when in backend
     *
     * Extension constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        if ($this->app['config']->getWhichEnd() == 'backend') {
            $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/twig');
        }
    }

    /**
     * Initialize the extension
     * - Extend IntegrityChecker
     * - Register URLs
     * - Register menu option
     * - Triggers user object check
     * - Register Twig functions
     */
    public function initialize()
    {
        $this->app['integritychecker'] = $this->app->share(
            function ($app) {
                return new IntegrityChecker($app, $this->config);
            }
        );

        if ($this->app['config']->getWhichEnd() == 'backend') {
            $backendRoot = $this->app['resources']->getUrl('bolt');

            $this->app
                ->get($backendRoot . 'profile/extended', array($this, 'profile'))
                ->bind('profile-extended');
            $this->app
                ->post($backendRoot . 'profile/extended', array($this, 'saveProfile'))
                ->bind('profile-extended-save');

            $this->addMenuOption('Extended Profile', $backendRoot . 'profile/extended', 'fa:user');

            $this->checkIfUserObjectNeedsToBeUpdated();
        }

        $this->app
            ->get($this->config['profiles']['prefix'] . '/{username}', array($this, 'publicProfile'))
            ->bind('public-profile');

        $this->addTwigFunction('avatar', 'avatar');
        $this->addTwigFunction('profile_link', 'profileLink');
        $this->addTwigFunction('has_profile', 'hasProfile');
    }

    /**
     * Checks if the user object is up to date.
     * Displays an update warning if needed.
     */
    private function checkIfUserObjectNeedsToBeUpdated()
    {
        if ($this->app['users']->isAllowed('dbcheck') &&
            $this->app['users']->isAllowed('dbupdate')
        ) {
            $fields = $this->config['fields'];
            $user = $this->app['users']->getCurrentUser();

            $incomplete = false;
            foreach (array_keys($fields) as $key) {
                if (!isset($user[$key])) {
                    $incomplete = true;
                    break;
                }
            }

            if ($incomplete &&
                $this->app['paths']['current'] != $this->app['paths']['bolt'] . 'dbcheck'
            ) {
                $this->app['session']->getFlashBag()->set(
                    'error',
                    sprintf(
                        "<b>User Profiles:</b> Your users table is outdated. Go to 'Configuration' > '<a href='%s'>Check Database</a>' to update it.",
                        $this->app['paths']['protocol'] . '://' . $this->app['paths']['hostname'] . $this->app['paths']['bolt'] . 'dbcheck'
                    )
                );
            }
        }
    }

    /**
     * Route handler for extended user profile edit page
     *
     * @param Request $request
     * @return RedirectResponse|\Twig_Markup
     */
    public function profile(Request $request)
    {
        if (!$this->app['users']->getCurrentUser()) {
            return new RedirectResponse($this->app['resources']->getUrl('bolt'));
        }

        return $this->app['twig']->render('profile_extended.twig', [
            'user' => $this->app['users']->getCurrentUser(),
            'fields' => $this->config['fields'],
            'message' => null
        ]);
    }

    /**
     * Route handler for extended user profile POST request
     *
     * @param Request $request
     * @return RedirectResponse|\Twig_Markup
     */
    public function saveProfile(Request $request)
    {
        $user = $this->app['users']->getCurrentUser();

        if (!$user) {
            return new RedirectResponse($this->app['resources']->getUrl('bolt'));
        }

        $data = $this->cleanupPostData($request->request->all(), $user);

        foreach ($data as $key => $value) {
            $user[$key] = $value;
        }

        $this->saveUser($user);

        return $this->app['twig']->render('profile_extended.twig', [
            'user' => $user,
            'fields' => $this->config['fields'],
            'message' => "User {$user['displayname']} has been saved."
        ]);
    }

    /**
     * Route handler for public user profile page
     *
     * @param Request $request
     * @param $username
     * @throws NotFoundHttpException
     * @return \Twig_Markup
     */
    public function publicProfile(Request $request, $username)
    {
        $user = $this->app['users']->getUser($username);

        if (!$user || !$this->hasProfile($user)) {
            throw new NotFoundHttpException;
        }

        return $this->app['twig']->render($this->config['profiles']['template'], [
            'user' => $user
        ]);
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

    /**
     * Modified copy of Bolts own saveUser() function
     *
     * @param array $user
     * @return mixed
     */
    private function saveUser(array $user)
    {
        unset($user['password']);
        unset($user['sessionkey']);

        // Make sure the 'stack' is set.
        if (empty($user['stack'])) {
            $user['stack'] = json_encode(array());
        } elseif (is_array($user['stack'])) {
            $user['stack'] = json_encode($user['stack']);
        }

        // Serialize roles array
        if (empty($user['roles']) || !is_array($user['roles'])) {
            $user['roles'] = '[]';
        } else {
            $user['roles'] = json_encode(array_values(array_unique($user['roles'])));
        }

        $dbPrefix = $this->app['config']->get('general/database/prefix', 'bolt_');

        return $this->app['db']->update($dbPrefix . 'users', $user, array('id' => $user['id']));
    }

    /**
     * Twig helper function to get an avatar URL for a given user, based on the extension config
     *
     * @param array $user
     * @param int $gravatar_size
     * @param null $fallback
     * @return bool|string
     */
    public function avatar(array $user, $gravatar_size = 100, $fallback = null)
    {
        $config = $this->config;

        if ($fallback) {
            $config['avatars']['fallback_url'] = $fallback;
        }

        $avatarResolver = new AvatarUrlResolver($this->app, $config);

        return $avatarResolver->resolve($user, $gravatar_size);
    }

    /**
     * Twig helper function to get the profile URL for a given user
     *
     * @param array $user
     * @return string
     */
    public function profileLink(array $user)
    {
        if ($this->hasProfile($user)) {
            return '/' . $this->config['profiles']['prefix'] . '/' . $user['username'];
        }

        return '';
    }

    /**
     * Determine if the given user has a public profile
     * Used as Twig function and for internal checks
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

        // because of BC
        if ($permissionConfig) {
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

        return true;
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return "User Profiles";
    }
}

