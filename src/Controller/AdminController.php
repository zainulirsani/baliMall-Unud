<?php

namespace App\Controller;

use App\Email\BaseMail;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DataPackageService;
use App\Service\DataTableService;
use App\Service\DJPService;
use App\Service\DokuService;
use App\Service\FileUploader;
use App\Service\HttpClientService;
use App\Service\QrCodeGenerator;
use App\Service\RajaOngkirService;
use App\Service\SendInBlueMailService;
use App\Service\SwiftMailerService;
use App\Service\TokoDaringService;
use App\Traits\AppTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;
use App\Service\SftpUploader;

class AdminController extends AbstractController implements CsrfControllerInterface
{
    use TranslatorTrait;
    use AppControllerTrait;
    use AppTrait;
    use RouteControllerTrait;

    protected $translator;
    protected $validator;
    protected $cache;
    protected $key;
    protected $entity;
    protected $dataTable;
    protected $dataPackage;
    protected $sections;
    protected $authorizedRoles = [
        'ROLE_ADMIN',
        'ROLE_ACCOUNTING_1',
        'ROLE_ACCOUNTING_2',
        'ROLE_HELPDESK_USER',
        'ROLE_HELPDESK_MERCHANT',
        'ROLE_ADMIN_PRODUCT',
        'ROLE_ADMIN_MERCHANT',
        'ROLE_ADMIN_VOUCHER',
        'ROLE_SUPER_ADMIN',
        'ROLE_ADMIN_MERCHANT_CABANG',
    ];

    protected $templates = [
        'index' => '@__main__/admin/%s/index.html.twig',
        'view' => '@__main__/admin/%s/view.html.twig',
        'form' => '@__main__/admin/%s/form.html.twig',
        'import' => '@__main__/admin/%s/import.html.twig',
    ];

    protected $roleParam;

    protected $sftpUploader;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        ?SftpUploader $sftpUploader=null
    )
    {
        $this->translator = $translator;
        $this->validator = $validator;

        $this->setLocale('en');
        $this->authorizeAccess($authorizationChecker);

        $this->cache = new FilesystemAdapter('fs', 3600, __DIR__.'/../../var');
        $this->dataTable = new DataTableService($this->key);
        $this->dataPackage = new DataPackageService($this->key ?? 'default');
        $this->sftpUploader = $sftpUploader;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['session.flash_bag'] = FlashBagInterface::class;
        $services['logger'] = LoggerInterface::class;
        $services[SendInBlueMailService::class] = SendInBlueMailService::class;
        $services[HttpClientService::class] = HttpClientService::class;
        $services[SwiftMailerService::class] = SwiftMailerService::class;
        $services[RajaOngkirService::class] = RajaOngkirService::class;
        $services[BaseMail::class] = BaseMail::class;
        $services[QrCodeGenerator::class] = QrCodeGenerator::class;
        $services[FileUploader::class] = FileUploader::class;
        $services[TokoDaringService::class] = TokoDaringService::class;
        $services[DJPService::class] = DJPService::class;
        $services[DokuService::class] = DokuService::class;

        return $services;
    }

    public function getCsrfType(): string
    {
        return 'admin';
    }

    protected function authorizeAccess(AuthorizationCheckerInterface $authorizationChecker): void
    {
        if (false === $authorizationChecker->isGranted($this->authorizedRoles)) {
            throw new AccessDeniedException($this->translator->trans('message.error.403'));
        }
    }

    protected function getUserProfile(int $userId)
    {
        /** @var UserRepository $repository */
        $repository = $this->getRepository(User::class);

        return $repository->getDataWithProfileById($userId);
    }

    protected function getDefaultData(): array
    {
        $this->manipulateDataPackage();

        /** @var User $user */
        $user = $this->getUser();

        return [
            'admin_login' => true,
            'admin_user' => $this->getUserProfile($user->getId()),
            'city_data' => $this->manipulateCitiesData(),
            'data_package' => $this->dataPackage->getPackageInformation(),
            'date_picker' => true,
            'dt_script' => 'v1',
            'page_section' => $this->key,
            'province_data' => $this->get(RajaOngkirService::class)->getProvince(),
            'text_editor' => true,
            'locale' => $this->getLocale(),
        ];
    }

    protected function manipulateDataPackage(): void
    {
        //
    }

    protected function isAuthorizedToManage(): bool
    {
        return in_array($this->getUser()->getRole(), $this->authorizedRoles);
    }

    protected function isAuthorizedToChangeStatus(): bool
    {
        return $this->getUser()->getRole() == 'ROLE_SUPER_ADMIN';
    }

    protected function getAdminMerchantCabangProvince()
    {
        $user = $this->getUser();

        if ($user->getRole() === 'ROLE_ADMIN_MERCHANT_CABANG') {
            return $user->getAdminMerchantBranchProvince() ?? null;
        }

        return null;
    }

    protected function checkAuthorizedAdminCabang(string $merchantProvinceId)
    {
        $user = $this->getUser();

        if ($user->getRole() === 'ROLE_ADMIN_MERCHANT_CABANG') {
            if ($merchantProvinceId !== (string) $user->getAdminMerchantBranchProvince()) {
                throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
            }
        }
    }
}
