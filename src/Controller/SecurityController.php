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
        }
        return $this->render('security/login.html.twig');
    }

    public function loginCheckAction(): Response
    {
        $request = $this->getRequest();
        $userRepository = new User($this->getDbCon());
        $credentials = $request->request->all();
        if($userRepository->checkAuthentication($credentials['name'], $credentials['passwd'])) {
            $_SESSION['isAuthenticated'] = true;
            return new RedirectResponse($this->generateRoute('default'));
        }
        return new RedirectResponse($this->generateRoute('login'));
    }
}