<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $session = $request->getSession();
            $user = $this->getUser();
            $session->set('_locale', $user->getLocale());
            return new RedirectResponse($this->generateUrl('job_index', ['_locale' => $user->getLocale()]));
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
             'controller_name' => 'LoginController',
             'last_username' => $lastUsername,
             'error'         => $error
        ]);
    }

    public function logoutAction(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    public function loginCheckAction(): void
    {

    }
}