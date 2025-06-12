<?php

namespace App\Controller;

use App\Email\BaseMail;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\BreadcrumbService;
use App\Service\CartService;
use App\Service\DokuService;
use App\Service\BniService;
use App\Service\BpdSnapService;
use App\Service\DJPService;
use App\Service\FileUploader;
use App\Service\GoSendService;
use App\Service\HttpClientService;
use App\Service\MidtransService;
use App\Service\QrCodeGenerator;
use App\Service\RajaOngkirService;
use App\Service\SamitraService;
use App\Service\SendInBlueMailService;
use App\Service\SwiftMailerService;
use App\Service\TokoDaringService;
use App\Service\SftpUploader;
use App\Service\SanitizerService;
use App\Traits\AppTrait;
use App\Traits\WebTrait;
use phpDocumentor\Reflection\Types\This;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;


class PublicController extends AbstractController implements CsrfControllerInterface
{
    use TranslatorTrait;
    use AppControllerTrait;
    use AppTrait;
    use WebTrait;

    protected $translator;
    protected $validator;
    protected $cache;
    protected $key;
    protected $redBox = true;
    protected $multiLang = true;
    protected $allowedParentCategory;
    protected $allowedCategories;
    protected $sftpUploader;
    // protected $sanitizer;

    public function __construct(
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        ?SftpUploader $sftpUploader=null
        // SanitizerService $sanitizer
    )
    {
        $this->translator = $translator;
        $this->validator = $validator;
        $this->cache = new FilesystemAdapter('fs', 3600, __DIR__.'/../../var');

        $this->allowedParentCategory = 8;
        $this->allowedCategories = [8, 25, 26];

        //$this->setLocale('id');
        $this->sftpUploader = $sftpUploader;
        // $this->sanitizer = $sanitizer;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['user.cart'] = CartService::class;
        $services['session.flash_bag'] = FlashBagInterface::class;
        $services['logger'] = LoggerInterface::class;
        $services[SendInBlueMailService::class] = SendInBlueMailService::class;
        $services[HttpClientService::class] = HttpClientService::class;
        $services[SwiftMailerService::class] = SwiftMailerService::class;
        $services[RajaOngkirService::class] = RajaOngkirService::class;
        $services[FileUploader::class] = FileUploader::class;
        $services[BaseMail::class] = BaseMail::class;
        $services[QrCodeGenerator::class] = QrCodeGenerator::class;
        $services[SamitraService::class] = SamitraService::class;
        $services[GoSendService::class] = GoSendService::class;
        $services[DokuService::class] = DokuService::class;
        $services[BniService::class] = BniService::class;
        $services[BpdSnapService::class] = BpdSnapService::class;
        $services[DJPService::class] = DJPService::class;
        $services[MidtransService::class] = MidtransService::class;
        $services[TokoDaringService::class] = TokoDaringService::class;
        $services[SanitizerService::class] = SanitizerService::class;

        return $services;
    }

    public function getCsrfType(): string
    {
        return 'public';
    }

    protected function getUserProfile()
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserRepository $repository */
        $repository = $this->getRepository(User::class);

        return $repository->getDataWithProfileById($user->getId());
    }


    protected function getDefaultData(): array
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $transactionTotal = 0;
        $transactionSellerUnread = 0;
        $transactionBuyerUnread = 0;
        $messagesTotal = 0;
        $messagesUnread = 0;
        $storeId = 0;
        $storeOwner = false;
        $userLogin = false;
        $userCart = $this->getUserCart();
        $isBuyer = true;
        $isGovernment = false;
        $categoryWithParents = $this->getProductCategoriesWithParentsData();
        $productCategoryData = [];
        $productCategoryIndex = 0;
        $locales = $this->getParameter('supported_locales');

        if ($user instanceof User) {
            $messagesTotal = 0;
            $messagesUnread = 0;
            $userLogin = true;
            $userRoles = $this->getParameter('user_roles');
            unset($userRoles['ROLE_ADMIN'], $userRoles['ROLE_USER_SELLER']);
            $isBuyer = in_array($user->getRole(), $userRoles, false);
            $isGovernment = $user->getRole() === 'ROLE_USER_GOVERNMENT';

            if (!$session->get('login_time')) {
                $session->set('login_time', time());
            }

            if (!$session->has('user_has_store')) {
                /** @var Store[] $stores */
                $stores = $user->getStores();

                if ($storeOwner = (count($stores) > 0)) {
                    $session->set('user_has_store', [
                        'id' => (int) $stores[0]->getId(),
                        'slug' => $stores[0]->getSlug(),
                        'owner' => true,
                    ]);
                }
            } else {
                $store = $session->get('user_has_store');
                $storeId = $store['id'];
                $storeOwner = $store['owner'];
            }
        }

        foreach ($categoryWithParents as $categoryWithParent) {
            if (!empty($categoryWithParent['parent_id'])) {
                $productCategoryData[($productCategoryIndex - 1)]['children'][] = [
                    'id' => $categoryWithParent['id'],
                    'text' => $categoryWithParent['text'],
                ];
            } else {
                $productCategoryData[$productCategoryIndex] = [
                    'id' => $categoryWithParent['id'],
                    'text' => $categoryWithParent['text'],
                    'children' => [],
                ];

                $productCategoryIndex++;
            }
        }

        return [
            'breadcrumbs' => BreadcrumbService::all(),
            'colorbox' => false,
            'date_picker' => false,
            'is_buyer' => $isBuyer,
            'is_government' => $isGovernment,
            'locale' => count($locales) > 0 ? $this->getLocale() : 'id',
            'messages' => [
                'total' => $messagesTotal,
                'unread' => $messagesUnread,
            ],
            'public_user' => [],
            'product_category_data' => $productCategoryData,
            'product_category_search_filter' => $this->getProductCategoriesFeatured(0, 'no', 'yes'),
            'product_category_featured' => $this->getProductCategoriesFeatured(),
            'red_box' => $this->redBox,
            'search_token_id' => 'form_search_global',
            'store_id' => $storeId,
            'store_owner' => $storeOwner,
            'text_editor' => false,
            'transaction' => [
                'total' => $transactionTotal,
                'seller_unread' => $transactionSellerUnread,
                'buyer_unread' => $transactionBuyerUnread,
            ],
            'user_cart' => $userCart,
            'user_login' => $userLogin,
            'user_notification' => $this->getUserNotification(['read' => 'no']),
        ];
    }

    public function authorizeApiRequest():bool
    {
        $token = getenv('BANDARA_API_TOKEN');;
        $request = $this->getRequest();

        if (!$request->headers->has('Authorization')) {

            return false;
        }

        if ($request->headers->get('Authorization') !== $token) {
            return  false;
        }

        return true;

    }
}
