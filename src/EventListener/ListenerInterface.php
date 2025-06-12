<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

interface ListenerInterface
{
    public function handle(GenericEvent $event): void;
}
