<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CartService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CustomExceptionController
{
    private $container;
    private $logger;
    private $twig;

    public function __construct(ContainerInterface $container, DebugLoggerInterface $logger, Environment $twig)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    /**
     * @param Throwable $exception
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function show(Throwable $exception): Response
    {
        if ($previous = $exception->getPrevious()) {
            $code = (int) $previous->getCode();

            if ($previous instanceof ResourceNotFoundException) {
                $code = 404;
            }
        } elseif (method_exists($exception, 'getStatusCode')) {
            $code = ((int) $exception->getStatusCode() < 200) ? 404 : (int) $exception->getStatusCode();
        } else {
            $code = ((int) $exception->getCode() < 200) ? 404 : (int) $exception->getCode();
        }

        /** @var RequestStack $request */
        $request = $this->container->get('request_stack');
        $request = $request->getCurrentRequest();
        $format = $request->attributes->get('_format') ?? 'html';
        /** @var SessionInterface $session */
        $session = $this->container->get('session');
        $admin = $session->get('_security_admin');
        $admin = unserialize($admin, ['allowed_classes' => true]);
        /** @var User $user */
        $user = $admin ? $admin->getUser() : [];
        $profile = [];
        $isBuyer = true;
        $isGovernment = false;

        if ($user instanceof User) {
            /** @var ManagerRegistry $manager */
            $manager = $this->container->get('doctrine');
            /** @var UserRepository $repository */
            $repository = $manager->getManager()->getRepository(User::class);
            $profile = $repository->getDataWithProfileById($user->getId());
            $userRoles = $this->container->getParameter('user_roles');
            unset($userRoles['ROLE_ADMIN'], $userRoles['ROLE_USER_SELLER']);
            $isBuyer = in_array($user->getRole(), $userRoles, false);
            $isGovernment = $user->getRole() === 'ROLE_USER_GOVERNMENT';
        }

        switch ($code) {
            case 404:
                $trans = 'message.error.not_found';
                $image = 'dist/img/404.png';
                $pageTitle = 'title.page.404';
                $template = 'error404.html.twig';
                break;
            case 403:
                $trans = 'message.error.forbidden';
                $image = 'dist/img/bg.jpg';
                $pageTitle = 'title.page.403';
                $template = 'error403.html.twig';
                break;
            default:
                $trans = 'message.error.generic';
                $image = 'dist/img/bg.jpg';
                $pageTitle = 'title.page.500';
                $template = 'error.html.twig';
                $code = 500;
                break;
        }

        $data = [
            'admin_user' => $profile,
            'content' => [],
            'exception' => $exception,
            'image' => $image,
            'is_buyer' => $isBuyer,
            'locale' => $request->getLocale(),
            'logger' => $this->logger,
            'page_title' => $pageTitle,
            'status_code' => $code,
            'status_text' => Response::$statusTexts[$code] ?? '',
            'store_owner' => false,
            'translate' => $trans,
            'user' => $user,
            'user_cart' => $this->getUserCart(),
            'is_government' => $isGovernment,
        ];

        if ($format === 'json') {
            return new JsonResponse($data);
        }

        $template = sprintf('@Twig/Exception/%s', $template);
        $response = $this->twig->render($template, $data);

        if ($format !== 'html') {
            http_response_code($code);
            echo $response;
            exit;
        }

        return new Response($response, $code);
    }

    private function getUserCart(): CartService
    {
        $config = $this->container->getParameter('cart_config');

        return new CartService($config);
    }
}
