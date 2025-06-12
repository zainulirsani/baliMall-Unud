<?php

namespace App\Plugins;

use App\Controller\AppControllerTrait;
use App\Entity\User;
use App\Traits\AppTrait;
use App\Traits\WebTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class BasePlugin extends AbstractController
{
    use TranslatorTrait;
    use AppControllerTrait;
    use AppTrait;
    use WebTrait;

    protected $translator;
    protected $validator;
    protected $cache;
    protected $key;
    protected $multiLang = true;

    public function __construct(
        TranslatorInterface $translator,
        ValidatorInterface $validator
    )
    {
        $this->translator = $translator;
        $this->validator = $validator;
        $this->cache = new FilesystemAdapter('fs', 3600, __DIR__.'/../../var');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['session.flash_bag'] = FlashBagInterface::class;

        return $services;
    }

    protected function getDefaultData(): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $isBuyer = true;
        $isGovernment = false;

        if ($user instanceof User) {
            $userRoles = $this->getParameter('user_roles');
            unset($userRoles['ROLE_ADMIN'], $userRoles['ROLE_USER_SELLER']);

            $isBuyer = in_array($user->getRole(), $userRoles, false);
            $isGovernment = $user->getRole() === 'ROLE_USER_GOVERNMENT';
        }

        return [
            'is_buyer' => $isBuyer,
            'is_government' => $isGovernment,
        ];
    }
}
