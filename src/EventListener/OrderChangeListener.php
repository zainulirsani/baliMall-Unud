<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\OrderChangeLog;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderChangeListener implements ListenerInterface
{
    public function handle(GenericEvent $event): void
    {
        if ($event->hasArgument('previousOrderValues') &&
            $event->hasArgument('currentOrderValues') &&
            $event->hasArgument('user') &&
            $event->hasArgument('em'))
        {
            $em = $event->getArgument('em');
            $user = $event->getArgument('user');
            $previousOrderValues = $this->parseObjectToArray($event->getArgument('previousOrderValues'));
            $currentOrderValues = $this->parseObjectToArray($event->getArgument('currentOrderValues'));
            $user = $event->getArgument('user');
            $isCreated = $event->getArgument('is_created') ?? false;

            $diff = array_diff($currentOrderValues, $previousOrderValues);

            if ($isCreated) {
                $userData = [
                    'id' => $user->getId(),
                    'username' => $user->getUserName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'subRole' => $user->getSubRole(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'phoneNumber' => $user->getPhoneNumber(),
                ];

                $orderChangeLog = new OrderChangeLog();
                $orderChangeLog->setOrderId($previousOrderValues['id']);
                $orderChangeLog->setUserId($user->getId());
                $orderChangeLog->setPreviousValues(['status' => 'pending']);
                $orderChangeLog->setCurrentValues($currentOrderValues);
                $orderChangeLog->setChanges(['status' => $currentOrderValues['isB2gTransaction'] ? 'confirmed' : 'pending']);
                $orderChangeLog->setUser($userData);

                $em->persist($orderChangeLog);
                $em->flush();
            }

            if (count($diff) > 0) {

                if (isset($diff['updatedAt'])) {
                    unset($diff['updatedAt']);
                }

                $userData = [
                    'id' => $user->getId(),
                    'username' => $user->getUserName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'subRole' => $user->getSubRole(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'phoneNumber' => $user->getPhoneNumber(),
                ];

                $orderChangeLog = new OrderChangeLog();
                $orderChangeLog->setOrderId($previousOrderValues['id']);
                $orderChangeLog->setUserId($user->getId());
                $orderChangeLog->setPreviousValues($previousOrderValues);
                $orderChangeLog->setCurrentValues($currentOrderValues);
                $orderChangeLog->setChanges($diff);
                $orderChangeLog->setUser($userData);

                $em->persist($orderChangeLog);
                $em->flush();
            }
        }
    }

    public function parseObjectToArray($order): array
    {
        $parsedOrder = [];

        if ($order instanceof Order) {
            $parsedOrder = $order->toArray();

            $parsedOrder['createdAt'] = ($parsedOrder['createdAt'])->format('Y-m-d H:i:s');
            $parsedOrder['updatedAt'] = ($parsedOrder['updatedAt'])->format('Y-m-d H:i:s');

            foreach ($parsedOrder as $key => $item) {
                if (is_object($item)) {
                    try {
                        $parsedOrder[$key] = $item->getId();
                    }catch (\Throwable $throwable) {
                        $parsedOrder[$key] = null;
                    }
                }elseif (is_array($item)) {
                    $parsedOrder[$key] = json_encode($item);
                }


                if ($key == 'isApprovedPPK') {
                    $parsedOrder[$key] = $item != null ? ($item ? 'true' : 'false') : 'null';
                }
            }
        }

        return $parsedOrder;
    }
}
