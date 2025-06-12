<?php

namespace App\Controller\Order;

use App\Controller\AdminController;
use App\Email\BaseMail;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Bank;
use App\Entity\Doku;
use App\Entity\OrderChangeLog;
use App\Entity\OrderComplaint;
use App\Entity\OrderPayment;
use App\Entity\OrderProduct;
use App\Entity\OrderShippedFile;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserPpkTreasurer;
use App\Entity\UserPicDocument;
use App\Entity\VirtualAccount;
use App\Entity\VoucherUsedLog;
use App\EventListener\OrderChangeListener;
use App\EventListener\OrderEntityListener;
use App\EventListener\RemoveOrderPaymentEntityListener;
use App\EventListener\SetOrderSharedInvoiceEntityListener;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\DokuRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\VirtualAccountRepository;
use App\Service\HttpClientService;
use App\Service\DJPService;
use App\Service\DokuService;
use App\Service\TokoDaringService;
use App\Service\WSClientBPD;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Carbon\Carbon;

class AdminOrderController extends AdminController
{
    protected $key = 'order';
    protected $entity = Order::class;
    protected $logger;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->logger = $logger;

        $this->authorizedRoles = [
            'ROLE_HELPDESK_USER', 'ROLE_HELPDESK_MERCHANT', 'ROLE_SUPER_ADMIN', 'ROLE_ACCOUNTING_1',
            'ROLE_ACCOUNTING_2',
        ];
    }

    protected function isAuthorizeToChangeB2gStatus(): bool
    {
        return $this->getUser()->getRole() === 'ROLE_ACCOUNTING_2';
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        $buttonEdit = $translator->trans('button.edit');
        $buttonShared = $translator->trans('label.shared_orders');
        $buttonRestore = $translator->trans('label.restore_order');
        $statuses = $this->getParameter('order_statuses');
        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'o.id']);
        $parameters['update_at_by'] = 'o.updatedAt';
        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }

        if ($parameters['role'] === 'buyer') {
            $parameters['roles'] = ['ROLE_USER', 'ROLE_USER_BUYER'];
            $parameters['version'] = 'v2';
        } elseif ($parameters['role'] === 'business') {
            $parameters['roles'] = ['ROLE_USER_BUSINESS'];
        } elseif ($parameters['role'] === 'government') {
            $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
        } else {
            $parameters['roles'] = ['ROLE_INVALID'];
        }

        $roleParam = $parameters['role'];

        // $parameters['role'] = null;

        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        /** @var OrderRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $orders = $results['data'];
        $number = $parameters['offset'];
        $data = [];
        /** @var ProductCategoryRepository $productCategoryRepository */
        $productCategoryRepository = $this->getRepository(ProductCategory::class);

        foreach ($orders as $order) {
            $number++;
            $orderId = (int)$order['o_id'];
            $transactionId = $order['o_sharedInvoice'];
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $orderId, 'role' => $roleParam]);
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $orderId, 'role' => $roleParam]);
            
            $status = $statuses[$order['o_status']] ?? 'label.pending';
            $status = $translator->trans($status);
            $buttons = "<a href=\"$urlView\" class=\"btn btn-sm btn-info\">$buttonView</a>";
            $buttonCancel = $order['o_status'] != 'cancel_request' ? $translator->trans('button.cancel') :$translator->trans('button.approve_cancel');


            if ($this->isAuthorizedToManage() || ($this->isAuthorizeToChangeB2gStatus() && $parameters['roles'] === ['ROLE_USER_GOVERNMENT'])) {
                $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-sm btn-primary\">$buttonEdit</a>";
                if ($order['o_status'] === 'cancel_request'){
                    $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-cancel\" title=\"$buttonCancel\" data-id=\"$orderId\"><i class=\"fa fa-ban\" aria-hidden=\"true\"></i> $buttonCancel</a>";
                }
            }

            $storeNames = [$order['s_name']];
            $invoices = [$order['o_invoice']];
            $tax_type = [!empty($order['o_taxType']) ? $this->getParameter('tax_payment_types')[$order['o_taxType']]['label'] : '-'];
            $dokuInvoices = [!empty($order['o_dokuInvoiceNumber']) ? $order['o_dokuInvoiceNumber'] : '-'];
            $ship_method = [!empty($order['o_shipped_method']) ? $this->getParameter('shipped_method_options')[$order['o_shipped_method']]: ''];
            $method_pay = [$this->getParameter('ppk_method_options')[!empty($order['o_ppk_payment_method']) ? $order['o_ppk_payment_method']:'uang_persediaan']];
            $orderStatuses = [$status];
            $orderCreated = [!empty($order['o_createdAt']) ? $order['o_createdAt']->format('d M Y H:i') : '-'];
            $orderUpdated = [!empty($order['o_updatedAt']) ? $order['o_updatedAt']->format('d M Y H:i') : '-'];
            $actionButtons = [$buttons];
            $products = $repository->getOrderProducts($orderId);
            $categories = [];
            $orderCategories = '';
            $orderRestoration = '';

            foreach ($products as $product) {
                $categories[] = (int)$product['p_category'];
            }

            if (isset($parameters['version']) && $parameters['version'] === 'v2') {
                $sharedId = $order['o_sharedId'];
                $related = $repository->getOrderRelatedBySharedId($sharedId, $orderId);

                foreach ($related as $item) {
                    $relatedId = (int)$item['o_id'];
                    $relatedUrlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $relatedId]);
                    $relatedUrlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $relatedId]);
                    $relatedStatus = $statuses[$item['o_status']] ?? 'label.pending';
                    $actionButton = "<a href=\"$relatedUrlView\" class=\"btn btn-sm btn-info\">$buttonView</a>";

                    if ($this->isAuthorizedToManage()) {
                        $actionButton .= "\n<a href=\"$relatedUrlEdit\" class=\"btn btn-sm btn-primary\">$buttonEdit</a>";
                    }

                    $storeNames[] = $item['s_name'];
                    $invoices[] = $item['o_invoice'];
                    $tax_type[] = !empty($item['o_taxType']) ? $this->getParameter('tax_payment_types')[$item['o_taxType']]['label'] : '-';
                    $dokuInvoices[] = !empty($item['o_dokuInvoiceNumber']) ? $item['o_dokuInvoiceNumber'] : '-';
                    $ship_method[] = !empty($order['o_shipped_method']) ? $this->getParameter('shipped_method_options')[$item['o_shipped_method']] : '';
                    $method_pay[] = $this->getParameter('ppk_method_options')[!empty($order['o_ppk_payment_method']) ? $order['o_ppk_payment_method']:'uang_persediaan'];
                    $orderStatuses[] = $translator->trans($relatedStatus);
                    $orderCreated[] = !empty($item['o_createdAt']) ? $item['o_createdAt']->format('d M Y H:i') : '-';
                    $orderUpdated[] = !empty($item['o_updatedAt']) ? $item['o_updatedAt']->format('d M Y H:i') : '-';
                    $actionButtons[] = $actionButton;
                    $products = $repository->getOrderProducts($relatedId);

                    foreach ($products as $product) {
                        $categories[] = (int)$product['p_category'];
                    }

                    if ($this->isAuthorizedToManage()) {
                        if ($item['o_status'] === 'cancel' && !empty($sharedId)) {
                            $orderRestoration = "&nbsp;<a href=\"javascript:void(0);\" class=\"btn btn-sm btn-danger btn-restore-order\" data-shared-id=\"$sharedId\">$buttonRestore</a>";
                        }
                    }
                }

                if ($this->isAuthorizedToManage()) {
                    if (empty($orderRestoration) && $order['o_status'] === 'cancel') {
                        $orderRestoration = "&nbsp;<a href=\"javascript:void(0);\" class=\"btn btn-sm btn-danger btn-restore-order\" data-shared-id=\"$sharedId\">$buttonRestore</a>";
                    }
                }

                if (!empty($sharedId)) {
                    $sharedUrlId = base64_encode(sprintf('bm-order:%s', $transactionId));
                    $urlShared = $this->generateUrl($this->getAppRoute('shared'), ['id' => $sharedUrlId]);
                    $sharedButton = "<a href=\"$urlShared\" class=\"btn btn-sm btn-success\">$buttonShared</a>";
                    $transactionId .= '<br><br>' . $sharedButton;

                    if (!empty($orderRestoration)) {
                        $transactionId .= $orderRestoration;
                    }
                }
            }

            if (count($categories) > 0) {
                $categories = array_unique($categories);
                /** @var ProductCategory[] $productCategories */
                $productCategories = $productCategoryRepository->getCategoryFromProduct($categories, true);
                $temp = [];

                foreach ($productCategories as $productCategory) {
                    $temp[] = $productCategory->getName();
                }

                $orderCategories = implode('; ', $temp);
            }

            $orderTotal = (float) $order['o_total'] + (float) $order['o_shippingPrice'];
            $nominal = "Rp. ".number_format($orderTotal, 0, ',', '.');

            $lastChangeStatus = '-';

            if (isset($order['o_statusChangeTime']) && !empty($order['o_statusChangeTime'])) {
                Carbon::setLocale('id');
                try {
                    $lastChangeStatus = Carbon::now()->subtract(Carbon::parse($order['o_statusChangeTime']))->diffInDays();
                    if ($lastChangeStatus < 1) {
                        $lastChangeStatus = Carbon::now()->subtract(Carbon::parse($order['o_statusChangeTime']))->diffForHumans();
                    } else {
                        $lastChangeStatus .= ' hari yang lalu';
                    }
                }catch (Exception $exception) {

                }
            }

            if ($order['o_taxType'] == 58) {
                $badge_kelengkapan = '';
                if (!empty($order['o_djpReportStatus'])) {
                    if ($order['o_djpReportStatus'] == 'djp_report_sent') {
                        $badge_color = '#198754';
                        $badge_text  = 'Berhasil terkirim';
                    } else {
                        $badge_color = '#dc3545';
                        $badge_text  = 'Gagal terkirim';
                    }
                } else {
                    $badge_color = '#ffc107';
                    $badge_text  = 'Belum terkirim';
                    $orderObject = $repository->find($order['o_id']);
                    if (
                    !empty($orderObject->getTaxDocumentNpwp()) && strlen(preg_replace('/[^0-9]/', '', $orderObject->getTaxDocumentNpwp())) > 14
                    && !empty($orderObject->getWorkUnitName()) 
                    && !empty($orderObject->getSeller()->getUser()->getNpwp())
                    && strlen(preg_replace('/[^0-9]/', '', $orderObject->getSeller()->getUser()->getNpwp())) > 14
                    
                    ) {
                        $badge_kelengkapan = '<span class="badge badge-sm" style="background-color: #198754">Sudah lengkap</span>';
                    } else {
                        $badge_kelengkapan = '<span class="badge badge-sm" style="background-color: #0d6efd">Belum lengkap</span>';
                    }
                }
                $use_djp = '<span class="badge badge-sm" style="background-color: '.$badge_color.'">'.$badge_text.'</span>'.$badge_kelengkapan;
            } else {
                $use_djp = '-';
            }

            $data[] = [
                $number,
                $transactionId,
                implode('<hr>', $storeNames),
                implode('<hr>', $invoices),
                implode('<hr>', $dokuInvoices),
                trim($order['u_firstName'].' '.$order['u_lastName']),
                // $method_pay,
                $orderCategories,
                $nominal,
                $ship_method,
                $tax_type,
                implode('<hr>', $orderStatuses),
                $lastChangeStatus,
                $use_djp,
                implode('<hr>', $orderCreated),
                implode('<hr>', $orderUpdated),
                implode('<hr>', $actionButtons),
            ];
        }
        // dump([
        //     'draw' => $parameters['draw'],
        //     'recordsTotal' => $total,
        //     'recordsFiltered' => $total,
        //     'data' => $data,
        // ]);
        return [
            'draw' => $parameters['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    public function deletePayment()
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $orderId = abs($request->request->get('oid', '0'));
        $paymentId = abs($request->request->get('pid', '0'));
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        /** @var Order $order */
        $order = $repository->find($orderId);
        $response = ['deleted' => false];

        if ($order instanceof Order) {
            /** @var OrderPaymentRepository $repository */
            $repository = $this->getRepository(OrderPayment::class);
            /** @var OrderPayment $orderPayment */
            $orderPayment = $repository->findOneBy([
                'id' => $paymentId,
                'order' => $order,
            ]);

            if ($orderPayment instanceof OrderPayment) {
                $this->appGenericEventDispatcher(new GenericEvent($orderPayment, [
                    'em' => $this->getEntityManager(),
                ]), 'app.delete_order_payment', new RemoveOrderPaymentEntityListener());

                /** @var User $buyer */
                $buyer = $order->getBuyer();
                $translator = $this->getTranslator();

                $notification = new Notification();
                $notification->setSellerId(0);
                $notification->setBuyerId($buyer->getId());
                $notification->setIsSentToSeller(true);
                $notification->setIsSentToBuyer(false);
                $notification->setTitle($translator->trans('notifications.order_status'));
                $notification->setContent($translator->trans('notifications.payment_deleted_text', ['%invoice%' => $order->getInvoice()]));

                $em = $this->getEntityManager();
                $em->persist($notification);
                $em->flush();

                $response['deleted'] = true;
            }
        }

        return $this->view('', $response, 'json');
    }

    protected function getDefaultData(): array
    {
        $data = parent::getDefaultData();
        $data['dt_script'] = 'v2';

        return $data;
    }

    protected function actReadData(int $id)
    {
        /** @var OrderRepository $repository */
        $repository = $this->getRepository($this->entity);
        $repositoryUser = $this->getRepository(User::class);
        $orderDetail = $repository->getOrderDetail($id);
        $storeRepository = $this->getRepository(Store::class);

        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);

        $reduceOrderByVoucher = [];

        if (!empty($orderDetail['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($orderDetail['o_sharedId'], $orderDetail['s_pkp']);
        }

        $orderDetail['reduce_by_order_id'] = $reduceOrderByVoucher;
        $orderDetail['order_change_log'] = $orderLog;
        $orderDetail['all_ppk_users'] = $repositoryUser->getAllPpkUsersWithoutLpse();
        $orderDetail['all_treasurer_users'] = $repositoryUser->getAllTreasurerUsersWithoutLpse();

        $this->checkAuthorizedAdminCabang($orderDetail['s_provinceId']);

        $shippedFiles = [];

        if($orderDetail){
            if($orderDetail['o_shippedFiles'] && $orderDetail['o_shipped_method'] == 'self_courier'){
                foreach ($orderDetail['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }
        // Get PPK stamp & signature
        $data_ppk = isset($orderDetail['o_ppkId']) ? $repositoryUser->find($orderDetail['o_ppkId']) : null;

        // Get data bendahara
        $data_treasurer = isset($orderDetail['o_treasurerId']) ? $repositoryUser->find($orderDetail['o_treasurerId']) : null;

        // Get PP stamp & signature
        $data_pp = isset($orderDetail['u_id']) ? $repositoryUser->find($orderDetail['u_id']) : null;

        // Get merchant stamp & signature
        $store = isset($orderDetail['s_ow_id']) ? $storeRepository->findOneBy(['user' => $orderDetail['s_ow_id']]) : null;

        $orderDetail['shippedFiles'] = $shippedFiles;

        // Handle PP data
        $orderDetail['pp_stamp'] = $data_pp ? $data_pp->getUserStamp() : null;
        $orderDetail['pp_signature'] = $data_pp ? $data_pp->getUserSignature() : null;

        // Handle PPK data
        $orderDetail['ppk_stamp'] = $data_ppk ? $data_ppk->getUserStamp() : null;
        $orderDetail['ppk_signature'] = $data_ppk ? $data_ppk->getUserSignature() : null;

        // Handle Treasurer data
        $orderDetail['treasurer_stamp'] = $data_treasurer ? $data_treasurer->getUserStamp() : null;
        $orderDetail['treasurer_signature'] = $data_treasurer ? $data_treasurer->getUserSignature() : null;

        // Handle Store data
        if ($store && $store->getUser()) {
            $orderDetail['seller_stamp'] = $store->getUser()->getUserStamp();
            $orderDetail['seller_signature'] = $store->getUser()->getUserSignature();
        } else {
            $orderDetail['seller_stamp'] = null;
            $orderDetail['seller_signature'] = null;
        }

        if (!empty($orderDetail['o_statusChangeTime'])) {
            Carbon::setLocale('id');
            $statusLastChanged = Carbon::now()->subtract(Carbon::parse($orderDetail['o_statusChangeTime']))->diffForHumans();
            $orderDetail['o_statusChangeTime'] = $statusLastChanged;
        }

        return $orderDetail;
    }

    protected function actEditData(int $id)
    {
        if (!$this->isAuthorizedToManage() && !$this->isAuthorizeToChangeB2gStatus()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository($this->entity);
        $repositoryUser = $this->getRepository(User::class);
        $storeRepository = $this->getRepository(Store::class);

        $orderDetail = $repository->getOrderDetail($id);
        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);

        $reduceOrderByVoucher = [];     

        if (!empty($orderDetail['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($orderDetail['o_sharedId'], $orderDetail['s_pkp']);
        }

        if (!empty($orderDetail['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($orderDetail['o_sharedId'], $orderDetail['s_pkp']);
        }

        $orderObject = $repository->find($orderDetail['o_id']);
        $djp_error = [];
        if (empty($orderObject->getTaxDocumentNpwp()) ) {
            $djp_error[] = 'Mohon lengkapi Tax Document NPWP';
        } else if (strlen(preg_replace('/[^0-9]/', '', $orderObject->getTaxDocumentNpwp())) <= 14) {
            $djp_error[] = 'Tax Document NPWP harus diatas 14 digit';
        }

        if (empty($orderObject->getWorkUnitName())) {
            $djp_error[] = 'Mohon lengkapi Work Unit Name';
        }

        if (empty($orderObject->getSeller()->getUser()->getNpwp())) {
            $djp_error[] = 'Mohon lengkapi NPWP pada Seller';
        } else if (strlen(preg_replace('/[^0-9]/', '', $orderObject->getSeller()->getUser()->getNpwp())) <= 14) {
            $djp_error[] = 'NPWP pada Seller harus diatas 14 digit';
        } 
                    
        $orderDetail['reduce_by_order_id'] = $reduceOrderByVoucher;
        $orderDetail['djp_error'] = $djp_error;
        $orderDetail['order_change_log'] = $orderLog;
        $orderDetail['is_accounting2'] = $this->isAuthorizeToChangeB2gStatus();
        $orderDetail['bank_list'] = $this->getRepository(Bank::class)->findBy(['is_active' => true]);
        $orderDetail['all_ppk_users'] = $repositoryUser->getAllPpkUsersWithoutLpse();
        $orderDetail['all_treasurer_users'] = $repositoryUser->getAllTreasurerUsersWithoutLpse();
        $this->checkAuthorizedAdminCabang($orderDetail['s_provinceId']);


        $shippedFiles = [];

        if($orderDetail){
            if($orderDetail['o_shippedFiles'] && $orderDetail['o_shipped_method'] == 'self_courier'){
                foreach ($orderDetail['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }

        // Get PPK stamp & signature
        $data_ppk = isset($orderDetail['o_ppkId']) ? $repositoryUser->find($orderDetail['o_ppkId']) : null;

        // Get data bendahara
        $data_treasurer = isset($orderDetail['o_treasurerId']) ? $repositoryUser->find($orderDetail['o_treasurerId']) : null;

        // Get PP stamp & signature
        $data_pp = isset($orderDetail['u_id']) ? $repositoryUser->find($orderDetail['u_id']) : null;

        // Get merchant stamp & signature
        $store = isset($orderDetail['s_ow_id']) ? $storeRepository->findOneBy(['user' => $orderDetail['s_ow_id']]) : null;

        $orderDetail['shippedFiles'] = $shippedFiles;

        // Handle PP data
        $orderDetail['pp_stamp'] = $data_pp ? $data_pp->getUserStamp() : null;
        $orderDetail['pp_signature'] = $data_pp ? $data_pp->getUserSignature() : null;

        // Handle PPK data
        $orderDetail['ppk_stamp'] = $data_ppk ? $data_ppk->getUserStamp() : null;
        $orderDetail['ppk_signature'] = $data_ppk ? $data_ppk->getUserSignature() : null;

        // Handle Treasurer data
        $orderDetail['treasurer_stamp'] = $data_treasurer ? $data_treasurer->getUserStamp() : null;
        $orderDetail['treasurer_signature'] = $data_treasurer ? $data_treasurer->getUserSignature() : null;

        // Handle Store data
        if ($store && $store->getUser()) {
            $orderDetail['seller_stamp'] = $store->getUser()->getUserStamp();
            $orderDetail['seller_signature'] = $store->getUser()->getUserSignature();
        } else {
            $orderDetail['seller_stamp'] = null;
            $orderDetail['seller_signature'] = null;
        }


        if (!empty($orderDetail['o_statusChangeTime'])) {
            Carbon::setLocale('id');
            $statusLastChanged = Carbon::now()->subtract(Carbon::parse($orderDetail['o_statusChangeTime']))->diffForHumans();
            $orderDetail['o_statusChangeTime'] = $statusLastChanged;
        }

        return $orderDetail;
    }

    protected function actUpdateData(Request $request, $id): string
    {
        if (!$this->isAuthorizedToManage() && !$this->isAuthorizeToChangeB2gStatus()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }
        $formData = $request->request->all();
        $roleParam = $request->query->get('role', null);
        $status = $formData['status'] ?? 'invalid';
        /** @var Order $order */
        $order = $this->getRepository($this->entity)->find($id);
        $repositoryUser = $this->getRepository(User::class);

        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id, 'role' => $roleParam]);
        $translator = $this->getTranslator();
        $errors = [];
        if ($status == 'cancel') {
            if (empty($formData['cancel_reason'])) {
                $errors['cancel_reason'] = $translator->trans('global.not_empty', [], 'validators');
            }

            if (empty($order->getCancelFile()) &&  empty($request->files->get('cancel_file'))) {
                $errors['cancel_file'] = $translator->trans('global.not_empty', [], 'validators');
            }
            if (count($errors) > 0) {
                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('form_data', $formData);
                $flashBag->set('warning', $this->getTranslator()->trans('Cancel File atau Cancel Reason tidak boleh kosong jika ingin mengubah status menjadi cancel'));
                $flashBag->set('errors', $errors);
                return $redirect;
            }
        }


        if ($this->isAuthorizeToChangeB2gStatus() && $status !== 'paid') {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $this->checkAuthorizedAdminCabang($order->getSeller()->getId());

        if ($status !== 'invalid' && $order instanceof Order) {
            $em = $this->getEntityManager();

            $prefixPath = $this->constructUploadPath();

            if (!empty($order->getInvoiceFile())) {
                $prefixPath = $this->constructUploadPath($order->getInvoiceFile());
            }elseif (!empty($order->getBastFile())) {
                $prefixPath = $this->constructUploadPath($order->getBastFile());
            }elseif (!empty($order->getReceiptFile())) {
                $prefixPath = $this->constructUploadPath($order->getReceiptFile());
            }

            $uploader = $this->get(FileUploader::class);

            $uploader->setTargetDirectory($prefixPath);

            if ($status === 'cancel') {
                // Increment product quantity
                /** @var OrderProduct[] $orderProducts */
                $orderProducts = $order->getOrderProducts();

                foreach ($orderProducts as $orderProduct) {
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();
                    $stock = $product->getQuantity();
                    $newStock = $stock + $orderProduct->getQuantity();

                    $product->setQuantity($newStock < 1 ? 0 : $newStock);

                    $em->persist($product);
                }

                $em->flush();
            }

            if (
                (!$order->getIsB2gTransaction() && $status == 'received')
                ||
                ($order->getIsB2gTransaction() && $status == 'paid')
            ) {
                $orderPayment = $order->getPayment();
                if ($orderPayment != null) {
                    $orderPayment->setDate(new DateTime());
                    $em->persist($orderPayment);
                    $em->flush();
                }
                $this->setDisbursementProductFee($em, $order);
                // if (!empty($order->getTreasurerName()) && !empty($order->getTreasurerEmail())) {
                //     /**
                //      * Send email to bendahara
                //      */
                //     try {
                //         /** @var BaseMail $mailToSeller */
                //         $mailToSeller = $this->get(BaseMail::class);
                //         $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Paid');
                //         $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                //         $mailToSeller->setMailRecipient($order->getTreasurerEmail());
                //         $mailToSeller->setMailData([
                //             'name' => $order->getTreasurerName(),
                //             'invoice' => $order->getInvoice(),
                //             'pp' => $order->getName(),
                //             'ppk_name' => $order->getPpkName(),
                //             'satker' => !empty($order->getWorkUnitName()) ? $order->getWorkUnitName() : $order->getBuyer()->getLkppWorkUnit(),
                //             'klpd' => !empty($order->getInstitutionName()) ? $order->getInstitutionName() : $order->getBuyer()->getLkppKLDI(),
                //             'merchant' => $order->getSeller()->getName(),
                //             'payment_method' => $order->getPpkPaymentMethod(),
                //             'status' => 'paid',
                //             'type' => 'treasurer',
                //             'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                //         ]);
                //         $mailToSeller->send();

                //     } catch (\Throwable $exception) {

                //     }
                // }

                // if (!empty($order->getUnitName()) && !empty($order->getUnitEmail())) {
                //     /**
                //      * Send email to pic
                //      */
                //     try {
                //         /** @var BaseMail $mailToSeller */
                //         $mailToSeller = $this->get(BaseMail::class);
                //         $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Paid');
                //         $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                //         $mailToSeller->setMailRecipient($order->getUnitEmail());
                //         $mailToSeller->setMailData([
                //             'name' => $order->getUnitName(),
                //             'invoice' => $order->getInvoice(),
                //             'pp' => $order->getName(),
                //             'ppk_name' => $order->getPpkName(),
                //             'satker' => !empty($order->getWorkUnitName()) ? $order->getWorkUnitName() : $order->getBuyer()->getLkppWorkUnit(),
                //             'klpd' => !empty($order->getInstitutionName()) ? $order->getInstitutionName() : $order->getBuyer()->getLkppKLDI(),
                //             'merchant' => $order->getSeller()->getName(),
                //             'payment_method' => $order->getPpkPaymentMethod(),
                //             'status' => 'paid',
                //             'type' => 'pic',
                //             'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                //         ]);
                //         $mailToSeller->send();

                //     } catch (\Throwable $exception) {

                //     }
                // }

                // if (!empty($order->getPpkName()) && !empty($order->getPpkEmail())) {
                //     /**
                //      * Send email to ppk
                //      */
                //     try {
                //         /** @var BaseMail $mailToSeller */
                //         $mailToSeller = $this->get(BaseMail::class);
                //         $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Paid');
                //         $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                //         $mailToSeller->setMailRecipient($order->getPpkEmail());
                //         $mailToSeller->setMailData([
                //             'name' => $order->getPpkName(),
                //             'invoice' => $order->getInvoice(),
                //             'pp' => $order->getName(),
                //             'ppk_name' => $order->getPpkName(),
                //             'satker' => !empty($order->getWorkUnitName()) ? $order->getWorkUnitName() : $order->getBuyer()->getLkppWorkUnit(),
                //             'klpd' => !empty($order->getInstitutionName()) ? $order->getInstitutionName() : $order->getBuyer()->getLkppKLDI(),
                //             'merchant' => $order->getSeller()->getName(),
                //             'payment_method' => $order->getPpkPaymentMethod(),
                //             'status' => 'paid',
                //             'type' => 'ppk',
                //             'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                //         ]);
                //         $mailToSeller->send();

                //     } catch (\Throwable $exception) {

                //     }
                // }
            }

            //--- TYPE #1 (send to all stores that share the same shared_id)
            //if ($status === 'confirmed' && $order->getStatus() === 'paid' && !empty($order->getSharedId())) {
            //    /** @var Order[] $orders */
            //    $orders = $this->getRepository($this->entity)->findBy(['sharedId' => $order->getSharedId()]);
            //
            //    foreach ($orders as $transaction) {
            //        $this->sendConfirmationEmailToStoreOwner($transaction);
            //    }
            //}

            //--- TYPE #2 (send only to store that has this order)
            if ($status === 'confirmed' && $order->getStatus() === 'paid') {
                $this->sendConfirmationAndNotificationEmail($order);
            }

            /**
             * Khusus order lkpp, ketika 1 order paid maka kirim confirmation transaction ke toko daring berdasarkan shared_invoice
             * @TODO Kalau cancel maka kirim konfirmasi pembatalan ke toko_daring dengan set konfirmasi_ppmse ke 0
             */

            $buyer = $order->getBuyer();
            if (($status === 'paid' || $status === 'cancel') &&
                $order->getIsB2gTransaction() &&
                $order->getTokoDaringReportStatus() === 'sent' &&
                !empty($buyer->getLkppLpseId())
            ) {

                $tokoDaringRepository = $this->get(TokoDaringService::class);
                $res = $tokoDaringRepository->sendConfirmationTransactionToTokoDaring($order, $status);

                if (!$res['error']) {
                    $orderRepository = $this->getRepository(Order::class);
                    $orders = $orderRepository->findBy(['sharedInvoice' => $order->getSharedInvoice()]);

                    foreach ($orders as $order) {
                        $order->setTokoDaringReportStatus('done');
                        $em->persist($order);
                        $em->flush();
                    }
                }
            }

            //  if ($status === 'paid' &&
            //      (empty($order->getDjpReportStatus()) || $order->getDjpReportStatus() !== 'djp_report_sent') &&
            //      $order->getTaxType() === 58
            //  ) {
            //      $djpService = $this->get(DJPService::class);
            //      $result_barang = $djpService->postTransations($order);

            //      $djpStatus = 'djp_report_failed';

            //      if ($result_barang['error'] === false) {
            //          $djpStatus = 'djp_report_sent';
            //          $order->setDjpResponseOrder(json_encode($result_barang));
            //      }


            //      $order->setDjpReportStatus($djpStatus);
            //  }

            if ($status !== $order->getStatus()) {
                /** @var Store $store */
                $store = $order->getSeller();
                /** @var User $seller */
                $seller = $store->getUser();
                /** @var User $buyer */
                $buyer = $order->getBuyer();

                $notification = new Notification();
                $notification->setSellerId($seller->getId());
                $notification->setBuyerId($buyer->getId());
                $notification->setIsSentToSeller(false);
                $notification->setIsSentToBuyer(false);
                $notification->setTitle($translator->trans('notifications.order_status'));
                $notification->setContent($translator->trans('notifications.order_status_text', ['%invoice%' => $order->getInvoice(), '%status%' => $status]));

                $em->persist($notification);

                $order->setStatusChangeTime();
            }

            $previousOrderValues = clone $order;

            $order->setStatus($status);
            if ($status == 'cancel') {
                if (!empty($request->files->get('cancel_file'))) {
                    $filecancel = $uploader->upload($request->files->get('cancel_file'), false);
                    $order->setCancelFile($filecancel);
                }
                $order->setCancelReason(filter_var($formData['cancel_reason'], FILTER_SANITIZE_STRING));
            }
            $order->setNote(filter_var($formData['note'], FILTER_SANITIZE_STRING));
            $order->setJobPackageName(filter_var($formData['job_package_name'], FILTER_SANITIZE_STRING));
            $order->setFiscalYear(filter_var($formData['fiscal_year'], FILTER_SANITIZE_STRING));
            $order->setRupCode(filter_var($formData['rup'], FILTER_SANITIZE_STRING));
            $order->setSourceOfFund(filter_var($formData['source_of_fund'], FILTER_SANITIZE_STRING));
            $order->setBudgetCeiling(filter_var(str_replace(".", "", $formData['budget_ceiling'])), FILTER_SANITIZE_STRING);
            $order->setWorkUnitName(filter_var($formData['work_unit_name'], FILTER_SANITIZE_STRING));
            $order->setInstitutionName(filter_var($formData['institution_name'], FILTER_SANITIZE_STRING));
            $order->setBudgetAccount(filter_var($formData['budget_account'], FILTER_SANITIZE_STRING));
            // dd($formData);
            if (!empty($formData['ppk_id'])) {
                $data_ppk = $repositoryUser->find($formData['ppk_id']);
                $order->setPpkId($formData['ppk_id']);
                $order->setPpkName(filter_var($data_ppk->getFirstName().' '.$data_ppk->getLastName(), FILTER_SANITIZE_STRING));
                $order->setPpkNip(filter_var($data_ppk->getNip(), FILTER_SANITIZE_STRING));
                $order->setPpkTelp(filter_var($data_ppk->getPhoneNumber(), FILTER_SANITIZE_STRING));
                $order->setPpkEmail(filter_var($data_ppk->getEmail(), FILTER_SANITIZE_STRING));
                $order->setPpkType(filter_var($data_ppk->getSubRoleTypeAccount(), FILTER_SANITIZE_STRING));
            }

            if (!empty($formData['treasurer_id'])) {
                $data_treasurer = $repositoryUser->find($formData['treasurer_id']);
                $order->setTreasurerId($formData['treasurer_id']);
                $order->setTreasurerName(filter_var($data_treasurer->getFirstName().' '.$data_treasurer->getLastName(), FILTER_SANITIZE_STRING));
                $order->setTreasurerNip(filter_var($data_treasurer->getNip(), FILTER_SANITIZE_STRING));
                $order->setTreasurerTelp(filter_var($data_treasurer->getPhoneNumber(), FILTER_SANITIZE_STRING));
                $order->setTreasurerEmail(filter_var($data_treasurer->getEmail(), FILTER_SANITIZE_STRING));
                $order->setTreasurerType(filter_var($data_treasurer->getSubRoleTypeAccount(), FILTER_SANITIZE_STRING));
            }

            // $order->setPpkPaymentMethod(filter_var($formData['payment_method'], FILTER_SANITIZE_STRING));
            $order->setUnitName(filter_var($formData['pic_name'], FILTER_SANITIZE_STRING));
            $order->setUnitPic(filter_var($formData['pic_telp'], FILTER_SANITIZE_STRING));
            $order->setUnitEmail(filter_var($formData['pic_email'], FILTER_SANITIZE_STRING));
            $order->setUnitAddress(filter_var($formData['pic_unit'], FILTER_SANITIZE_STRING));
            $order->setUnitTelp(filter_var($formData['pic_address'], FILTER_SANITIZE_STRING));
            $order->setTaxDocumentNpwp(filter_var($formData['tax_document_npwp'], FILTER_SANITIZE_STRING));


            if (isset($formData['tax_types']) && !empty($formData['tax_types'])) {

                $order->setTaxType($formData['tax_types']);
                

                if ($formData['tax_types'] == '59') {
                    if ($order->getTotal() + $order->getShippingPrice() <= 2220000) {
                        $textFaktur = ', Mohon upload faktur pajak dengan kode 010';
                    }
                    if ($formData['pph_choose'] == 'lainnya') {
                        $isOtherPph = true;
                        $otherPphName = $formData['other_pph_name'];
                        $pph = $formData['other_pph_persentase'];
                    } else {
                        $isOtherPph = false;
                        $otherPphName = '';
                        $pph = $formData['pph_choose'];
                    }

                    if ($formData['ppn_choose'] == 'lainnya') {
                        $isOtherPpn = true;
                        $otherPpnName = $formData['other_ppn_name'];
                        $ppn = $formData['other_ppn_persentase'];
                    } else {
                        $isOtherPpn = false;
                        $otherPpnName = '';
                        $ppn = $formData['ppn_choose'];
                    }
                    $order->setIsOtherPph($isOtherPph);
                    $order->setOtherPphName($otherPphName);

                    $order->setIsOtherPpn($isOtherPpn);
                    $order->setOtherPpnName($otherPpnName);

                    $order->setTreasurerPph($pph);
                    $order->setTreasurerPpn($ppn);

                    $order->setTreasurerPphNominal($formData['pph_nominal']);
                    $order->setTreasurerPpnNominal($formData['ppn_nominal']);
                    $order->setPpkPaymentMethod('pembayaran_langsung');
                } else {
                    $order->setIsOtherPph('');
                    $order->setOtherPphName('');
                    $order->setIsOtherPpn('');
                    $order->setOtherPpnName('');
                    $order->setTreasurerPph('');
                    $order->setTreasurerPpn('');
                    $order->setTreasurerPphNominal('');
                    $order->setTreasurerPpnNominal('');
                    $order->setPpkPaymentMethod('uang_persediaan');
                }
            }

            if (isset($formData['doku_invoice'])) {
                $order->setDokuInvoiceNumber(filter_var($formData['doku_invoice'], FILTER_SANITIZE_STRING));
            }
            if ($order->getShippedMethod() == 'self_courier') {
                $order->setSelfCourierName(filter_var($formData['nama_penerima'], FILTER_SANITIZE_STRING));
                $order->setSelfCourierPosition(filter_var($formData['posisi_jabatan'], FILTER_SANITIZE_STRING));
                $order->setSelfCourierTelp(filter_var($formData['no_hp_penerima'], FILTER_SANITIZE_STRING));
            }

            $shippedFile = $order->getOrderShippedFiles();
            for ($i=0; $i < 3; $i++) { 
                if (!empty($request->files->get('o_shippedFiles_'.$i))) {
                    $fileShipped = $uploader->upload($request->files->get('o_shippedFiles_'.$i), true);
                    if (isset($shippedFile[$i]) && !empty($shippedFile[$i])) {
                        $orderShippedFile = $shippedFile[$i];
                        $orderShippedFile->setFilePath($fileShipped);
                    } else {
                        $orderShippedFile = new OrderShippedFile();
                        $orderShippedFile->setOrder($order);
                        $orderShippedFile->setFilePath($fileShipped);
                    }
                    $em->persist($orderShippedFile);
                    $em->flush();
                }
            }
            


            if (!empty($request->files->get('reupload_bast'))) {
                $fileBast = $uploader->upload($request->files->get('reupload_bast'), false);
                $order->setBastFile($fileBast);
            }

            if (!empty($request->files->get('reupload_tax_invoice'))) {
                $fileTaxInvoice = $uploader->upload($request->files->get('reupload_tax_invoice'), false);
                $order->setTaxInvoiceFile($fileTaxInvoice);
            }

            if (!empty($request->files->get('reupload_invoice'))) {
                $fileInvoice = $uploader->upload($request->files->get('reupload_invoice'), false);
                $order->setInvoiceFile($fileInvoice);
            }

            if (!empty($request->files->get('reupload_receipt'))) {
                $fileReceipt = $uploader->upload($request->files->get('reupload_receipt'), false);
                $order->setReceiptFile($fileReceipt);
            }

            if (!empty($request->files->get('reupload_wo'))) {
                $fileWo = $uploader->upload($request->files->get('reupload_wo'), false);
                $order->setWorkOrderLetterFile($fileWo);
            }

            if (!empty($request->files->get('reupload_delevery'))) {
                $fileDelevery = $uploader->upload($request->files->get('reupload_delevery'), false);
                $order->setDeliveryPaperFile($fileDelevery);
            }

            if (!empty($request->files->get('reupload_withholding'))) {
                $fileWithholding = $uploader->upload($request->files->get('reupload_withholding'), false);
                $order->setWithholdingTaxSlipFile($fileWithholding);
            }

            if (!empty($request->files->get('reupload_payment'))) {

                $prefixPath = 'payment/';
                $prefixPath .= (new DateTime())->format('Y-m-d');

                $uploader->setTargetDirectory($prefixPath);

                $paymentFile = 'uploads/'.$uploader->upload($request->files->get('reupload_payment'), false);

                if ($order->getPayment() != null) {
                    $orderPayment = $order->getPayment();
                    $orderPayment->setAttachment($paymentFile);
                    $orderPayment->setDate(new DateTime());
                } else {
                    $user = $order->getBuyer();

                    $orderPayment = new OrderPayment();
                    $orderPayment->setOrder($order);
                    $orderPayment->setType('bank_transfer');
                    $orderPayment->setName(trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName())));
                    $orderPayment->setEmail($user->getEmailCanonical());
                    $orderPayment->setDate(new DateTime());
                    $orderPayment->setAttachment($paymentFile);
                    $orderPayment->setMessage(filter_var($formData['message'], FILTER_SANITIZE_STRING));
                    $orderPayment->setBankName(filter_var($formData['bank_name'], FILTER_SANITIZE_STRING));
                    $orderPayment->setInvoice($order->getInvoice());
                    $orderPayment->setNominal($order->getTotal() + $order->getShippingPrice());
                }
                $em->persist($orderPayment);
                $em->flush();

                $em->persist($orderPayment);
                $em->flush();

            }


            $em->persist($order);
            $em->flush();

            if ($status === 'processed' && $order->getSeller()->getIsUsedErzap() == true && (empty($order->getErzapOrderReport()) || $order->getErzapOrderReport() === 'failed')) {
                $this->erzapOrderWebhook($order->getSharedId(), $em);
            }

            $this->logOrder($em, $previousOrderValues, $order, $this->getUser());

            // if ($formData['btn_action'] === 'set_resolved') {
            //     /** @var OrderComplaint $orderComplaint */
            //     $orderComplaint = $order->getComplaint();
            //     $orderComplaint->setIsResolved(true);
            //     $orderComplaint->setResolvedAt();

            //     $em->persist($orderComplaint);
            //     $em->flush();
            // }

            $this->addFlash(
                'success',
                $translator->trans('message.success.order_updated', ['%name%' => $order->getName()])
            );

            if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute(), ['role' => $roleParam]);
            }
        }

        return $redirect;
    }

    protected function manipulateDataPackage(): void
    {
        $this->dataPackage->setAbleToCreate(false);
        $this->dataPackage->setAbleToUpdate(false);
        $this->dataPackage->setAbleToExport(true);
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $tax_payment = $this->getParameter('tax_payment_types');
        $tax_type['belum_pilih'] = 'Belum memilih Tipe Pajak';
        foreach ($tax_payment as $key => $value) {
            $tax_type[$key] = $value['label'];
        }
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'role' => [
                'type' => 'hidden',
                'selections' => $this->getParameter('user_roles'),
                'value' => htmlspecialchars($request->query->get('role', 'buyer')),
            ],
            'store' => [
                'type' => 'select',
                'selections' => [],
            ],
            'status' => [
                'type' => 'select',
                'selections' => $this->getParameter('order_statuses'),
                'multiple' => true,
            ],
            'updated_at' => [
                'type' => 'checkbox',
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
            'year' => [
                'type' => 'text',
            ],
            'status_last_changed' => [
                'type' => 'select',
                'selections' => $this->getParameter('status_change_filter_option'),
            ],
            // 'ppk_payment_method' => [
            //     'type' => 'select',
            //     'selections' => $this->getParameter('ppk_method_options'),
            // ],
            'tax_type' => [
                'type' => 'select',
                'selections' => $tax_type,
            ],
            'jump_to_page' => [
                'type' => 'text',
            ],
            'status_djp' => [
                'type' => 'select',
                'selections' => $this->getParameter('djp_send_status'),
            ],

        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['no', 'transaction_id', 'store_name', 'invoice', 'doku_invoice', 'buyer_name', 'category', 'nominal' , 'shipped_method', 'tax_type', 'status', 'status_last_changed', 'status_djp', 'created', 'updated', 'actions']);
    }

    protected function prepareDataTableButton(): void
    {
        $this->dataTable->setButtons([]);
    }

    public function cancel($id)
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $this->isAjaxRequest('POST');

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        /** @var Order $order */
        $order = $repository->find((int)$id);
        $response = ['status' => false];
        $sharedId = $order->getSharedId();
        $related = $repository->getOrderRelatedBySharedId($sharedId, $id);

        $this->checkAuthorizedAdminCabang($order->getSeller()->getId());

        $this->cancelOrder($order);

        foreach ($related as $item) {
            $order_id = $item['o_id'];
            $order_related = $repository->find((int)$order_id);
            $this->cancelOrder($order_related);
        }

        $this->addFlash(
            'success',
            $this->getTranslator()->trans('message.success.order_cancelled')
        );

        $response['status'] = true;

        return $this->view('', $response, 'json');
    }

    public function cancelOrder($order)
    {
        /** @var OrderRepository $repository */
        /** @var Order $order */
        if (($order instanceof Order) && ($order->getStatus() == 'cancel_request') ) {
            $em = $this->getEntityManager();
            // Increment product quantity
            /** @var OrderProduct[] $orderProducts */
            $orderProducts = $order->getOrderProducts();

            foreach ($orderProducts as $orderProduct) {
                /** @var Product $product */
                $product = $orderProduct->getProduct();
                $newStock = $product->getQuantity() + $orderProduct->getQuantity();

                $product->setQuantity($newStock < 1 ? 0 : $newStock);

                $em->persist($product);
            }

            $previousOrderValues = clone $order;

            $order->setStatus('cancel');
            $this->logOrder($em, $previousOrderValues, $order, $this->getUser());
            $order->setStatusChangeTime();


            $em->flush();

        }
    }

    private function sendConfirmationEmailToStoreOwner(Order $order): void
    {
        /** @var Store $seller */
        $seller = $order->getSeller();
        /** @var User $owner */
        $owner = $seller->getUser();
        /** @var BaseMail $mailToSeller */
        $mailToSeller = $this->get(BaseMail::class);
        $mailToSeller->setMailSubject($this->getTranslator()->trans('message.info.order_received'));
        $mailToSeller->setMailTemplate('@__main__/email/order_notification.html.twig');
        $mailToSeller->setMailRecipient($owner->getEmailCanonical());
        $mailToSeller->setMailData([
            'name' => $owner->getFirstName(),
            'invoice' => $order->getInvoice(),
            'recipient_type' => 'seller',
            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToSeller->send();
    }

    private function sendConfirmationAndNotificationEmail(Order $order): void
    {
        /** @var BaseMail $baseMail */
        $baseMail = $this->get(BaseMail::class);
        $baseMail->setMailSubject($this->getTranslator()->trans('message.info.order_received'));
        $baseMail->setMailTemplate('@__main__/email/order_notification.html.twig');

        //--- Send email notification to seller
        /** @var Store $seller */
        $seller = $order->getSeller();
        /** @var User $owner */
        $owner = $seller->getUser();
        $mailToSeller = clone $baseMail;
        $mailToSeller->setMailRecipient($owner->getEmailCanonical());
        $mailToSeller->setMailData([
            'name' => $owner->getFirstName(),
            'invoice' => $order->getInvoice(),
            'recipient_type' => 'seller',
            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToSeller->send();
        //--- Send email notification to seller

        //--- Send email notification to buyer
        /** @var User $buyer */
        $buyer = $order->getBuyer();
        $mailToBuyer = clone $baseMail;
        $mailToBuyer->setMailRecipient($buyer->getEmailCanonical());
        $mailToBuyer->setMailData([
            'name' => $buyer->getFirstName(),
            'invoice' => $order->getInvoice(),
            'recipient_type' => 'buyer',
            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToBuyer->send();
        //--- Send email notification to buyer

        //--- Send email notification to admin
        $mailToAdmin = clone $baseMail;
        $mailToAdmin->setToAdmin();
        $mailToAdmin->setMailData([
            'name' => 'Admin',
            'invoice' => $order->getInvoice(),
            'recipient_type' => 'admin',
            'link' => $this->generateUrl('admin_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToAdmin->send();
        //--- Send email notification to admin
    }

    public function resolved(int $complaint_id): RedirectResponse
    {
        $repository = $this->getRepository(OrderComplaint::class);
        $complain   = $repository->find($complaint_id);
        $em = $this->getEntityManager();
        $complain->setIsResolved(true);
        $complain->setResolvedAt(new DateTime('now'));
        $em->persist($complain);
        $em->flush();
        $order      = $complain->getOrder();
        return $this->redirect($this->generateUrl($this->getAppRoute('edit'), ['id' => $order->getId()]));
    }

    public function resend(int $id, string $type): RedirectResponse
    {
        $valid = ['bast'];

        if (!in_array($type, $valid)) {
            throw new NotFoundHttpException('Invalid resend type provided!');
        }

        /** @var Order $order */
        $order = $this->getRepository($this->entity)->find($id);
        /** @var User $buyer */
        $buyer = $order->getBuyer();
        $translator = $this->getTranslator();

        if ($type === 'bast') {
            $order->setBastFile(null);
        }

        $notification = new Notification();
        $notification->setSellerId(0);
        $notification->setBuyerId($buyer->getId());
        $notification->setIsSentToSeller(true);
        $notification->setIsSentToBuyer(false);
        $notification->setTitle($translator->trans('notifications.order_bast'));
        $notification->setContent($translator->trans('notifications.order_bast_text', ['%invoice%' => $order->getInvoice()]));

        $em = $this->getEntityManager();
        $em->persist($order);
        $em->persist($notification);
        $em->flush();

        $this->addFlash('success', $translator->trans('message.success.order_updated'));

        return $this->redirect($this->generateUrl($this->getAppRoute('edit'), ['id' => $id]));
    }

    public function shared($id)
    {
        $parts = explode(':', base64_decode($id));
        $sharedInvoice = $parts[1] ?? 'n/a';

        if ($sharedInvoice === 'n/a') {
            throw new NotFoundHttpException('Invalid order shared id!');
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $orders = $repository->getOrderDetailBySharedInvoice($sharedInvoice, ['buyer' => 'n/a']);
        $sharedId = $orders[0]['o_sharedId'];
        $qris = null;
        $va = null;

        if ($orders[0]['u_role'] === 'ROLE_USER_GOVERNMENT') {
            return $this->redirectToRoute($this->getAppRoute());
        }

        if (isset($orders[0]['o_sharedInvoice']) && !empty($orders[0]['o_sharedInvoice'])) {
            $qris = $repository->getQRISPaymentDetail($orders[0]['o_sharedInvoice']);
            $va = $repository->getVAPaymentDetail($orders[0]['o_sharedInvoice']);
        }

        return $this->view('@__main__/admin/order/shared.html.twig', [
            'shared_id' => $sharedId,
            'orders' => $orders,
            'qris' => $qris,
            'va' => $va,
        ]);
    }

    public function setSharedInvoice(): RedirectResponse
    {
        $this->deniedAccess();

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);

        $this->appGenericEventDispatcher(new GenericEvent($repository, [
            'em' => $this->getEntityManager(),
            'run_type' => 'batch',
        ]), 'app.set_order_shared_invoice', new SetOrderSharedInvoiceEntityListener());

        return $this->redirectToRoute($this->getAppRoute());
    }

    public function restore()
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $sharedId = $request->request->get('sid', 'invalid');
        $response = ['restored' => false];
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        /** @var Order[] $orders */
        $orders = $repository->findBy([
            'sharedId' => $sharedId,
            'status' => 'cancel',
        ]);

        if (count($orders) > 0) {
            $em = $this->getEntityManager();

            foreach ($orders as $order) {
                $order->setStatus('pending');
                $em->persist($order);
            }

            $em->flush();
            $this->addFlash('success', $this->getTranslator()->trans('message.success.order_restored'));

            $response['restored'] = true;
        }

        return $this->view('', $response, 'json');
    }

    public function paymentCheck(LoggerInterface $logger, $channel): RedirectResponse
    {
        $request = $this->getRequest();
        $sharedId = $request->query->get('id', '');

        if ($channel === 'virtual-account' && !empty($sharedId)) {
            /** @var OrderRepository $orderRepository */
            $orderRepository = $this->getRepository(Order::class);
            /** @var Order[] $orders */
            $orders = $orderRepository->findBy(['sharedId' => $sharedId]);
            $invoice = count($orders) > 0 ? $orders[0]->getSharedInvoice() : 'invalid-invoice';

            /** @var VirtualAccountRepository $vaRepository */
            $vaRepository = $this->getRepository(VirtualAccount::class);
            /** @var VirtualAccount $va */
            $va = $vaRepository->findOneBy([
                'invoice' => $invoice,
                'paidStatus' => '0',
            ]);

            if ($va instanceof VirtualAccount) {
                $buyerName = count($orders) > 0 ? $orders[0]->getName() : '';
                $buyerEmail = count($orders) > 0 ? $orders[0]->getEmail() : '';
                $translator = $this->getTranslator();
                $tempInvoices = [];

                $em = $this->getEntityManager();
                $wsClient = new WSClientBPD();

                try {
                    $response = $wsClient->billInquiry($va->getBillNumber());

                    $logger->error('VA response on payment check from CMS!', $response);

                    if ($response['status'] && $response['code'] === '00' && $response['data'][0]['sts_bayar'] === '1') {
                        $va->setPaidStatus('1');
                        $va->setResponse(json_encode($response['data']));

                        $em->persist($va);
                        $em->flush();

                        /** @var OrderPaymentRepository $paymentRepository */
                        $paymentRepository = $this->getRepository(OrderPayment::class);
                        /** @var BaseMail $baseMail */
                        $baseMail = $this->get(BaseMail::class);

                        foreach ($orders as $order) {
                            /** @var Store $store */
                            $store = $order->getSeller();
                            /** @var User $seller */
                            $seller = $store->getUser();
                            /** @var User $buyer */
                            $buyer = $order->getBuyer();

                            $order->setStatus($order->getIsB2gTransaction() ? 'payment_process' : 'paid');

                            /** @var OrderPayment $payment */
                            $payment = $paymentRepository->findOneBy(['invoice' => $order->getInvoice()]);

                            if (!$payment instanceof OrderPayment) {
                                $payment = new OrderPayment();
                            }

                            $payment->setOrder($order);
                            $payment->setInvoice($order->getInvoice());
                            $payment->setName($buyerName);
                            $payment->setEmail($buyerEmail);
                            $payment->setType('virtual_account');
                            $payment->setAttachment($va->getReferenceId());
                            $payment->setNominal($order->getTotal() + $order->getShippingPrice());
                            $payment->setMessage('Pembayaran menggunakan Virtual Account');
                            $payment->setBankName('bpd_bali');

                            try {
                                $payment->setDate(new DateTime('now'));
                            } catch (Exception $e) {
                            }

                            $notification = new Notification();
                            $notification->setSellerId($seller->getId());
                            $notification->setBuyerId($buyer->getId());
                            $notification->setIsSentToSeller(false);
                            $notification->setIsSentToBuyer(false);
                            $notification->setTitle($translator->trans('notifications.order_status'));
                            $notification->setContent($translator->trans('notifications.order_status_text', ['%invoice%' => $order->getInvoice(), '%status%' => 'paid']));

                            $em->persist($order);
                            $em->persist($payment);
                            $em->persist($notification);
                            $em->flush();

                            $tempInvoices[] = $order->getInvoice();
                        }

                        //--- Send email notification to buyer
                        $mailToBuyer = clone $baseMail;
                        $mailToBuyer->setMailSubject($translator->trans('message.info.new_user_payment'));
                        $mailToBuyer->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
                        $mailToBuyer->setMailRecipient($buyerEmail);
                        $mailToBuyer->setMailData([
                            'name' => $buyerName,
                            'invoices' => $tempInvoices,
                            'recipient_type' => 'buyer',
                            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                        $mailToBuyer->send();
                        //--- Send email notification to buyer

                        //--- Send email notification to admin
                        $mailToAdmin = clone $baseMail;
                        $mailToAdmin->setMailSubject($translator->trans('message.info.new_user_payment'));
                        $mailToAdmin->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
                        $mailToAdmin->setToAdmin();
                        $mailToAdmin->setMailData([
                            'name' => 'Admin',
                            'invoices' => $tempInvoices,
                            'recipient_type' => 'admin',
                            'link' => $this->generateUrl('admin_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                        $mailToAdmin->send();
                        //--- Send email notification to admin

                        $this->addFlash('success', $this->getTranslator()->trans('message.success.payment_processed', ['%total%' => 1]));
                    }
                } catch (Exception $e) {
                    $logger->error(sprintf('VA exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
                }
            }

            return $this->redirectToRoute('admin_order_shared', ['id' => base64_encode(sprintf('bm-order:%s', $invoice))]);
        }

        return $this->redirectToRoute($this->getAppRoute());
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        /** @var OrderRepository $repository */
        // $parameters = $this->populateParametersForDataTable($parameters, ['order_by' => 'o.id']);


        if ($parameters['role'] === 'buyer') {
            $parameters['roles'] = ['ROLE_USER', 'ROLE_USER_BUYER'];
            $parameters['version'] = 'v2';
        } elseif ($parameters['role'] === 'business') {
            $parameters['roles'] = ['ROLE_USER_BUSINESS'];
        } elseif ($parameters['role'] === 'government') {
            $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
            $parameters['version'] = null;
        } else {
            $parameters['roles'] = ['ROLE_INVALID'];
            $parameters['version'] = null;
        }

        $roleParam = $parameters['role'];

        // $parameters['role'] = null;

        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        $repository = $this->getRepository(Order::class);
        $data = $repository->getDataForTable($parameters);
        $url = $this->get('router')->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $package = new UrlPackage($url, new EmptyVersionStrategy());
        $writer = null;

        if (count($data['data']) > 0) {
            // dd($data);
            // if (isset($parameters['role'])) {
            //     if ($parameters['role'] === 'buyer') {
            //         $data['data'] = array_filter($data['data'], function ($item){
            //             return $item['o_isB2gTransaction'] === false;
            //         });
            //     }elseif ($parameters['role'] === 'government') {
            //         $data['data'] = array_filter($data['data'], function ($item){
            //             return $item['o_isB2gTransaction'] === true;
            //         });
            //     }

            //     $data['total'] = count($data['data']);
            // }

            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Invoice');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Doku Invoice');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Shared Invoice');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Total');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Shipping Amount');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Shipping Courier');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Shipping Service');
            $sheet->setCellValueByColumnAndRow(11, 1, 'Tracking Code');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Buyer Name');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Buyer Email');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Buyer Phone');
            $sheet->setCellValueByColumnAndRow(15, 1, 'Buyer Address');
            $sheet->setCellValueByColumnAndRow(16, 1, 'Buyer City');
            $sheet->setCellValueByColumnAndRow(17, 1, 'Buyer Province');
            $sheet->setCellValueByColumnAndRow(18, 1, 'Buyer Post Code');
            $sheet->setCellValueByColumnAndRow(19, 1, 'Note');
            $sheet->setCellValueByColumnAndRow(20, 1, 'Tax Document Email');
            $sheet->setCellValueByColumnAndRow(21, 1, 'Tax Document Phone');
            $sheet->setCellValueByColumnAndRow(22, 1, 'Tax Document File');
            $sheet->setCellValueByColumnAndRow(23, 1, 'Is B2G Transaction');
            $sheet->setCellValueByColumnAndRow(24, 1, 'Negotiation Status');
            $sheet->setCellValueByColumnAndRow(25, 1, 'Execution Time');
            $sheet->setCellValueByColumnAndRow(26, 1, 'Job Package Name');
            $sheet->setCellValueByColumnAndRow(27, 1, 'Fiscal Year');
            $sheet->setCellValueByColumnAndRow(28, 1, 'Source of Fund');
            $sheet->setCellValueByColumnAndRow(29, 1, 'Budget Ceiling');
            $sheet->setCellValueByColumnAndRow(30, 1, 'BAST File');
            $sheet->setCellValueByColumnAndRow(31, 1, 'Delivery Paper File');
            $sheet->setCellValueByColumnAndRow(32, 1, 'Tax Invoice File');
            $sheet->setCellValueByColumnAndRow(33, 1, 'Invoice File');
            $sheet->setCellValueByColumnAndRow(34, 1, 'Receipt File');
            $sheet->setCellValueByColumnAndRow(35, 1, 'SPK File');
            $sheet->setCellValueByColumnAndRow(36, 1, 'Store Name');
            $sheet->setCellValueByColumnAndRow(37, 1, 'Store Address');
            $sheet->setCellValueByColumnAndRow(38, 1, 'Product Name');
            $sheet->setCellValueByColumnAndRow(39, 1, 'Product Category');
            $sheet->setCellValueByColumnAndRow(40, 1, 'Payment Method');
            $sheet->setCellValueByColumnAndRow(41, 1, 'Shipped Method');
            $sheet->setCellValueByColumnAndRow(42, 1, 'Status Last Changed On');
            $sheet->setCellValueByColumnAndRow(43, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(44, 1, 'Updated At');


            foreach ($data['data'] as $item) {
                $status = ucwords(str_replace('_', ' ', $item['o_status']));
                $taxDocumentFile = !empty($item['o_taxDocumentFile']) ? $package->getUrl($item['o_taxDocumentFile']) : null;
                $bastFile = !empty($item['o_bastFile']) ? $package->getUrl($item['o_bastFile']) : null;
                $deliveryPaperFile = !empty($item['o_deliveryPaperFile']) ? $package->getUrl($item['o_deliveryPaperFile']) : null;
                $taxInvoiceFile = !empty($item['o_taxInvoiceFile']) ? $package->getUrl($item['o_taxInvoiceFile']) : null;
                $invoiceFile = !empty($item['o_invoiceFile']) ? $package->getUrl($item['o_invoiceFile']) : null;
                $receiptFile = !empty($item['o_receiptFile']) ? $package->getUrl($item['o_receiptFile']) : null;
                $workOrderLetterFile = !empty($item['o_workOrderLetterFile']) ? $package->getUrl($item['o_workOrderLetterFile']) : null;

                /** @var OrderRepository $orderRepository */
                $orderRepository = $this->getRepository(Order::class);
                $detailOrder = $orderRepository->getOrderProducts($item['o_id']);

                $storeName = $detailOrder[0]['s_name'];
                $storeAddress = $detailOrder[0]['s_address'];
                $productCategory = $detailOrder[0]['pc_name'];
                $productName = $detailOrder[0]['p_name'];

                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['o_id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['o_invoice']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['o_dokuInvoiceNumber']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $item['o_sharedInvoice']);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $status);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['o_total']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item['o_shippingPrice']);
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['o_shippingCourier']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['o_shippingService']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['o_trackingCode']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['o_name']);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['o_email']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['o_phone']);
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['o_address']);
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['o_city']);
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), $item['o_province']);
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), $item['o_postCode']);
                $sheet->setCellValueByColumnAndRow(19, ($number + 1), $item['o_note']);
                $sheet->setCellValueByColumnAndRow(20, ($number + 1), $item['o_taxDocumentEmail']);
                $sheet->setCellValueByColumnAndRow(21, ($number + 1), $item['o_taxDocumentPhone']);
                $sheet->setCellValueByColumnAndRow(22, ($number + 1), $taxDocumentFile);
                $sheet->setCellValueByColumnAndRow(23, ($number + 1), $item['o_isB2gTransaction'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(24, ($number + 1), $item['o_negotiationStatus']);
                $sheet->setCellValueByColumnAndRow(25, ($number + 1), $item['o_executionTime']);
                $sheet->setCellValueByColumnAndRow(26, ($number + 1), $item['o_jobPackageName']);
                $sheet->setCellValueByColumnAndRow(27, ($number + 1), $item['o_fiscalYear']);
                $sheet->setCellValueByColumnAndRow(28, ($number + 1), $item['o_sourceOfFund']);
                $sheet->setCellValueByColumnAndRow(29, ($number + 1), $item['o_budgetCeiling']);
                $sheet->setCellValueByColumnAndRow(30, ($number + 1), $bastFile);
                $sheet->setCellValueByColumnAndRow(31, ($number + 1), $deliveryPaperFile);
                $sheet->setCellValueByColumnAndRow(32, ($number + 1), $taxInvoiceFile);
                $sheet->setCellValueByColumnAndRow(33, ($number + 1), $invoiceFile);
                $sheet->setCellValueByColumnAndRow(34, ($number + 1), $receiptFile);
                $sheet->setCellValueByColumnAndRow(35, ($number + 1), $workOrderLetterFile);
                $sheet->setCellValueByColumnAndRow(36, ($number + 1), $storeName);
                $sheet->setCellValueByColumnAndRow(37, ($number + 1), $storeAddress);
                $sheet->setCellValueByColumnAndRow(38, ($number + 1), $productName);
                $sheet->setCellValueByColumnAndRow(39, ($number + 1), $productCategory);
                $sheet->setCellValueByColumnAndRow(40, ($number + 1), !empty($item['o_ppk_payment_method']) ? $this->getParameter('ppk_method_options')[$item['o_ppk_payment_method']]:'');
                $sheet->setCellValueByColumnAndRow(41, ($number + 1), !empty($item['o_shipped_method']) ? $this->getParameter('shipped_method_options')[$item['o_shipped_method']]:'');
                $sheet->setCellValueByColumnAndRow(42, ($number + 1), !empty($item['o_statusChangeTime']) ? $item['o_statusChangeTime']->format('Y-m-d H:i:s') : '-');
                $sheet->setCellValueByColumnAndRow(43, ($number + 1), $item['o_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(44, ($number + 1), !empty($item['o_updatedAt']) ? $item['o_updatedAt']->format('Y-m-d H:i:s') : '-');


                $number++;
            }

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }

    public function constructUploadPath($path = ''): string
    {
        $prefix = 'orders/';

        $parts = explode('/', $path);

        if (isset($parts[1]) && count($parts) === 3) {
            $prefix .= $parts[1];
            $prefix .= '/';

            return $prefix;
        }

        $prefix .= 'reupload_file/';

        return $prefix;
    }

    public function erzapOrderWebhook(string $orderSharedId, $em)
    {
        $repository = $this->getRepository(Order::class);
        $orders = $repository->findBy(['sharedId' => $orderSharedId]);

        if (count($orders) > 0) {
            foreach ($orders as $order) {
                $request = [];
                $request['order_id'] = $order->getInvoice();
                $request['order_status'] = $order->getStatus();
                $request['shipping_courrier'] = $order->getShippingCourier().'('.$order->getShippingService().')';
                $request['order_notes'] = $order->getNote();
                $request['order_time'] = $order->getCreatedAt()->getTimestamp();
                $request['shop_id'] = $order->getSeller()->getShopId();

                // Product
                $orderProducts = $repository->getOrderProducts($order->getId());
                $produk_mp = [];

                if (count($orderProducts) > 0) {
                    foreach ($orderProducts as $p) {
                        $produk['idproduk_mp'] = $p['p_id'];
                        $produk['nama_produk'] = $p['p_name'];
                        $produk['quantity'] = (int)$p['op_quantity'];
                        $produk['notes'] = $p['op_note'];
                        $produk['harga_jual'] = (int)$p['p_price'];
                        $produk['total_harga'] = (int)$p['p_price'] * (int)$p['op_quantity'];
                        $produk['sku'] = $p['p_sku'];
                        $produk_mp[] = $produk;
                    }
                }
                $request['produk_mp'] = $produk_mp;

                $reciept['name'] = $order->getBuyer()->getUsername();
                $reciept['phone'] = $order->getBuyer()->getPhoneNumber();

                // address
                $address['address_full'] = $order->getAddress();
                $address['district'] = $order->getDistrict();
                $address['city'] = $order->getCity();
                $address['province'] = $order->getProvince();
                $address['country'] = $order->getCountry();
                $address['postal_code'] = $order->getPostCode();

                $reciept['address'] = $address;

                $request['recipient'] = $reciept;

                // Price
                $price['subtotal'] = (int)$order->getTotal();
                $price['biaya_kirim'] = (int)$order->getShippingPrice();
                $price['discount'] = 0;
                $price['total_payment'] = (int)($order->getTotal() + $order->getShippingPrice());
                $request['price'] = $price;

                $erzapReportStatus = 'failed';

                try {
                    $headers = [
                        'Content-Type' => 'application/json',
                    ];
                    $options = ['headers' => $headers, 'json' => $request];
                    $endpoint = getenv('ERZAP_WEBHOOK_ENDPOINT');
                    $result = HttpClientService::run($endpoint, $options, 'POST');

                    if ($result['error'] === false) {
                        $erzapReportStatus = 'sent';
                        $this->logger->error('ERZAP Order webhook success', $result);

                    } else {
                        $this->logger->error('ERZAP Order webhook failed', $result);
                    }

                } catch (\Throwable $th) {
                    $this->logger->error('ERZAP Order webhook error', [$th->getMessage()]);
                }

                $order->setErzapOrderReport($erzapReportStatus);
                $em->persist($order);
                $em->flush();
            }
        }
    }

    public function resend_djp(Request $request, $id):RedirectResponse
    {
        $repository = $this->getRepository($this->entity);
        $order      = $repository->find($id);
        $em         = $this->getEntityManager();
        $roleParam  = $request->query->get('role', null);
        $translator = $this->getTranslator();
        $valid_data = true;

        if (empty($order->getTaxDocumentNpwp())) {
            $this->addFlash(
                'warning',
                $translator->trans('message.error.dokumen_npwp_tab_data')
            );
            $valid_data = false;
        }

        if ($order->getPpkPaymentMethod() === 'uang_persediaan' && $valid_data == true) {
            $djpService = $this->get(DJPService::class);
            $result_barang = $djpService->postTransations($order);

            $djpStatus = 'djp_report_failed';

            if ($result_barang['error'] === false) {
                $djpStatus = 'djp_report_sent';
                $order->setDjpResponseOrder(json_encode($result_barang));
            }


            $order->setDjpReportStatus($djpStatus);

            $em->persist($order);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.djp_send_success')
            );
        }

        return $this->redirectToRoute($this->getAppRoute('edit'), ['id' => $id, 'role' => $roleParam]);
    }

    public function reset_va_doku($id)
    {
        $repository = $this->getRepository(Order::class);
        $order      = $repository->find($id);

        $em = $this->getEntityManager();
        if (!empty($order->getDokuInvoiceNumber())) {
                $order->setDokuInvoiceNumber('');
        }
        $em->persist($order);
        $em->flush();
        $this->addFlash(
            'success',
            'Berhasil Reset Doku Virtual Account'
        );
        return $this->redirect($this->generateUrl($this->getAppRoute('edit'), ['id' => $id, 'role' => 'government']));
    }
    
    public function resend_email($type, $id):RedirectResponse
    {
        $repository = $this->getRepository(Order::class);
        $repoPPK = $this->getRepository(UserPpkTreasurer::class);
        $repoPic = $this->getRepository(UserPicDocument::class);
        $repoDoku = $this->getRepository(Doku::class);
        $order      = $repository->find($id);
        $status     = $order->getStatus() != 'paid' ? 'received' : 'paid';
        $subject     = $order->getStatus() != 'paid' ? '_Approval PPK' : '_Paid';
        $translator = $this->getTranslator();

        if ($type == 'ppk') {
            if (!empty($order->getPpkName()) && !empty($order->getPpkEmail())) {

                $data_ppk = $repoPPK->findOneBy(['email'=>$order->getPpkEmail()]);
                /**
                 * Send email to ppk
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().$subject);
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getPpkEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getPpkName(),
                        'invoice' => $order->getInvoice(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_ppk->getSatker(),
                        'klpd' => $data_ppk->getKldi(),
                        'merchant' => $order->getSeller()->getName(),
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'status' => $status,
                        'type' => 'ppk',
                        'link_login' => getenv('APP_URL').'/login',
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_bapd' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bapd'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_spk' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk_new'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {
                    
                }

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.resend_email_ppk')
                );
            }
        } else if ($type == 'treasurer') {

            if (!empty($order->getTreasurerName()) && !empty($order->getTreasurerEmail())) {
                if ($status == 'received') {
                    $subject = '_Payment Process';
                }

                $data_bendahara = $repoPPK->findOneBy(['email' => $order->getTreasurerEmail()]);
                /**
                 * Send email to bendahara
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().$subject);
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getTreasurerEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getTreasurerName(),
                        'invoice' => $order->getInvoice(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_bendahara->getSatker(),
                        'klpd' => $data_bendahara->getKldi(),
                        'merchant' => $order->getSeller()->getName(),
                        'status' => $status,
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'type' => 'treasurer',
                        'link_login' => getenv('APP_URL').'/login',
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_pay' => $this->generateUrl('treasurer_pay_with_channel', ['id' => $order->getSharedId(), 'channel' => 'doku'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_bapd' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bapd'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_spk' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk_new'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {

                }

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.resend_email_treasurer')
                );
            }
        } else if ($type == 'pic') {
            if (!empty($order->getUnitName()) && !empty($order->getUnitEmail())) {
                $data_pic = $repoPic->findOneBy(['email' => $order->getUnitEmail()]);
                /**
                 * Send email to pic
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_'.$subject);
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getUnitEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getUnitName(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_pic->getSatker(),
                        'klpd' => $data_pic->getKlpd(),
                        'invoice' => $order->getInvoice(),
                        'merchant' => $order->getSeller()->getName(),
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'status' => $status,
                        'type' => 'pic',
                        'link_login' => getenv('APP_URL').'/login',
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_st' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'performa_invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_bapd' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bapd'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_spk' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk_new'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {

                }

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.resend_email_pic')
                );
            }
        }
        return $this->redirect($this->generateUrl($this->getAppRoute('edit'), ['id' => $id, 'role' => 'government']));
    }
}
