<?php

namespace App\EventSubscriber;

use App\Service\RajaOngkirService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RajaOngkirSubscriber implements EventSubscriberInterface
{
    private $parameters;
    private $logger;

    public function __construct(array $parameters, LoggerInterface $logger)
    {
        $this->parameters = $parameters;
        $this->logger = $logger;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        try {
            $cache = new FilesystemAdapter('fs', 0, __DIR__.'/../../var');
            /** @var CacheItem $provinceData */
            $provinceData = $cache->getItem('ro_province_data');
            /** @var CacheItem $cityData */
            $cityData = $cache->getItem('ro_city_data');
            $rajaOngkir = new RajaOngkirService($this->parameters, $this->logger);

            if (!$provinceData->isHit()) {
                $rajaOngkir->getProvince();
            }

            if (!$cityData->isHit()) {
                $rajaOngkir->getCity();
            }
        } catch (InvalidArgumentException $e) {
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}
