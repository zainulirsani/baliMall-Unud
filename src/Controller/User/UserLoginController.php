<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserLoginController extends PublicController
{
    public function login(AuthenticationUtils $authUtils, LoggerInterface $logger)
    {
        $request = $this->getRequest();
        $error = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();
        $headers = $request->headers->all();
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $template = '@__main__/public/user/login.html.twig';
        $loginPath = 'login_check';
        $targetPath = isset($headers['referer']) ? parse_url($headers['referer'][0], PHP_URL_PATH) : '/user/dashboard';
        $targetPath = $targetPath === '/' ? '/user/dashboard' : $targetPath;
        $langId = $this->getLocale();


        $session->set(getenv('REFERRER_PATH'), $targetPath);

        if ($request->attributes->get('_route') === 'admin_login') {
            if (null !== $session->get('_security_main')) {
                return $this->redirectToRoute('user_dashboard');
            }

            if (null !== $session->get('_security_admin')) {
                return $this->redirectToRoute('admin_dashboard');
            }

            $template = '@__main__/admin/user/login.html.twig';
            $loginPath = 'admin_login_check';
            $langId = 'en';
        } else {
            if ($session->has('_security_admin')) {
                $admin = $session->get('_security_admin');
                /** @var UsernamePasswordToken $admin */
                $admin = unserialize($admin, ['allowed_classes' => true]);
                $admin = $admin->getUser();
                $redirect = 'homepage';

                if ($admin instanceof User) {
                    switch ($admin->getRole()) {
                        case 'ROLE_USER':
                            $redirect = 'user_dashboard';
                            break;
                        case 'ROLE_ADMIN':
                        case 'ROLE_ACCOUNTING_1':
                        case 'ROLE_ACCOUNTING_2':
                        case 'ROLE_HELPDESK_USER':
                        case 'ROLE_HELPDESK_MERCHANT':
                        case 'ROLE_ADMIN_PRODUCT':
                        case 'ROLE_ADMIN_MERCHANT':
                        case 'ROLE_ADMIN_VOUCHER':
                        case 'ROLE_SUPER_ADMIN':
                        case 'ROLE_ADMIN_MERCHANT_CABANG':
                            $redirect = 'admin_dashboard';
                            break;
                    }
                }

                return $this->redirectToRoute($redirect);
            }

            if (null !== $session->get('_security_main')) {
                $user_main = $session->get('_security_main');
                $user_main = unserialize($user_main, ['allowed_classes' => true]);
                $user_main = $user_main->getUser();
                $repository = $this->getRepository(User::class);
                $userLogin = $repository->find($user_main->getId());
                if ($userLogin->getSubRole() == 'PPK' or $userLogin->getSubRole() == 'TREASURER') {
                    return $this->redirectToRoute('user_ppktreasurer_dashboard');
                } else {
                    return $this->redirectToRoute('user_dashboard');
                }
            }
        }

        $data = [
            'error' => $error,
            'last_username' => $lastUsername,
            'login_path' => $loginPath,
            'target_path' => $targetPath,
            'popup_text' => null
        ];

        if ($error) {
            $translator = $this->getTranslator();
            $message = $error->getMessage();
            //$popupText = $translator->trans($error->getMessageKey(), $error->getMessageData(), 'security', $langId);
            $popupText = $translator->trans('user.inactive_account', [], 'validators', $langId);

            $logger->error('User failed to login!', ['message' => $message]);

            switch ($message) {
                case 'Invalid credentials.':
                case 'Bad credentials.':
                    $popupText = $translator->trans('user.invalid_credentials', [], 'validators', $langId);
                    break;
                case 'Invalid CSRF token.':
                    $popupText = $translator->trans('message.error.csrf', [], 'messages', $langId);
                    break;
                case 'Account is disabled.':
                case 'User account "'.$lastUsername.'" is not yet activated.':
                case 'Authentication request could not be processed due to a system problem.':
                    $popupText = $translator->trans('user.inactive_account', [], 'validators', $langId);
                    break;
                //case 'Authentication request could not be processed due to a system problem.':
                //    $popupText = $translator->trans($error->getMessageKey(), $error->getMessageData(), 'security', $langId);
                //    break;
            }

            $data['popup_text'] = $popupText;
        }

        if ($session->has('b2c_disabled')) {
            $translator = $this->getTranslator();

            $data['error'] = true;
            $data['popup_text'] = $translator->trans('user.not_valid', [], 'validators', $langId);
            $session->remove('b2c_disabled');
        }

        if ($loginPath === 'login_check') {
            $data['page_title'] = 'title.login';
        }

        return $this->view($template, $data);
    }
}
