<?php

namespace App\Traits;

use Doctrine\ORM\EntityManager;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

trait ContainerTrait
{
    protected function getContainer(): ContainerInterface
    {
        if (!isset($this->container)) {
            throw new RuntimeException('Container property is either not found or set to null!');
        }

        return $this->container;
    }

    protected function getCsrfTokenManager(): CsrfTokenManager
    {
        return $this->getContainer()->get('security.csrf.token_manager');
    }

    /**
     * @param string $view
     * @param array  $parameters
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get('twig');

        return $twig->render($view, $parameters);
    }

    protected function getRepository(string $entity)
    {
        /** @var EntityManager $orm */
        $orm = $this->getContainer()->get('doctrine.orm.entity_manager');

        return $orm->getRepository($entity);
    }

    protected function getRequest(): Request
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');

        return $requestStack->getCurrentRequest();
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->getContainer()->get('translator');
    }
}
