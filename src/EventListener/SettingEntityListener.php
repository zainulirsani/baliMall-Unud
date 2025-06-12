<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

class SettingEntityListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        //$event->getSubject();
    }
}
