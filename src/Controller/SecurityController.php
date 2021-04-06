<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
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
}