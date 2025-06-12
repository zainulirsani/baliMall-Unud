<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserNotificationController extends PublicController
{
    public function notification()
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new Response();
        }

        $role = $user->getRole();
        $type = $role === 'ROLE_USER_SELLER' ? 'seller' : 'buyer';
        /** @var NotificationRepository $repository */
        $repository = $this->getRepository(Notification::class);
        $notifications = $repository->getOrderNotification($user->getId(), $type);

        $response = new StreamedResponse();
        $response->setCallBack(function () use ($notifications, $role) {
            if (count($notifications) > 0) {
                foreach ($notifications as $notification) {
                    echo "data: ".json_encode($notification)."\n\n";
                    flush();
                    $this->handleSendNotification($role, $notification['n_id']);
                    sleep(5);
                }
            }

            sleep(10);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->send();

        return $response;
    }

    private function handleSendNotification(string $role, int $id): void
    {
        /** @var NotificationRepository $repository */
        $repository = $this->getRepository(Notification::class);
        /** @var Notification $notification */
        $notification = $repository->find($id);

        if ($role === 'ROLE_USER_SELLER') {
            $notification->setIsSentToSeller(true);
        } else {
            $notification->setIsSentToBuyer(true);
        }

        $em = $this->getEntityManager();
        $em->persist($notification);
        $em->flush();
    }
}
