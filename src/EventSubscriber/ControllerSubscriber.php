<?php

namespace App\EventSubscriber;

use App\Controller\CsrfControllerInterface;
use App\Kernel;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;

class ControllerSubscriber implements EventSubscriberInterface
{
    private $container;
    private $ajaxCsrfToken;

    public function __construct(ContainerInterface $container, $ajaxCsrfToken)
    {
        $this->container = $container;
        $this->ajaxCsrfToken = $ajaxCsrfToken;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        /**
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        /**
         * Check valid CSRF token on every POST request
         * if the Controller is an instance of CsrfControllerInterface
         */
        if ($controller[0] instanceof CsrfControllerInterface) {
            /** @var TranslatorInterface $translator */
            $translator = $this->container->get('translator');
            $controllerType = $controller[0]->getCsrfType();

            if (!in_array($controllerType, ['public', 'admin'])) {
                throw new UnexpectedValueException($translator->trans('message.error.csrf'));
            }

            $request = $event->getRequest();

            if ($request->isMethod('POST')) {
                $routeName = $request->attributes->get('_route', '_default_');
                $csrfWhitelist = $this->container->getParameter('csrf_whitelist');

                if (!in_array($routeName, $csrfWhitelist, false)) {
                    $token = $request->request->get('_csrf_token');
                    $tokenId = $request->request->get('_csrf_token_id');
                    $ajaxTokenID = $this->ajaxCsrfToken[$controllerType];
                    $csrfExcludes = $this->container->getParameter('csrf_exclude_routes');

                    if (!$token) {
                        if ($request->isXmlHttpRequest()) {
                            $this->ajaxErrorResponse();
                        }

                        throw new UnexpectedValueException($translator->trans('message.error.csrf'), 403);
                    }

                    if (!$this->isCsrfTokenValid($tokenId, $token)) {
                        if ($request->isXmlHttpRequest()) {
                            $this->ajaxErrorResponse();
                        }

                        throw new AccessDeniedException($translator->trans('message.error.csrf'));
                    }

                    if ($tokenId !== $ajaxTokenID) {
                        if (!in_array($tokenId, $csrfExcludes, false)) {
                            /** @var CsrfTokenManager $tokenManager */
                            $tokenManager = $this->container->get('security.csrf.token_manager');
                            $tokenManager->refreshToken($tokenId);
                        }

                        $request->request->remove('_csrf_token');
                        $request->request->remove('_csrf_token_id');
                    }
                }
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $request = $event->getRequest();
        $response = $event->getResponse();
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Strict-Transport-Security', 'max-age=86400; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        //$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        if ($kernel->isDebug() && $request->isXmlHttpRequest()) {
            $response->headers->set('Symfony-Debug-Toolbar-Replace', '0');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * This function is a duplicate from Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait::isCsrfTokenValid
     * because it is marked as an internal use only
     *
     * @param string      $id
     * @param null|string $token
     *
     * @return bool
     */
    private function isCsrfTokenValid(string $id, ?string $token): bool
    {
        if (!$this->container->has('security.csrf.token_manager')) {
            throw new LogicException('CSRF protection is not enabled in your application.');
        }

        /** @var CsrfTokenManager $tokenManager */
        $tokenManager = $this->container->get('security.csrf.token_manager');

        return $tokenManager->isTokenValid(new CsrfToken($id, $token));
    }

    private function ajaxErrorResponse(): void
    {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Invalid CSRF Token!']);
        exit;
    }
}
