<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerTrait
{
    protected function deniedAccess(string $role = 'ROLE_SUPER_ADMIN'): void
    {
        if (false === $this->getAuthorizationChecker()->isGranted(strtoupper($role))) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }
    }

    protected function isAjaxRequest(string $method = 'GET'): void
    {
        $request = $this->getRequest();

        if (!$request->isXmlHttpRequest() && !$request->isMethod(strtoupper($method))) {
            throw $this->createNotFoundException($this->getTranslator()->trans('message.error.404'));
        }
    }

    protected function view(string $template, array $data = [], string $type = 'template')
    {
        if ($type === 'json') {
            return $this->json($data);
        }

        if ($type === 'html') {
            return $this->stream($template, $data);
        }

        return $this->render($template, array_merge($this->getDefaultData(), $data));
    }

    protected function getAppRoute(string $section = 'index'): string
    {
        return sprintf('admin_%s_%s', $this->key, $section);
    }

    protected function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        /** @var AuthorizationCheckerInterface */
        return $this->getService('security.authorization_checker');
    }

    protected function getBaseUrl(): string
    {
        return $this->getRequest()->getSchemeAndHttpHost();
    }

    protected function getCache(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return $this->cache;
    }

    protected function getDefaultData(): array
    {
        return [];
    }

    protected function getEntityManager(): ObjectManager
    {
        /** @var ManagerRegistry $orm */
        $orm = $this->getDoctrine();

        return $orm->getManager();
    }

    protected function getRequest(): Request
    {
        /** @var RequestStack $request */
        $request = $this->getService('request_stack');

        return $request->getCurrentRequest();
    }

    protected function getRepository(string $entity): ObjectRepository
    {
        /** @var ManagerRegistry $orm */
        $orm = $this->getDoctrine();

        return $orm->getRepository($entity);
    }

    protected function getService(string $serviceId)
    {
        /** @var ContainerInterface $container */
        $container = $this->container;

        return $container->get($serviceId);
    }

    protected function getSession()
    {
        return $this->getService('session');
    }

    protected function getSetting(string $slug)
    {
        try {
            $cache = $this->getCache();
            /** @var CacheItem $settings */
            $settings = $cache->getItem(getenv('APP_SETTINGS_CACHE'));

            if ($settings = $settings->get()) {
                return $settings[$slug] ?? null;
            }
        } catch (InvalidArgumentException $e) {
        }

        return null;
    }

    protected function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface */
        return $this->translator;
    }

    protected function getValidator(): ValidatorInterface
    {
        /** @var ValidatorInterface */
        return $this->validator;
    }
}
