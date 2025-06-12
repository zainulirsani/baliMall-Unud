<?php

namespace App\EventSubscriber;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    /** @var Environment $twig */
    private $twig;
    private $mode;

    public function __construct(Environment $twig, bool $mode = false)
    {
        $this->twig = $twig;
        $this->mode = $mode;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->mode) {
            $request = $event->getRequest();
            $currentRoute = $request->get('_route');

            // Admin section should not be affected by maintenance
            if (strpos($currentRoute, 'admin') !== false) {
                return;
            }

            $data = [
                'page_title' => 'message.info.under_construction_alt',
                'locale' => 'en',
            ];

            try {
                $template = $this->twig->render('@__main__/shared/maintenance.html.twig', $data);

                $event->setResponse(new Response($template));
            } catch (Exception $e) {
                exit('This should not be accessible!');
            }

            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]]
        ];
    }
}
