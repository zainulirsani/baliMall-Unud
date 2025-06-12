<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The custom listener must be called before LocaleListener,
 * which initializes the locale based on the current request.
 *
 * To do so, set your listener priority to a higher value than LocaleListener priority
 * (which you can obtain running the debug:event kernel.request command)
 *
 * Source: https://symfony.com/doc/current/translation/locale.html
 *
 * Class LocaleSubscriber
 * @package App\EventSubscriber
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    private $adminUrl;
    private $languages;

    public function __construct(string $defaultLocale = 'en', string $adminUrl = 'admin', array $languages = [])
    {
        $this->defaultLocale = $defaultLocale;
        $this->adminUrl = $adminUrl;
        $this->languages = $languages;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $session = $request->getSession();

        if ($session->has('_current_locale')) {
            $request->query->set('lang', $session->get('_current_locale'));
        }

        $attributes = $request->attributes->all();
        $requestUri = $request->server->get('REQUEST_URI', '/');
        $format1 = sprintf('%s_', $this->adminUrl);
        $format2 = sprintf('/%s', $this->adminUrl);
        $isAdmin = true;

        // Force locale to 'id' for FE section
        if (isset($attributes['_route']) && strpos($attributes['_route'], $format1) === false) {
            $this->defaultLocale = 'id';
            $isAdmin = false;
        }

        // Force locale to 'id' for FE section
        if (strpos($requestUri, $format2) === false) {
            $this->defaultLocale = 'id';
            $isAdmin = false;
        }

        if (!$isAdmin && count($this->languages) > 0 && $request->query->has('lang')) {
            $this->defaultLocale = $this->checkLocale($request->query->get('lang'));
        }

        if ($isAdmin) {
            $this->defaultLocale = 'en';
        }

        $request->setLocale($this->defaultLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 120]]
        ];
    }

    private function checkLocale($lang)
    {
        return (!in_array(strtolower($lang), $this->languages, false)) ? $this->defaultLocale : strtolower($lang);
    }
}
