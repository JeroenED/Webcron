<?php


namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $session = $request->getSession();
            $user = $this->getUser();
            $session->set('_locale', $user->getLocale());
            return new RedirectResponse($this->generateUrl('job_index', ['_locale' => $user->getLocale()]));
        }
        if($request->cookies->has('logout-notice')) {
            $this->addFlash('success', 'settings.flashes.passwordsaved');
            $res = new Response();
            $res->headers->clearCookie('logout-notice');
            $res->sendHeaders();
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

    public function settingsAction(Request $request)
    {
        $params['locales'] = $this->getParameter('enabled_locales');
        $params['user'] = $this->getUser();
        return $this->render('settings.html.twig', $params);
    }

    public function settingsSaveAction(Request $request, ManagerRegistry $em, UserPasswordHasherInterface $passwordHasher)
    {
        $session = $request->getSession();
        $data = $request->request->all();
        $locale = $request->getLocale();
        $userRepo = $em->getRepository(User::class);
        $route = 'settings';

        /** @var User $user */
        $user = $this->getUser();
        if(!empty($data['locale']) && $user->getLocale() != $data['locale']) {
            $error = false;
            if(!array_key_exists($data['locale'], $this->getParameter('enabled_locales'))) {
                $error = true;
                $this->addFlash('danger', 'settings.flashes.inexistinglocale');
            }
            if(!$error) {
                $userRepo->setLocale($user, $data['locale']);
                $locale = $data['locale'];
                $this->addFlash('success', 'settings.flashes.localesaved');
            }
        }
        if(!empty($data['current']) && !empty($data['password'])) {
            $error = false;
            if (empty($data['repeat']) || ($data['password'] != $data['repeat'])) {
                $error = true;
                $this->addFlash('danger', 'settings.flashes.repeatpasswordnotok');
            } elseif(!$passwordHasher->isPasswordValid($user, $data['current'])) {
                $error = true;
                $this->addFlash('danger', 'settings.flashes.currentpassnotok');
            }
            if(!$error) {
                $hashedpass = $passwordHasher->hashPassword($user, $data['password']);
                $userRepo->setPassword($user, $hashedpass);
                $cookie = new Cookie('logout-notice', true, (time() + 2), secure: $request->isSecure());
                $res = new Response();
                $res->headers->setCookie( $cookie );
                $res->sendHeaders();
                $route = 'logout';
            }
        }

        return $this->redirect($this->generateUrl($route, ['_locale' => $locale]));
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