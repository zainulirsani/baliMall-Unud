<?php

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ProductSubscriber implements EventSubscriberInterface
{
    private $manager;
    private $logger;

    public function __construct(EntityManagerInterface $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            $route = $request->attributes->get('_route');

            if (strpos($route, 'user_product') !== false
                || strpos($route, 'admin_product') !== false) {
                $request->request->set('overridden', $this->manager->isOpen() ? 'yes' : 'no');

                $this->logger->error('*** Start ***');
                $this->logger->error('Product Form Data', $request->request->all());
                $this->logger->error('*** End ***');
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}
