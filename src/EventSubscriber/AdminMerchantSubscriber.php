<?php

namespace App\EventSubscriber;

use App\Controller\PublicController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminMerchantSubscriber extends PublicController implements EventSubscriberInterface
{
    protected $key = 'admin_merchant';

    public function onKernelController($event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }

        $user = $this->getUser();
        $session = $this->getSession();
        $sessionKey = '_security_main';

//        if (null !== $session->get($sessionKey)) {
//            if ($user && $user->getRole() === 'ROLE_USER_SELLER') {
//                $operatorSession = $session->get($this->key);
//                $request = $this->getRequest();
//                $requestPath = $request->getPathInfo();
//
//                if ($operatorSession === null &&
//                    str_contains($requestPath, '/operator/select') === false &&
//                    count($user->getOperators()) > 0
//                ) {
//                    header('Location: /user/operator/select');
//                    exit;
//                }
//
//                if ($operatorSession !== null) {
//                    $this->checkValidAccess();
//                }
//            }
//        }

        return true;
    }

    private function checkValidAccess(){
        $session = $this->getSession();
        $request = $this->getRequest();
        $requestPath = $request->getPathInfo();
        $operator = $session->get($this->key);

        try {
            $opRole = $operator->getRole();
        }catch (\Throwable $throwable) {
            throw new AccessDeniedException("Not allowed, invalid role");
        }

        /**
         * @TODO perlu cek lagi apakah str_contains safe
         */
        if ($opRole !== 'ROLE_ADMIN_MERCHANT_OWNER') {
            if (str_contains($requestPath, '/order') && $opRole !== 'ROLE_ADMIN_MERCHANT_ORDER'){
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/user/payment-confirmation') && $opRole !== 'ROLE_ADMIN_MERCHANT_ORDER') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/store') && $opRole !== 'ROLE_ADMIN_MERCHANT_OWNER') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/profile') && $opRole !== 'ROLE_ADMIN_MERCHANT_CUSTOMER') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/chat') && $opRole !== 'ROLE_ADMIN_MERCHANT_CHAT') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/user/operator') && $opRole !== 'ROLE_ADMIN_MERCHANT') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }

            if (str_contains($requestPath, '/user/product') && $opRole !== 'ROLE_ADMIN_MERCHANT_PRODUCT') {
                throw new AccessDeniedException("Not allowed, invalid role");
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}
