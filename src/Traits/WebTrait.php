<?php

namespace App\Traits;

use App\Entity\Notification;
use App\Entity\Store;
use App\Entity\User;
use App\Exception\StoreInactiveException;
use App\Repository\NotificationRepository;
use App\Repository\StoreRepository;
use App\Service\CartService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait WebTrait
{
    public function getUserStore()
    {
        /** @var SessionInterface $session */
        $session = $this->getSession();

        if ($session->has('user_has_store')) {
            $store = $session->get('user_has_store');
            /** @var StoreRepository $repository */
            $repository = $this->getRepository(Store::class);

            return $repository->find((int) $store['id']);
        }

        return null;
    }

    public function getUserCart(): CartService
    {
        return $this->get('user.cart');
    }

    public function getTranslation(string $id, array $parameters = [], string $domain = 'messages', string $locale = 'id')
    {
        $locale = $this->multiLang ? $this->getLocale() : $locale;

        return $this->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @throws StoreInactiveException
     */
    public function checkForInvalidStoreAccess(): void
    {
        /** @var Store $userStore */
        $userStore = $this->getUserStore();

        if (!empty($userStore) && !$userStore->getIsActive() || (!empty($userStore) && $userStore->getStatus() && $userStore->getStatus() === 'DRAFT')) {
            throw new StoreInactiveException($this->getTranslation('message.info.store_inactive'));
        }
    }

    public function getUserNotification(array $parameters = []): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = [];
        $default = [
            'limit' => 100,
            'sort_by' => 'n.id',
            'order_by' => 'DESC',
        ];

        if ($user instanceof User) {
            /** @var NotificationRepository $repository */
            $repository = $this->getRepository(Notification::class);
            $data = $repository->getOrderNotification($user->getId(), 'seller', array_merge($default, $parameters));
        }

        return [
            'count' => count($data),
            'data' => $data,
        ];
    }
}
