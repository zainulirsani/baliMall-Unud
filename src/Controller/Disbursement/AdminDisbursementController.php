<?php

namespace App\Controller\Disbursement;

use App\Controller\AdminController;
use App\Email\BaseMail;
use App\Entity\Disbursement;
use App\Entity\Order;
use App\Entity\Store;
use App\Entity\User;
use App\Service\HttpClientService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

class AdminDisbursementController extends AdminController
{
    protected $key = 'disbursement';
    protected $entity = Disbursement::class;
    private $logger;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->authorizedRoles = ['ROLE_SUPER_ADMIN', 'ROLE_ACCOUNTING_1', 'ROLE_ACCOUNTING_2'];
        $this->logger = $logger;
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'transaction_id', 'store', 'invoice', 'grand_total', 'order_status', 'disbursement_status', 'tax_type', 'status_last_changed', 'created', 'updated_at', 'actions']);
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        $buttonEdit = 'Request Disbursement';
        $buttonDone = 'Done';
        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'd.id']);
        // dd($parameters);

        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }

        if ($parameters['role'] === 'buyer') {
            $parameters['roles'] = ['ROLE_USER', 'ROLE_USER_BUYER'];
            $parameters['version'] = 'v2';
        } elseif ($parameters['role'] === 'government') {
            $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
        } else {
            $parameters['roles'] = ['ROLE_INVALID'];
        }

        $parameters['d_status_last_changed'] = $parameters['status_last_changed'];
        unset($parameters['status_last_changed']);
        // dd($parameters);

        $roleParam = $parameters['role'];

        $parameters['role'] = null;
        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $disbursementData = $results['data'];
        $data = [];

        try {
            foreach ($disbursementData as $disbursement) {
                $id = (int)$disbursement['d_id'];
                $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $id, 'role' => $roleParam]);
                $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id, 'role' => $roleParam]);
                $urlDone = $this->generateUrl($this->getAppRoute('done'));
                $orderStatus = $disbursement['o_status'];
                $disbursementStatus = $disbursement['d_status'];
                $method_pay = [$this->getParameter('ppk_method_options')[!empty($disbursement['o_ppk_payment_method']) ? $disbursement['o_ppk_payment_method']:'uang_persediaan']];

                if ($disbursementStatus === 'pending') {
                    $buttons = "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
                } elseif ($disbursementStatus === 'processed') {
                    $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>\n";
                    $buttons .= "<a data-toggle=\"modal\" data-target=\"#modal-done\" data-id=\"$id\" data-from=\"list\" data-role=\"$roleParam\" class=\"btn btn-primary btn-upload-proof \">$buttonDone</a>";
                } elseif ($disbursementStatus === 'done') {
                    $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
                }

                $checkbox = "<input value=\"$id\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";

                if ($this->isAuthorizedToManage() === false) {
                    $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
                }

                $lastChangeStatus = '-';

                if (isset($disbursement['d_statusChangeTime']) && !empty($disbursement['d_statusChangeTime'])) {
                    Carbon::setLocale('id');
                    try {
                        $lastChangeStatus = Carbon::now()->subtract(Carbon::parse($disbursement['d_statusChangeTime']))->diffInDays();
                        if ($lastChangeStatus < 1) {
                            $lastChangeStatus = Carbon::now()->subtract(Carbon::parse($disbursement['d_statusChangeTime']))->diffForHumans();
                        } else {
                            $lastChangeStatus .= ' hari yang lalu';
                        }
                    }catch (Exception $exception) {

                    }
                }

                $data[] = [
                    $checkbox,
                    $disbursement['o_sharedInvoice'],
                    ucfirst($disbursement['s_name']),
                    $disbursement['o_invoice'],
                    number_format($disbursement['d_total'], 0, '', '.'),
                    $translator->trans(sprintf('label.%s', $orderStatus)),
                    // $method_pay,
                    $translator->trans(sprintf('label.%s', $disbursement['d_status'])),
                    !empty($disbursement['o_taxType']) ? $this->getParameter('tax_payment_types')[$disbursement['o_taxType']]['label'] : '-',
                    $lastChangeStatus,
                    !empty($disbursement['d_createdAt']) ? $disbursement['d_createdAt']->format('d M Y') : '-',
                    !empty($disbursement['d_updatedAt']) ? $disbursement['d_updatedAt']->format('d M Y') : '-',
                    $buttons,
                ];
            }
        } catch (\Throwable $throwable) {

        }

        return [
            'draw' => $parameters['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    protected function actReadData(int $id)
    {
        $repository = $this->getRepository($this->entity);
        $orderRepository = $this->getRepository(Order::class);

        $data = $repository->getDataById($id);

        $data['o_products'] = $orderRepository->getOrderProducts($data['order_id']);

        $data['is_authorized_to_manage'] = $this->isAuthorizedToManage();
        $data['is_authorized_to_change_status'] = $this->isAuthorizedToChangeStatus();


        return $data;
    }

    protected function actEditData(int $id)
    {
        $repository = $this->getRepository($this->entity);
        $orderRepository = $this->getRepository(Order::class);

        $data = $repository->getDataById($id);

        if ($data['status'] !== 'pending') {
            throw new NotFoundHttpException();
        }

        $data['o_products'] = $orderRepository->getOrderProducts($data['order_id']);

        return $data;
    }

    protected function actUpdateData(Request $request, int $id): string
    {
        $formData = $request->request->all();
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);
        $translator = $this->getTranslator();
        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $repository = $this->getRepository($this->entity);
        $disbursement = $repository->find($id);

        // if (!$disbursement instanceof Disbursement) {
        //     throw new NotFoundHttpException();
        // }

        if ($disbursement->getStatus() === 'pending') {

            $orderProducts = $this->getRepository(Order::class)->find($disbursement->getOrderId())->getOrderProducts();

            if (count($orderProducts) > 0) {
                foreach ($orderProducts as $key => $order_product) {
                    $nominal_order_product = floatval($formData['fee_nominal'][$key]);
                    $persentase_order_product = floatval($formData['fee'][$key]);
                    $order_product->setFeeNominal($nominal_order_product);
                    $order_product->setFee($persentase_order_product);

                    $entity_manager = $this->getEntityManager();
                    $entity_manager->persist($order_product);
                    $entity_manager->flush();
                }
            }
            $totalProductPrice = $disbursement->getTotalProductPrice();
            $productFee = floatval($formData['total_product_fee']);
            $ppn = $formData['ppn'] == '' ? '' : floatval(str_replace(",", "", $formData['ppn']));
            $pph = $formData['pph'] == '' ? '' : floatval(str_replace(",", "", $formData['pph']));
            $bankFee = $formData['bank_fee'] == '' ? '' : floatval(str_replace(",", "", $formData['bank_fee']));
            $managementFee = $formData['management_fee'] == '' ? '' : floatval(str_replace(",", "", $formData['management_fee']));
            $otherFee = $formData['other_fee'] == '' ? '' : floatval(str_replace(",", "", $formData['other_fee']));

            $persentasePPN = $formData['persentase_ppn'] == '' ? '' : (float)$formData['persentase_ppn'];
            $persentasePPH = $formData['persentase_pph'] == '' ? '' : (float)$formData['persentase_pph'];
            $persentaseBankFee = $formData['persentase_bank'] == '' ? '' : (float)$formData['persentase_bank'];
            $persentaseManagementFee = $formData['persentase_management_fee'] == '' ? '' : (float)$formData['persentase_management_fee'];
            $persentaseOtherFee = $formData['persentase_other_fee'] == '' ? '' : (float)$formData['persentase_other_fee'];
            $orderShippingPrice = $formData['order_shipping_price'] == '' ? '' : floatval(str_replace(",", "", $formData['order_shipping_price']));
            $total = null;

            try {
                $total = $totalProductPrice + $orderShippingPrice - ($productFee + $pph + $ppn + $bankFee + $managementFee + $otherFee);

            } catch (\Throwable $throwable) {
            }

            try {
                $disbursement->setPpn($ppn);
                $disbursement->setPph($pph);
                $disbursement->setBankFee($bankFee);
                $disbursement->setProductFee($productFee);
                $disbursement->setManagementFee($managementFee);
                $disbursement->setOtherFee($otherFee);
                $disbursement->setPersentasePpn($persentasePPN);
                $disbursement->setPersentasePph($persentasePPH);
                $disbursement->setPersentaseBank($persentaseBankFee);
                $disbursement->setPersentaseManagement($persentaseManagementFee);
                $disbursement->setPersentaseOther($persentaseOtherFee);
                $disbursement->setOrderShippingPrice($orderShippingPrice);
                $disbursement->setTotal($total);
            } catch (\Throwable $throwable) {
            }
        }

        if ($disbursement->getStatus() === 'pending') {
            $disbursement->setStatus('processed');
        } elseif ($disbursement->getStatus() === 'processed') {
            $disbursement->setStatus('done');
        }
        $disbursement->setStatusChangeTime();

        $disbursement->setLogs($this->getUser());

        $checkErrors = $this->getValidator()->validate($disbursement);

        if (count($checkErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($disbursement);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.disbursement_updated')
            );

            $roleParam = $request->query->get('role', null);

            $redirect = $this->generateUrl($this->getAppRoute(), ['role' => $roleParam]);

            // $this->sendDisbursementEmail($disbursement);

        } else {
            $errors = [];

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));

            foreach ($checkErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('errors', $errors);
        }

        return $redirect;
    }

    public function setDisbursementToDone(Request $request)
    {
        $uploadedFile = $request->files->get('payment_proof');
        $get_data_from = $request->files->get('from_click_done');
        $valid_extension = ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'];

        $flashBag = $this->get('session.flash_bag');

        $repository = $this->getRepository($this->entity);
        $roleParam = $this->getRequest()->request->get('role_modal_done', null);
        $app_route = $get_data_from == 'list' ? $this->getAppRoute() : $this->getAppRoute('index');
        $redirect = $this->generateUrl($app_route, ['role' => $roleParam]);

        $disbursement = $repository->find($this->getRequest()->request->get('id_modal_done', null));
        $message = $this->getTranslator()->trans('message.info.check_form');
        $disbursementError = $this->getValidator()->validate($disbursement);
        if (!$disbursement instanceof Disbursement) {
            throw new NotFoundHttpException();
        }

        if (empty($uploadedFile)) {
            $message = $this->getTranslator()->trans('global.proof_not_empty', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $disbursement, 'payment_proof', '', null, null, new Assert\NotBlank(), null);

            $disbursementError->add($constraint);
        } else {
            $extension = $uploadedFile->getClientOriginalExtension();
            if (!in_array($extension, $valid_extension)) {
                $message = $this->getTranslator()->trans('global.file_not_valid', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $disbursement, 'payment_proof', '', null, null, new Assert\NotBlank(), null);

                $disbursementError->add($constraint);
            }
        }

        if (count($disbursementError) === 0) {
            if ($disbursement->getStatus() === 'processed') {
                $prefixPath = 'uploads/';
                $uploader = $this->get(FileUploader::class);
                $uploader->setTargetDirectory('payment/');
                $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
                $disbursement->setPaymentProof($filePath);
                $disbursement->setStatus('done');
                $disbursement->setStatusChangeTime();
                $disbursement->setLogs($this->getUser());
            }

            $em = $this->getEntityManager();
            $em->persist($disbursement);
            $em->flush();

            $this->sendDisbursementEmail($disbursement);

            $order = $this->getRepository(Order::class)->find($disbursement->getOrderId());
           if ($disbursement->getStatus() === 'done' && $order->getSeller()->getIsUsedErzap() == true) {
               $this->erzapOrderCashOut($order);
           }

            $this->addFlash(
                'success',
                $this->getTranslator()->trans('message.success.disbursement_updated')
            );
        } else {
            $errors = [];

            $flashBag->set('warning', $message);

            foreach ($disbursementError as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('errors', $errors);
        }


        return $this->redirect($redirect);
    }

    public function setDisbursementStatus(Request $request){
        // get disbursement data from edit modal

        $targetStatus = $request->request->get('status');
        $disbursementId = $request->request->get('disbursement_id', null);
        $roleParam = $request->request->get('disbursement_role');

        $repository = $this->getRepository($this->entity);
        $flashBag = $this->get('session.flash_bag');
        
        // set default redirect
        $app_route = $this->getAppRoute('view');
        $redirect = $this->generateUrl($app_route, ['role' => $roleParam, 'id'=>$disbursementId]);

        // check if user has permission to edit (superadmin only); redirect if not allowed
        // find disbursement
        $disbursement = $repository->find($disbursementId);
        $message = $this->getTranslator()->trans('message.info.check_form');
        $disbursementError = $this->getValidator()->validate($disbursement);
        if (!$disbursement instanceof Disbursement) {
            throw new NotFoundHttpException();
        }

        // prevent user to change disbursement with status other than 'done'
        if($disbursement->getStatus() != 'done'){
            $message = $this->getTranslator()->trans('disbursement.allowed_change_status', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $disbursement, 'status', '', null, null, new Assert\NotBlank(), null);
            $disbursementError->add($constraint);
        }

        // prevent user to change disbursement to status 'done'
        if(strtolower($targetStatus) == 'done'){
            $message = $this->getTranslator()->trans('disbursement.disbursement_protect_done', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $disbursement, 'status', '', null, null, new Assert\NotBlank(), null);
            $disbursementError->add($constraint);
        }

        // on error, return flashbag with validation errors
        if (count($disbursementError) > 0) {
            $errors = [];

            $flashBag->set('warning', $message);
            foreach ($disbursementError as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('errors', $errors);
        }else{
            // NO ERROR -> PROCEED TO UPDATE
            // edit status & add log
            $disbursement->setStatus($targetStatus);
            $disbursement->setStatusChangeTime();
            $disbursement->setLogs($this->getUser());
    
            $em = $this->getEntityManager();
            $em->persist($disbursement);
            $em->flush();
    
            // return success
            $this->addFlash(
                'success',
                $this->getTranslator()->trans('message.success.disbursement_updated')
            );
        }

        return $this->redirect($redirect);
        
    }

    protected function prepareDataTableButton(): void
    {
        $this->dataTable->setButtons([]);
    }

    protected function manipulateDataPackage(): void
    {
        $this->dataPackage->setAbleToCreate(false);
        $this->dataPackage->setAbleToExport(true);
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        $repository = $this->getRepository(Disbursement::class);

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

        if (isset($parameters['date_start'])) {
            $dateStart = $parameters['date_start'];
            $parameters['date_start'] = !empty($dateStart) ? sprintf('%s 00:00:00', $dateStart) : null;
        }

        if (isset($parameters['date_end'])) {
            $dateEnd = $parameters['date_end'];
            $parameters['date_end'] = !empty($dateEnd) ? sprintf('%s 23:59:59', $dateEnd) : null;
        }

        $data = $repository->getDataToExport($parameters);
        $writer = null;
        if (count($data['data']) > 0) {
            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'Transaction ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Merchant');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Rekening Name');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Bank Name');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Rekening Number');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Invoice');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Order Status');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Total Product Price');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Product Fee');
            $sheet->setCellValueByColumnAndRow(11, 1, 'Ppn');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Pph');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Bank Fee');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Management Fee');
            $sheet->setCellValueByColumnAndRow(15, 1, 'Other Fee');
            $sheet->setCellValueByColumnAndRow(16, 1, 'Disbursement Status');
            $sheet->setCellValueByColumnAndRow(17, 1, 'Total');
            $sheet->setCellValueByColumnAndRow(18, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(19, 1, 'Updated At');


            foreach ($data['data'] as $item) {
                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['o_sharedInvoice']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['s_name']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['d_rekening_name']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $item['d_bank_name']);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item['d_nomor_rekening']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['o_invoice']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item['o_status']);
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['d_totalProductPrice']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['d_productFee']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['d_ppn']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['d_pph']);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['d_bankFee']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['d_managementFee']);
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['d_otherFee']);
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['d_status']);
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), empty($item['d_total']) ? 0 : $item['d_total']);
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), $item['d_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(19, ($number + 1), !empty($item['d_updatedAt']) ? $item['d_updatedAt']->format('Y-m-d H:i:s') : '-');

                $number++;
            }

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $stores = [];
        $userStores = $this->getUserStoresData();
        foreach ($userStores as $userStore) {
            $stores[$userStore['id']] = $userStore['text'];
        }
        $tax_payment = $this->getParameter('tax_payment_types');
        $tax_type['belum_pilih'] = 'Belum memilih Tipe Pajak';
        foreach ($tax_payment as $key => $value) {
            $tax_type[$key] = $value['label'];
        }
        $this->dataTable->setFilters([
            'store' => [
                'type' => 'select',
                'collections' => $stores,
            ],
            'keywords' => [
                'type' => 'text',
            ],
            'role' => [
                'type' => 'hidden',
                'selections' => $this->getParameter('user_roles'),
                'value' => htmlspecialchars($request->query->get('role', 'buyer')),
            ],
            'status' => [
                'type' => 'select',
                'selections' => $this->getParameter('disbursement_statuses'),
                'multiple' => true,
            ],
            'status_order' => [
                'type' => 'select',
                'selections' => $this->getParameter('order_statuses'),
                'multiple' => true,
            ],
            'jump_to_page' => [
                'type' => 'text',
            ],
            'status_last_changed' => [
                'type' => 'select',
                'selections' => $this->getParameter('status_change_filter_option'),
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
            'updated_at' => [
                'type' => 'checkbox',
            ],
            'tax_type' => [
                'type' => 'select',
                'selections' => $tax_type,
            ],
//            'year' => [
//                'type' => 'text',
//            ],
        ]);
    }

    protected function sendDisbursementEmail(Disbursement $disbursement)
    {
        $orderRepository = $this->getRepository(Order::class);
        $order = $orderRepository->find($disbursement->getOrderId());

        if ($order instanceof Order) {
            $data = [
                'id' => $disbursement->getId(),
                'total_product_price' => $disbursement->getTotalProductPrice(),
                'product_fee' => $disbursement->getProductFee(),
                'ppn' => $disbursement->getPpn(),
                'pph' => $disbursement->getPph(),
                'management_fee' => $disbursement->getManagementFee(),
                'other_fee' => $disbursement->getOtherFee(),
                'bank_fee' => $disbursement->getBankFee(),
                'status' => $disbursement->getStatus(),
                'total' => $disbursement->getTotal(),
                'invoice' => $order->getInvoice(),
                'products' => $orderRepository->getOrderProducts($order->getId()),
                'persentase_ppn' => $disbursement->getPersentasePpn(),
                'persentase_pph' => $disbursement->getPersentasePph(),
                'persentase_bank' => $disbursement->getPersentaseBank(),
                'persentase_management' => $disbursement->getPersentaseManagement(),
                'persentase_other' => $disbursement->getPersentaseOther(),
                'payment_proof' => $disbursement->getPaymentProof(),
                'rekening_name' => $disbursement->getRekeningName(),
                'bank_name' => $disbursement->getBankName(),
                'nomor_rekening' => $disbursement->getNomorRekening(),
                's_umkm_category' => $order->getSeller()->getUmkmCategory(),
            ];

            /** @var Store $seller */
            $seller = $order->getSeller();
            /** @var User $owner */
            $owner = $seller->getUser();
            /** @var BaseMail $mailToSeller */
            $mailToSeller = $this->get(BaseMail::class);
            $mailToSeller->setMailSubject($this->getTranslator()->trans('message.info.disbursement'));
            $mailToSeller->setMailTemplate('@__main__/email/order_disbursement.html.twig');
            $mailToSeller->setMailRecipient($owner->getEmailCanonical());
            $mailToSeller->setMailData([
                'name' => $owner->getFirstName(),
                'invoice' => $order->getInvoice(),
                'data' => $data,
                'link' => $this->generateUrl('user_order_detail', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToSeller->send();
        }
    }

    public function erzapOrderCashOut($order)
    {
        $endpoint = getenv('ERZAP_ORDER_CASH_OUT_WEBHOOK');
        $request = [
            'order_id' => $order->getInvoice(),
            'order_status' => 'COMPLETED',
            'shop_id' => $order->getSeller()->getShopId()
        ];

        $response = HttpClientService::run($endpoint, ['json' => $request], 'POST');

        $erzapStatus = 'order_cash_out_failed';

        if ($response['error'] === false) {
            $erzapStatus = 'order_cash_out_sent';
        }

        $em = $this->getEntityManager();

        $order->setErzapOrderReport($erzapStatus);
        $em->persist($order);
        $em->flush();
    }

}
