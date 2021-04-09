<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use JeroenED\Webcron\Repository\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    public function loginAction(): Response
    {
        if(isset($_SESSION['isAuthenticated']) && $_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('default'));
        } elseif(isset($_COOKIE['autologin_enable']) && $_COOKIE['autologin_enable'] == true) {
            $userRepository = new User($this->getDbCon());
            $userId = $userRepository->checkAuthentication($_COOKIE['autologin_user'], $_COOKIE['autologin_auth'], true);
            if($userId !== false) {
                $_SESSION['user.id'] = $userId;
                $_SESSION['isAuthenticated'] = true;
            } else {
                return new RedirectResponse($this->generateRoute('logout'));
            }
            return new RedirectResponse($this->generateRoute('default'));
        }
        return $this->render('security/login.html.twig');
    }

    public function logoutAction(): Response
    {
        $_SESSION['isAuthenticated'] = false;
        unset($_SESSION['user.id']);
        unset($_COOKIE['autologin_auth']);
        unset($_COOKIE['autologin_user']);
        unset($_COOKIE['autologin_enable']);
        setcookie('autologin_auth', "", time() - 3600);
        setcookie('autologin_user', "", time() - 3600);
        setcookie('autologin_enable', "", time() - 3600);
        $this->addFlash('success', 'Successfully logged out');
        return new RedirectResponse($this->generateRoute('login'));
    }

    public function loginCheckAction(): Response
    {
        $request = $this->getRequest();
        $userRepository = new User($this->getDbCon());
        $credentials = $request->request->all();
        $userId = $userRepository->checkAuthentication($credentials['name'], $credentials['passwd']);
        if($userId !== false) {
            $_SESSION['user.id'] = $userId;
            $_SESSION['isAuthenticated'] = true;

            if(isset($credentials['autologin'])) {
                $token = $userRepository->createAutologinToken($credentials['passwd']);
                setcookie('autologin_auth', $token, time() + $_ENV['COOKIE_LIFETIME'], "/");
                setcookie('autologin_user', $credentials['name'], time() + $_ENV['COOKIE_LIFETIME'], "/");
                setcookie('autologin_enable', true, time() + $_ENV['COOKIE_LIFETIME'], "/");
            }
            return new RedirectResponse($this->generateRoute('default'));
        }
        $this->addFlash('danger', 'Login Failed');
        return new RedirectResponse($this->generateRoute('login'));
    }
}