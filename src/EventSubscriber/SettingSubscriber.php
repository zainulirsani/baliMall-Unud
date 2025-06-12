<?php

namespace App\EventSubscriber;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SettingSubscriber implements EventSubscriberInterface
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        try {
            $cache = new FilesystemAdapter('fs', 0, __DIR__.'/../../var');
            /** @var CacheItem $settings */
            $settings = $cache->getItem(getenv('APP_SETTINGS_CACHE'));

            if (!$settings->isHit()) {
                /** @var SettingRepository $repository */
                $repository = $this->manager->getRepository(Setting::class);
                $data = $repository->getSettingsToBeCached();

                if (count($data) > 0) {
                    $settings->set($data);
                    $cache->save($settings);
                }
            }
        } catch (InvalidArgumentException $e) {
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }
}
