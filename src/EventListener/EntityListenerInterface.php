<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

interface EntityListenerInterface
{
    public function handle(GenericEvent $event): void;
}
