<?php

namespace App\Controller\Store;

use App\Controller\AdminController;
use App\Email\BaseMail;
use App\Entity\Store;
use App\Entity\StoreOwnerLog;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\Disbursement;
use App\Entity\UserAddress;
use App\Helper\StaticHelper;
use App\Repository\DisbursementRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreOwnerLogRepository;
use App\Repository\StoreRepository;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManager;
use http\Client\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminStoreController extends AdminController
{
    protected $key = 'store';
    protected $entity = Store::class;
    protected $overwriteOwner = false;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->authorizedRoles = ['ROLE_ADMIN_MERCHANT', 'ROLE_SUPER_ADMIN'];
    }

    protected function prepareDataTableButton(): void
    {
        if ($this->isAuthorizedToManage()) {
            $buttons = [
                'activate' => [
                    'class' => ''
                ],
                'deactivate' => [
                    'class' => ''
                ],
                'delete' => [
                    'class' => 'btn-danger'
                ]
            ];

            $this->dataTable->setButtons($buttons);
        }
    }

    // overriding RouteControllerTrait create function
    public function create()
    {
        $this->prepareTemplateSection();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);

        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');
        $productCategories = productCategoryConversionData($productCategories);

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'productCategories' => $productCategories,
        ]);
    }

    // overriding RouteControllerTrait edit function
    public function edit($id)
    {
        $this->prepareTemplateSection();

        $formData = $this->actEditData($id);

        if (!$formData) {
            /** @var TranslatorInterface $translator */
            $translator = $this->getTranslator();

            throw $this->createNotFoundException($translator->trans('message.error.404'));
        }

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_edit', $this->key);

        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');
        $productCategories = productCategoryConversionData($productCategories);
        
        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'productCategories' => $productCategories,
        ]);
    }

    public function fetchSelect()
    {
        $this->isAjaxRequest();

        $request = $this->getRequest();
        $search = $request->query->get('search', null);
        $items = [
            [
                'id' => '',
                'text' => $this->getTranslator()->trans('label.select_option'),
            ]
        ];

        if (!empty($search)) {
            $parameters = [
                'order_by' => 's.id',
                'sort_by' => 'DESC',
                'search' => filter_var($search, FILTER_SANITIZE_STRING),
            ];

            /** @var StoreRepository $repository */
            $repository = $this->getRepository($this->entity);
            $items = $repository->getDataForSelectOptions($parameters);
        }

        return $this->view('', ['items' => $items], 'json');
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        //$buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

//        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 's.id']);
        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 's.updatedAt']);

        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }
        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        /** @var StoreRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $stores = $results['data'];
        $data = [];

        foreach ($stores as $store) {
            $storeId = (int)$store['s_id'];
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $storeId]);
            //$urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $storeId]);
//            $status = (int) $store['s_isActive'] === 1 ? 'label.active' : 'label.inactive';
//            $status = $translator->trans($status);
            $status = (string)$store['s_status'];
            $verified = (int)$store['s_isVerified'] === 1 ? 'label.verified' : 'label.unverified';
            $verified = $translator->trans($verified);

            $checkbox = "<input value=\"$storeId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
            $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";

            if ($this->isAuthorizedToManage()) {
                $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$storeId\">$buttonDelete</a>";
            }

            $umkm_category = !empty($store['s_businessCriteria']) ? $translator->trans('label.'.$this->getParameter('business_criteria')[strtoupper($store['s_businessCriteria'])]['label']) : '-';

            $data[] = [
                $checkbox,
                $store['s_idTayang'] ?? '-',
                $store['s_name'],
                trim($store['u_firstName'] . ' ' . $store['u_lastName']),
                $status,
                $umkm_category,
                $verified,
                !empty($store['s_createdAt']) ? $store['s_createdAt']->format('d M Y H:i') : '-',
                !empty($store['s_updatedAt']) ? $store['s_updatedAt']->format('d M Y H:i') : '-',
                $buttons,
            ];
        }

        return [
            'draw' => $parameters['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    protected function getDefaultData(): array
    {
        $data = parent::getDefaultData();
        $data['dt_script'] = 'v2';
        $data['register_number'] = $this->getRegisterNumber();

        return $data;
    }

    protected function actReadData(int $id)
    {
        /** @var StoreRepository $repository */
        $repository = $this->getRepository($this->entity);
        $data = $repository->getDataWithOwnerById($id);
        $totalPreviousFiles = 0;

        if (!empty($data['s_rekeningFile'])) {
            $data['s_rekeningFileRealPath']   = $data['s_rekeningFile'];
            $data['s_rekeningFileName']       = $this->getFileName($data['s_rekeningFile']);
            $data['s_rekeningFileDownload']   = $this->getDownloadPath($data['s_rekeningFile']);
            $data['s_rekeningFile']           = $this->getFilePath($data['s_rekeningFile']);
        }

        if (!empty($data['u_npwpFile'])) {
            $data['u_npwpFileRealPath']       = $data['u_npwpFile'];
            $data['u_npwpFileName']           = $this->getFileName($data['u_npwpFile']);
            $data['u_npwpFileDownload']       = $this->getDownloadPath($data['u_npwpFile']);
            $data['u_npwpFile']               = $this->getFilePath($data['u_npwpFile']);
        }
        
        if (!empty($data['u_user_signature'])) {
            $data['u_user_signatureRealPath'] = $data['u_user_signature'];
            $data['u_user_signatureName']     = $this->getFileName($data['u_user_signature']);
            $data['u_user_signatureDownload'] = $this->getDownloadPath($data['u_user_signature']);
            $data['u_user_signature']         = $this->getFilePath($data['u_user_signature']);
        }

        if (!empty($data['u_user_stamp'])) {
            $data['u_user_stampRealPath']     = $data['u_user_stamp'];
            $data['u_user_stampName']         = $this->getFileName($data['u_user_stamp']);
            $data['u_user_stampDownload']     = $this->getDownloadPath($data['u_user_stamp']);
            $data['u_user_stamp']             = $this->getFilePath($data['u_user_stamp']);
        }

        if (!empty($data['u_photoProfile'])) {
            $data['u_photoProfileRealPath']   = $data['u_photoProfile'];
            $data['u_photoProfileName']       = $this->getFileName($data['u_photoProfile']);
            $data['u_photoProfileDownload']   = $this->getDownloadPath($data['u_photoProfile']);
            $data['u_photoProfile']           = $this->getFilePath($data['u_photoProfile']);
        }

        if (!empty($data['u_bannerProfile'])) {
            $data['u_bannerProfileRealPath']  = $data['u_bannerProfile'];
            $data['u_bannerProfileName']      = $this->getFileName($data['u_bannerProfile']);
            $data['u_bannerProfileDownload']  = $this->getDownloadPath($data['u_bannerProfile']);
            $data['u_bannerProfile']          = $this->getFilePath($data['u_bannerProfile']);
        }

        if (!empty($data['u_ktpFile'])) {
            $data['u_ktpFileRealPath']        = $data['u_ktpFile'];
            $data['u_ktpFileName']            = $this->getFileName($data['u_ktpFile']);
            $data['u_ktpFileDownload']        = $this->getDownloadPath($data['u_ktpFile']);
            $data['u_ktpFile']                = $this->getFilePath($data['u_ktpFile']);
        }

        if (!empty($data['s_sppkpFile'])) {
            $data['s_sppkpFile'] = $this->getParsedFiles($data['s_sppkpFile']);
        }

        if (!empty($data['u_suratIjin'])) {
            $data['u_suratIjin'] = $this->getParsedFiles($data['u_suratIjin']);
        }

        if (!empty($data['u_dokumenFile'])) {
            $data['u_dokumenFile'] = $this->getParsedFiles($data['u_dokumenFile']);
        }

        if (!empty($data['s_previousChanges'])) {
            $data['s_previousChanges'] = json_decode($data['s_previousChanges'], true);

            if (!empty($data['s_previousChanges']['s_deliveryCouriers'])) {
                $data['s_previousChanges']['s_deliveryCouriers'] = explode(',', $data['s_previousChanges']['s_deliveryCouriers']);
            }

            if (!empty($data['s_previousChanges']['u_ktpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_ktpFileRealPath']         = $data['s_previousChanges']['u_ktpFile'];
                $data['s_previousChanges']['u_ktpFileName']             = $this->getFileName($data['s_previousChanges']['u_ktpFile']);
                $data['s_previousChanges']['u_ktpFileDownload']         = $this->getDownloadPath($data['s_previousChanges']['u_ktpFile']);
                $data['s_previousChanges']['u_ktpFile']                 = $this->getFilePath($data['s_previousChanges']['u_ktpFile']);
            }

            if (!empty($data['s_previousChanges']['u_npwpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_npwpFileRealPath']        = $data['s_previousChanges']['u_npwpFile'];
                $data['s_previousChanges']['u_npwpFileName']            = $this->getFileName($data['s_previousChanges']['u_npwpFile']);
                $data['s_previousChanges']['u_npwpFileDownload']        = $this->getDownloadPath($data['s_previousChanges']['u_npwpFile']);
                $data['s_previousChanges']['u_npwpFile']                = $this->getFilePath($data['s_previousChanges']['u_npwpFile']);
            }

            if (!empty($data['s_previousChanges']['u_user_signature'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_user_signatureRealPath']  = $data['s_previousChanges']['u_user_signature'];
                $data['s_previousChanges']['u_user_signatureName']      = $this->getFileName($data['s_previousChanges']['u_user_signature']);
                $data['s_previousChanges']['u_user_signatureDownload']  = $this->getDownloadPath($data['s_previousChanges']['u_user_signature']);
                $data['s_previousChanges']['u_user_signature']          = $this->getFilePath($data['s_previousChanges']['u_user_signature']);
            }

            if (!empty($data['s_previousChanges']['u_user_stamp'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_user_stampRealPath']      = $data['s_previousChanges']['u_user_stamp'];
                $data['s_previousChanges']['u_user_stampName']          = $this->getFileName($data['s_previousChanges']['u_user_stamp']);
                $data['s_previousChanges']['u_user_stampDownload']      = $this->getDownloadPath($data['s_previousChanges']['u_user_stamp']);
                $data['s_previousChanges']['u_user_stamp']              = $this->getFilePath($data['s_previousChanges']['u_user_stamp']);
            }

            if (!empty($data['s_previousChanges']['s_rekeningFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['s_rekeningFileRealPath']    = $data['s_previousChanges']['s_rekeningFile'];
                $data['s_previousChanges']['s_rekeningFileName']        = $this->getFileName($data['s_previousChanges']['s_rekeningFile']);
                $data['s_previousChanges']['s_rekeningFileDownload']    = $this->getDownloadPath($data['s_previousChanges']['s_rekeningFile']);
                $data['s_previousChanges']['s_rekeningFile']            = $this->getFilePath($data['s_previousChanges']['s_rekeningFile']);
            }

            if (!empty($data['s_previousChanges']['s_sppkpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['s_sppkpFile'] = $this->getParsedFiles($data['s_previousChanges']['s_sppkpFile'], true);
            }

            if (!empty($data['s_previousChanges']['u_suratIjinFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_suratIjinFile'] = $this->getParsedFiles($data['s_previousChanges']['u_suratIjinFile'], true);
            }

            if (!empty($data['s_previousChanges']['u_dokumenFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_dokumenFile'] = $this->getParsedFiles($data['s_previousChanges']['u_dokumenFile'], true);
            }
        }

        $data['totalPreviousFiles'] = $totalPreviousFiles;
        $data['isAllowedToEdit'] = $this->isAuthorizedToManage();

        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');

        $data['productCategories'] = productCategoryConversionData($productCategories);

        if (empty($productCategories)) {
            $data['productCategories'] = [];
        }

        if (empty($data['s_productCategories'])) {
            $data['s_productCategories'] = [];
        } else {
            $data['s_productCategories'] = json_decode($data['s_productCategories']);
        }

        $this->checkAuthorizedAdminCabang($data['s_provinceId']);

        return $data;
    }

    protected function actSaveData(Request $request): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());
        $deliveryCouriers = (isset($formData['s_deliveryCouriers']) && is_array($formData['s_deliveryCouriers'])) ? $formData['s_deliveryCouriers'] : [];
        $isPKP = (isset($formData['s_isPKP']) && (int)$formData['s_isPKP'] === 1);
        $used_erzap = (isset($formData['s_isUsedErzap']) && (int)$formData['s_isUsedErzap'] === 1);

        // if (isset($formData['s_slug']) && !empty($formData['s_slug'])) {
        //     $formData['s_slug'] = (new Slugify())->slugify($formData['s_slug']);
        // }

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $store = new Store();
        $store->setName(filter_var($formData['s_name'], FILTER_SANITIZE_STRING));
        $store->setBrand(filter_var($formData['s_brand'], FILTER_SANITIZE_STRING));
        $store->setSlug(filter_var($formData['s_slug'], FILTER_SANITIZE_STRING));
        //$store->setColor(filter_var($formData['s_color'], FILTER_SANITIZE_STRING));
        //$store->setTheme(filter_var($formData['s_theme'], FILTER_SANITIZE_STRING));
        $store->setDescription($formData['s_description']);
        $store->setIsVerified((bool)$formData['s_isVerified']);
        $store->setAddress($formData['s_address']);
        $store->setCity(filter_var($formData['s_city'], FILTER_SANITIZE_STRING));
        $store->setCityId((int)$formData['s_cityId']);
        $store->setDistrict(filter_var($formData['s_district'], FILTER_SANITIZE_STRING));
        $store->setDistrictId(0);
        $store->setProvince(filter_var($formData['s_province'], FILTER_SANITIZE_STRING));
        $store->setProvinceId((int)$formData['s_provinceId']);
        $store->setCountry('ID');
        $store->setCountryId(0);
        $store->setPostCode(filter_var($formData['s_postCode'], FILTER_SANITIZE_STRING));
        $store->setDeliveryCouriers($deliveryCouriers);
        $store->setIsPKP($isPKP);
        $store->setIsUsedErzap($used_erzap);
        $store->setNote(filter_var($formData['s_note'], FILTER_SANITIZE_STRING));
        $store->setFileHash(StaticHelper::secureRandomCode(16));
        $store->setBusinessCriteria(filter_var($formData['s_businessCriteria'], FILTER_SANITIZE_STRING));
        $store->setTypeOfBusiness(filter_var($formData['s_typeOfBusiness'], FILTER_SANITIZE_STRING));
        $store->setModalUsaha(filter_var($formData['s_modalUsaha'], FILTER_SANITIZE_NUMBER_FLOAT));
        $store->setTotalManpower(filter_var($formData['s_totalManpower'], FILTER_SANITIZE_NUMBER_FLOAT));
        $store->setRekeningName(filter_var($formData['s_rekeningName'], FILTER_SANITIZE_STRING));
        $store->setBankName(filter_var($formData['s_bankName'], FILTER_SANITIZE_STRING));
        $store->setNomorRekening(filter_var($formData['s_nomorRekening'], FILTER_SANITIZE_STRING));
        $store->setPosition(filter_var($formData['s_position'], FILTER_SANITIZE_STRING));

        $store->setRegisteredNumber(filter_var($formData['s_registeredNumber'], FILTER_SANITIZE_STRING));
        $store->setTnc(2);

        if ($formData['s_status'] === 'ACTIVE') {
            $store->setStatus('ACTIVE');
            $store->setStatusLog('Merchant Aktif');
            $store->setIsActive(1);
        } else if ($formData['s_status'] === 'INACTIVE') {
            $store->setStatus('INACTIVE');
            $store->setStatusLog('Merchant Nonaktif');
            $store->setIsActive(0);
        } else if ($formData['s_status'] === 'NEW_MERCHANT') {
            $store->setStatus('NEW_MERCHANT');
            $store->setStatusLog('Pengajuan Data Usaha');
            $store->setIsActive(0);
        } else if ($formData['s_status'] === 'VERIFIED') {
            $store->setStatus('VERIFIED');
            $store->setStatusLog('Merchant Terverifikasi');
            $store->setIsActive(0);
        } else if ($formData['s_status'] === 'DRAFT') {
            $store->setStatus('DRAFT');
            $store->setStatusLog('Merchant Draft');
            $store->setIsActive(0);
        } else if ($formData['s_status'] === 'PENDING') {
            $store->setStatus('PENDING');
            $store->setStatusLog('Merchant Pending');
            $store->setIsActive(0);
        }

        if (isset($formData['u_id']) && !empty($formData['u_id'])) {
            $user = $this->validateUserToStoreAssignment(abs($formData['u_id']));

            if ($user) {
                $store->setUser($user);
            }
        }

        $user = $store->getUser();

        try {
            $newPath = 'users/' . $user->getDirSlug();
        } catch (\Throwable $e) {
            $message = $translator->trans('global.not_empty', [], 'validators');
            $errors = [];
            $errors['u_id'] = $message;

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));

            return $redirect;
        }

        $prefixPath = 'uploads/';
        $uploader = $this->get(FileUploader::class);
        $uploader->setTargetDirectory($newPath);

        if (!empty($formData['u_name'])) {
            $name = StaticHelper::splitFullName(filter_var($formData['u_name'], FILTER_SANITIZE_STRING));
            $user->setFirstName($name['first_name']);
            $user->setLastName($name['last_name']);
        }

        $user->setTnc(1);
        $user->setGender(filter_var($formData['u_gender'], FILTER_SANITIZE_STRING));
        $user->setNik(filter_var($formData['u_nik'], FILTER_SANITIZE_NUMBER_INT));
        $user->setNpwp(filter_var($formData['u_npwp'], FILTER_SANITIZE_STRING));
        $user->setNpwpName(filter_var($formData['u_npwpName'], FILTER_SANITIZE_STRING));
        $user->setEmail(filter_var($formData['u_email'], FILTER_SANITIZE_EMAIL));
        $user->setPhoneNumber(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_NUMBER_INT));

        if (isset($formData['u_dob'])) {
            try {
                $user->setDob(new DateTime($formData['u_dob']));
            } catch (Exception $e) {
            }
        }

        $userAddress = new UserAddress();
        $userAddress->setUser($user);
        $userAddress->setTitle('Alamat');
        $userAddress->setAddress($formData['s_address']);
        $userAddress->setPostCode($formData['s_postCode']);
        $userAddress->setCity($formData['s_city']);
        $userAddress->setCityId(abs($formData['s_cityId']));
        $userAddress->setDistrict($formData['s_district']);
        $userAddress->setDistrictId(0);
        $userAddress->setProvince($formData['s_province']);
        $userAddress->setProvinceId(abs($formData['s_provinceId']));
        $userAddress->setCountry('ID');
        $userAddress->setCountryId(0);

        if (!empty($request->files->get('u_photoProfile'))) {
            $uploadedFile = $request->files->get('u_photoProfile');
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
            $user->setPhotoProfile($filePath);
        }

        if (!empty($request->files->get('u_photoBanner'))) {
            $uploadedFile = $request->files->get('u_photoBanner');
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
            $user->setBannerProfile($filePath);
        }

        if (!empty($request->files->get('u_ktpFile'))) {
            $uploadedFile = $request->files->get('u_ktpFile');
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
            $user->setKtpFile($filePath);
        }

        if (!empty($request->files->get('u_suratIjin'))) {
            $uploadedFiles = $request->files->get('u_suratIjin');
            $files = [];
            if (count($uploadedFiles) > 0) {
                foreach ($uploadedFiles as $file) {
                    $files[] = $prefixPath . $uploader->upload($file, false);
                }

                $user->setSuratIjinFile($files);
            }
        }

        if (!empty($request->files->get('u_dokumenFile'))) {
            $uploadedFiles = $request->files->get('u_dokumenFile');
            $files = [];
            if (count($uploadedFiles) > 0) {
                foreach ($uploadedFiles as $file) {
                    $files[] = $prefixPath . $uploader->upload($file, false);
                }
                $user->setDokumenFile($files);
            }
        }

        if (!empty($request->files->get('u_npwpFile'))) {
            $uploadedFile = $request->files->get('u_npwpFile');
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
            $user->setNpwpFile($filePath);
        }


        if (!empty($request->files->get('s_rekeningFile'))) {
            $uploadedFile = $request->files->get('s_rekeningFile');
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);
            $store->setRekeningFile($filePath);
        }

        if (!empty($request->files->get('s_sppkpFile'))) {
            $uploadedFiles = $request->files->get('s_sppkpFile');
            $files = [];
            if (count($uploadedFiles) > 0) {
                foreach ($uploadedFiles as $file) {
                    $files[] = $prefixPath . $uploader->upload($file, false);
                }

                $store->setSppkpFile($files);
            }
        }

        $storeInput = [
            'name' => $formData['s_name'],
            'brand' => $formData['s_brand'],
            'slug' => $formData['s_slug'],
            'description' => $formData['s_description'],
            'status' => $formData['s_status'],
            'isVerified' => $formData['s_isVerified'] === 1 ? 1 : -1,
            'typeOfBusiness' => $formData['s_typeOfBusiness'],
            'businessCriteria' => $formData['s_businessCriteria'],
            'position' => $formData['s_position'],
            'modalUsaha' => $formData['s_modalUsaha'],
            'totalManpower' => $formData['s_totalManpower'],
            'nomorRekening' => $formData['s_nomorRekening'],
            'bankName' => $formData['s_bankName'],
            'rekeningName' => $formData['s_rekeningName'],
            'rekeningFile' => $store->getRekeningFile(),
            'district' => $formData['s_district'],
        ];

        $userInput = [
            'npwp' => $formData['u_npwp'],
            'nik' => $formData['u_nik'],
            'email' => $formData['u_email'],
            'phoneNumber' => $formData['u_phoneNumber'],
            'dob' => $formData['u_dob'],
            'ktpFile' => $user->getKtpFile(),
            'suratIjin' => $user->getSuratIjinFile(),
            'npwpFile' => $user->getNpwpFile(),
        ];

        $validator = $this->getValidator();
        $storeErrors = $validator->validate($store);
        $userErrors = $validator->validate($user);

        if ($isPKP && empty($store->getSppkpFile())) {
            $message = $translator->trans('global.not_empty', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $store, 'sppkpFile', '', null, null, new Assert\NotBlank(), null);

            $storeErrors->add($constraint);
        }

        if (count($deliveryCouriers) < 1) {
            $message = $translator->trans('global.not_empty', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $store, 'deliveryCouriers', '', null, null, new Assert\NotBlank(), null);

            $storeErrors->add($constraint);
        }

        foreach ($storeInput as $key => $item) {
            if (empty($item)) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $store, $key, '', null, null, new Assert\NotBlank(), null);
                $storeErrors->add($constraint);
            }
        }

        foreach ($userInput as $key => $item) {
            if (empty($item)) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $store, $key, '', null, null, new Assert\NotBlank(), null);
                $userErrors->add($constraint);
            }
        }

        if (count($storeErrors) === 0 && count($userErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($store);
            $em->flush();

            $em->persist($user);
            $em->flush();

            $em->persist($userAddress);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.store_created', ['%name%' => $store->getName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $store->getId()]);
            }

            $this->removeUserStoresDataFromCache();
        } else {
            $errors = [];

            foreach ($storeErrors as $error) {
                $path = !empty($error->getPropertyPath()) ? $error->getPropertyPath() : 'name';
                $errors['s_' . $path] = $error->getMessage();
            }

            foreach ($userErrors as $error) {
                $path = !empty($error->getPropertyPath()) ? $error->getPropertyPath() : 'name';
                $errors['u_' . $path] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    protected function actEditData(int $id)
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var StoreRepository $repository */
        $repository = $this->getRepository($this->entity);
        $data = $repository->getDataWithOwnerById($id);
        $totalPreviousFiles = 0;

        if (!empty($data['s_rekeningFile'])) {
            $data['s_rekeningFileName'] = $this->getFileName($data['s_rekeningFile']);
            $data['s_rekeningFile'] = $this->getFilePath($data['s_rekeningFile']);
        }

        if (!empty($data['u_npwpFile'])) {
            $data['u_npwpFileName'] = $this->getFileName($data['u_npwpFile']);
            $data['u_npwpFile'] = $this->getFilePath($data['u_npwpFile']);
        }

        if (!empty($data['u_user_signature'])) {
            $data['u_user_signatureName'] = $this->getFileName($data['u_user_signature']);
            $data['u_user_signature'] = $this->getFilePath($data['u_user_signature']);
        }

        if (!empty($data['u_user_stamp'])) {
            $data['u_user_stampName'] = $this->getFileName($data['u_user_stamp']);
            $data['u_user_stamp'] = $this->getFilePath($data['u_user_stamp']);
        }

        if (!empty($data['s_sppkpFile'])) {
            $data['s_sppkpFile'] = $this->getParsedFiles($data['s_sppkpFile']);
        }

        if (!empty($data['u_suratIjin'])) {
            $data['u_suratIjin'] = $this->getParsedFiles($data['u_suratIjin']);
        }

        if (!empty($data['u_dokumenFile'])) {
            $data['u_dokumenFile'] = $this->getParsedFiles($data['u_dokumenFile']);
        }

        if (empty($data['s_registeredNumber'])) {
            $date = $data['s_createdAt'];
            $counter = $repository->getTotalRegisteredMerchantByDate($date);
            $counter += 1;

            if ($counter < 10) {
                $counter = '0' . (string)$counter;
            }

            $registerNumber = 'Nomor: ' . $date->format('d') . $counter . '/BUS-SKsp.Mc/' . $date->format('Y');

            $data['s_registeredNumber'] = $registerNumber;
        }

        if (!empty($data['u_photoProfile'])) {
            $data['u_photoProfileName'] = $this->getFileName($data['u_photoProfile']);
            $data['u_photoProfile'] = $this->getFilePath($data['u_photoProfile']);
        }

        if (!empty($data['u_bannerProfile'])) {
            $data['u_bannerProfileName'] = $this->getFileName($data['u_bannerProfile']);
            $data['u_bannerProfile'] = $this->getFilePath($data['u_bannerProfile']);
        }

        if (!empty($data['u_ktpFile'])) {
            $data['u_ktpFileName'] = $this->getFileName($data['u_ktpFile']);
            $data['u_ktpFile'] = $this->getFilePath($data['u_ktpFile']);
        }

        if (!empty($data['s_previousChanges'])) {
            $data['s_previousChanges'] = json_decode($data['s_previousChanges'], true);

            if (!empty($data['s_previousChanges']['s_deliveryCouriers'])) {
                $data['s_previousChanges']['s_deliveryCouriers'] = explode(',', $data['s_previousChanges']['s_deliveryCouriers']);
            }

            if (!empty($data['s_previousChanges']['u_ktpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_ktpFileName'] = $this->getFileName($data['s_previousChanges']['u_ktpFile']);
                $data['s_previousChanges']['u_ktpFileDownload'] = $this->getDownloadPath($data['s_previousChanges']['u_ktpFile']);
                $data['s_previousChanges']['u_ktpFile'] = $this->getFilePath($data['s_previousChanges']['u_ktpFile']);
            }

            if (!empty($data['s_previousChanges']['u_npwpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_npwpFileName'] = $this->getFileName($data['s_previousChanges']['u_npwpFile']);
                $data['s_previousChanges']['u_npwpFileDownload'] = $this->getDownloadPath($data['s_previousChanges']['u_npwpFile']);
                $data['s_previousChanges']['u_npwpFile'] = $this->getFilePath($data['s_previousChanges']['u_npwpFile']);
            }

            if (!empty($data['s_previousChanges']['u_user_signature'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_user_signatureName'] = $this->getFileName($data['s_previousChanges']['u_user_signature']);
                $data['s_previousChanges']['u_user_signature'] = $this->getFilePath($data['s_previousChanges']['u_user_signature']);
            }

            if (!empty($data['s_previousChanges']['u_user_stamp'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_user_stampName'] = $this->getFileName($data['s_previousChanges']['u_user_stamp']);
                $data['s_previousChanges']['u_user_stamp'] = $this->getFilePath($data['s_previousChanges']['u_user_stamp']);
            }

            if (!empty($data['s_previousChanges']['s_rekeningFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['s_rekeningFileName'] = $this->getFileName($data['s_previousChanges']['s_rekeningFile']);
                $data['s_previousChanges']['s_rekeningFileDownload'] = $this->getDownloadPath($data['s_previousChanges']['s_rekeningFile']);
                $data['s_previousChanges']['s_rekeningFile'] = $this->getFilePath($data['s_previousChanges']['s_rekeningFile']);
            }

            if (!empty($data['s_previousChanges']['s_sppkpFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['s_sppkpFile'] = $this->getParsedFiles($data['s_previousChanges']['s_sppkpFile'], true);
            }

            if (!empty($data['s_previousChanges']['u_suratIjinFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_suratIjinFile'] = $this->getParsedFiles($data['s_previousChanges']['u_suratIjinFile'], true);
            }

            if (!empty($data['s_previousChanges']['u_dokumenFile'])) {
                $totalPreviousFiles++;
                $data['s_previousChanges']['u_dokumenFile'] = $this->getParsedFiles($data['s_previousChanges']['u_dokumenFile'], true);
            }
        }

        $data['totalPreviousFiles'] = $totalPreviousFiles;

        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');

        $data['productCategories'] = productCategoryConversionData($productCategories);

        if (empty($productCategories)) {
            $data['productCategories'] = [];
        }

        if (empty($data['s_productCategories'])) {
            $data['s_productCategories'] = [];
        } else {
            $data['s_productCategories'] = json_decode($data['s_productCategories']);
        }

        $this->checkAuthorizedAdminCabang($data['s_provinceId']);

        return $data;
    }

    protected function getDownloadPath($file): string
    {
        // dd($this->generateUrl('admin_store_download', [[], 'path' => $file], UrlGeneratorInterface::ABSOLUTE_URL));
        return $this->generateUrl('admin_store_download', [[], 'path' => $file], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function getFileName($filename): string
    {
        $filename = explode('/', $filename);
        return end($filename);
    }

    protected function getFilePath($filename): string
    {
        return $this->getRequest()->getUriForPath('/' . $filename);
    }

    protected function getParsedFiles($files, $split = false): array
    {
        if ($split) {
            $files = explode(',', $files);
        } else {
            $files = json_decode($files);
        }

        $tmp = [];
        if (count($files) > 0) {
            foreach ($files as $i => $file) {
                $tmp[$i]['realPath'] = $file;
                $tmp[$i]['filename'] = $this->getFileName($file);
                $tmp[$i]['path'] = $this->getFilePath($file);
                $tmp[$i]['download'] = $this->getDownloadPath($file);
            }
        }
        return $tmp;
    }

    protected function actUpdateData(Request $request, $id): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        /** @var Store $store */
        $store = $this->getRepository($this->entity)->find($id);
        $user = $store->getUser();
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);
        $translator = $this->getTranslator();

        /** @var DisbursementRepository $repository */
        $d_repo = $this->getRepository(Disbursement::class);

        $em = $this->getEntityManager();

        if ($store instanceof Store) {

            $this->checkAuthorizedAdminCabang($store->getProvinceId());

            $deliveryCouriers = (isset($formData['s_deliveryCouriers']) && is_array($formData['s_deliveryCouriers'])) ? $formData['s_deliveryCouriers'] : [];
            $productCategories = (isset($formData['s_productCategories']) && is_array($formData['s_productCategories'])) ? $formData['s_productCategories'] : [];
            $isPKP = (isset($formData['s_isPKP']) && (int)$formData['s_isPKP'] === 1);
            $used_erzap = (isset($formData['s_isUsedErzap']) && (int)$formData['s_isUsedErzap'] === 1);
            $dirSlug = $store->getUser()->getDirSlug();
            $newPath = 'users/' . $dirSlug;
            $publicDir = $this->getParameter('public_dir_path') . '/';
            $deleted = [];
            $prefixPath = 'uploads/';
            $uploader = $this->get(FileUploader::class);
            $uploader->setTargetDirectory($newPath);

            $diff = $this->getStoreDiff($store, $formData, [
                'user_id' => (int)$user->getId(),
                'origin' => 'BE',
            ]);

            if ($formData['s_status'] === 'ACTIVE' && $store->getStatus() === 'UPDATE') {
                $previousChanges = $store->getPreviousChanges();

                if (!empty($previousChanges['u_photoProfile'])) {
                    $deleted[] = $previousChanges['u_photoProfile'];
                }

                if (!empty($previousChanges['u_bannerProfile'])) {
                    $deleted[] = $previousChanges['u_bannerProfile'];
                }

                if (!empty($previousChanges['s_rekeningFile'])) {
                    $deleted[] = $previousChanges['s_rekeningFile'];
                }

                if (!empty($previousChanges['u_npwpFile'])) {
                    $deleted[] = $previousChanges['u_npwpFile'];
                }

                if (!empty($previousChanges['u_ktpFile'])) {
                    $deleted[] = $previousChanges['u_ktpFile'];
                }

//                if (!empty($previousChanges['s_sppkpFile'])) {
//                    $files = explode(',', $previousChanges['s_sppkpFile']);
//                    if (count($files) > 0) {
//                        foreach ($files as $file) {
//                            $deleted[] = $file;
//                        }
//                    }
//                }
//
//                if (!empty($previousChanges['u_suratIjinFile'])) {
//                    $files = explode(',', $previousChanges['u_suratIjinFile']);
//                    if (count($files) > 0) {
//                        foreach ($files as $file) {
//                            $deleted[] = $file;
//                        }
//                    }
//                }
//
//                if (!empty($previousChanges['u_dokumenFile'])) {
//                    $files = explode(',', $previousChanges['u_dokumenFile']);
//                    if (count($files) > 0) {
//                        foreach ($files as $file) {
//                            $deleted[] = $file;
//                        }
//                    }
//                }
            }

            if (count($diff['diff']) > 0) {
                if ($store->getStatus() !== 'PENDING') {
                    $store->setPreviousValues($diff['data']);
                    $store->setPreviousChanges($diff['diff']);
                }
            }

            // if (isset($formData['s_slug']) && !empty($formData['s_slug'])) {
            //     $formData['s_slug'] = (new Slugify())->slugify($formData['s_slug']);
            // }

            // Skip updating owner & slug
            $store->setName(filter_var($formData['s_name'], FILTER_SANITIZE_STRING));
            $store->setBrand(filter_var($formData['s_brand'], FILTER_SANITIZE_STRING));
            $store->setSlug(filter_var($formData['s_slug'], FILTER_SANITIZE_STRING));
            //$store->setColor(filter_var($formData['s_color'], FILTER_SANITIZE_STRING));
            //$store->setTheme(filter_var($formData['s_theme'], FILTER_SANITIZE_STRING));
            $store->setDescription($formData['s_description']);
            $store->setIsVerified((bool)$formData['s_isVerified']);
            $store->setAddress($formData['s_address']);
            $store->setCity(filter_var($formData['s_city'], FILTER_SANITIZE_STRING));
            $store->setCityId((int)$formData['s_cityId']);
            $store->setDistrict(filter_var($formData['s_district'], FILTER_SANITIZE_STRING));
            $store->setDistrictId(0);
            $store->setProvince(filter_var($formData['s_province'], FILTER_SANITIZE_STRING));
            $store->setProvinceId((int)$formData['s_provinceId']);
            $store->setCountry('ID');
            $store->setCountryId(0);
            $store->setPostCode(filter_var($formData['s_postCode'], FILTER_SANITIZE_STRING));
            $store->setDeliveryCouriers($deliveryCouriers);
            $store->setIsPKP($isPKP);
            $store->setIsUsedErzap($used_erzap);
            $store->setNote(filter_var($formData['s_note'], FILTER_SANITIZE_STRING));
            $store->setFileHash(filter_var($formData['s_fileHash'], FILTER_SANITIZE_STRING));
            $store->setPosition(filter_var($formData['s_position'], FILTER_SANITIZE_STRING));
            $store->setProductCategories($productCategories);
            $store->setIdTayang(filter_var($formData['s_id_tayang'], FILTER_SANITIZE_STRING));

            $store->getUser()->setEmail(filter_var($formData['u_email'], FILTER_SANITIZE_EMAIL));
            $store->getUser()->setPhoneNumber(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_NUMBER_INT));


            if (!empty($formData['u_name'])) {
                $name = StaticHelper::splitFullName(filter_var($formData['u_name'], FILTER_SANITIZE_STRING));
                $user->setFirstName($name['first_name']);
                $user->setLastName($name['last_name']);
            }

            $user->setTnc(1);
            $user->setGender(filter_var($formData['u_gender'], FILTER_SANITIZE_STRING));
            $user->setNik(filter_var($formData['u_nik'], FILTER_SANITIZE_NUMBER_INT));
            $user->setNpwp(filter_var($formData['u_npwp'], FILTER_SANITIZE_STRING));
            $user->setEmail(filter_var($formData['u_email'], FILTER_SANITIZE_EMAIL));
            $user->setPhoneNumber(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_NUMBER_INT));

            if (isset($formData['u_dob'])) {
                try {
                    $user->setDob(new DateTime($formData['u_dob']));
                } catch (Exception $e) {
                }
            }

            //update disbursement yg pending
            $d_pending = $d_repo->getDataByStoreId($store->getId(), ['status' => 'pending']);
            foreach ($d_pending as $key => $value) {
                $d_update = $d_repo->find($value['id']);
                $d_update->setRekeningName(filter_var($formData['s_rekeningName'], FILTER_SANITIZE_STRING));
                $d_update->setBankName(filter_var($formData['s_bankName'], FILTER_SANITIZE_STRING));
                $d_update->setNomorRekening(filter_var($formData['s_nomorRekening'], FILTER_SANITIZE_STRING));
                $em->persist($d_update);
                $em->flush();
            }

            if (!$isPKP) {
                $files = $store->getSppkpFile();
                if (count($files) > 0) {
                    foreach ($files as $file) {
                        $deleted[] = $file;
                    }
                }

                $store->setSppkpFile();
            }

            if ($formData['s_status'] === 'ACTIVE' || $formData['s_status'] === 'UPDATE') {
                $store->setIsActive(1);
            } else {
                $store->setIsActive(0);
            }

            $store->setStatus(filter_var($formData['s_status'], FILTER_SANITIZE_STRING));

            $statusDesc = null;

            if ($store->getStatus() === 'VERIFIED') {
                $statusDesc = 'Merchant Terverifikasi';
            } elseif ($store->getStatus() === 'ACTIVE') {
                $statusDesc = 'Merchant Aktif';
            } elseif ($store->getStatus() === 'PENDING') {
                $statusDesc = 'Verifikasi Pending: ' . $store->getNote();
            } elseif ($store->getStatus() === 'INACTIVE') {
                $statusDesc = 'Merchant Nonaktif';
            } elseif ($store->getStatus() === 'UPDATE') {
                $statusDesc = 'Update Data';
            } else if ($store->getStatus() === 'NEW_MERCHANT') {
                $statusDesc = 'New Merchant';
            } else {
                $statusDesc = 'Merchant Draft';
            }

            $store->setStatusLog($statusDesc);

            if ($formData['s_status'] !== 'PENDING') {
                if (!empty($store->getStatus())) {
                    $store->setNote('');
                }
            }

            $store->setModalUsaha((float)$formData['s_modalUsaha']);
            $store->setTotalManpower(filter_var($formData['s_totalManpower'], FILTER_SANITIZE_NUMBER_INT));
            $store->setRekeningName(filter_var($formData['s_rekeningName'], FILTER_SANITIZE_STRING));
            $store->setBankName(filter_var($formData['s_bankName'], FILTER_SANITIZE_STRING));
            $store->setNomorRekening(filter_var($formData['s_nomorRekening'], FILTER_SANITIZE_NUMBER_INT));
            $store->setTypeOfBusiness(filter_var($formData['s_typeOfBusiness'], FILTER_SANITIZE_STRING));
            $store->setRegisteredNumber(filter_var($formData['s_registeredNumber'], FILTER_SANITIZE_STRING));
            $store->setBusinessCriteria(filter_var($formData['s_businessCriteria'], FILTER_SANITIZE_STRING));

            $name = StaticHelper::splitFullName(filter_var($formData['u_name'], FILTER_SANITIZE_STRING));
            $store->getUser()->setFirstName($name['first_name']);
            $store->getUser()->setLastName($name['last_name']);

            $store->getUser()->setNik(filter_var($formData['u_nik'], FILTER_SANITIZE_NUMBER_INT));
            $store->getUser()->setGender(filter_var($formData['u_gender'], FILTER_SANITIZE_STRING));

            try {
                $store->getUser()->setDob(new DateTime($formData['u_dob']));
            } catch (\Exception $exception) {
            }

            if (!empty($request->files->get('u_photoProfile'))) {
                $uploadedFile = $request->files->get('u_photoProfile');
                $oldFile = explode('/', $store->getUser()->getPhotoProfile());
                $uploader->setOldFilePath(end($oldFile));

                $store->getUser()->setPhotoProfile($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('u_photoBanner'))) {
                $uploadedFile = $request->files->get('u_photoBanner');
                $oldFile = explode('/', $store->getUser()->getBannerProfile());

                $uploader->setOldFilePath(end($oldFile));
                $store->getUser()->setBannerProfile($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('u_ktpFile'))) {
                $uploadedFile = $request->files->get('u_ktpFile');
                $oldFile = explode('/', $store->getUser()->getKtpFile());

                $uploader->setOldFilePath(end($oldFile));
                $store->getUser()->setKtpFile($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('s_rekeningFile'))) {
                $uploadedFile = $request->files->get('s_rekeningFile');
                $oldFile = explode('/', $store->getRekeningFile());

                $uploader->setOldFilePath(end($oldFile));
                $store->setRekeningFile($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('u_npwpFile'))) {
                $uploadedFile = $request->files->get('u_npwpFile');
                $oldFile = explode('/', $store->getUser()->getNpwpFile());

                $uploader->setOldFilePath(end($oldFile));
                $store->getUser()->setNpwpFile($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('u_user_signature'))) {
                $uploadedFile = $request->files->get('u_user_signature');
                $oldFile = explode('/', $store->getUser()->getUserSignature());

                $uploader->setOldFilePath(end($oldFile));
                $store->getUser()->setUserSignature($prefixPath . $uploader->upload($uploadedFile, false, true));
            }

            if (!empty($request->files->get('u_user_stamp'))) {
                $uploadedFile = $request->files->get('u_user_stamp');
                $oldFile = explode('/', $store->getUser()->getUserStamp());

                $uploader->setOldFilePath(end($oldFile));
                $store->getUser()->setUserStamp($prefixPath . $uploader->upload($uploadedFile, false, true));
            }


            if (!empty($request->files->get('s_sppkpFile'))) {
                $uploadedFiles = $request->files->get('s_sppkpFile');
                $oldFiles = $store->getSppkpFile();
                $files = [];
                if (is_array($uploadedFiles) && count($uploadedFiles) > 0) {

                    if (count($oldFiles) > 0) {
                        foreach ($oldFiles as $oldFile) {
                            $deleted[] = $publicDir . $oldFile;
                        }
                    }

                    foreach ($uploadedFiles as $file) {
                        $files[] = $prefixPath . $uploader->upload($file);
                    }

                    $store->setSppkpFile($files);
                }
            }

            if (!empty($request->files->get('u_suratIjin'))) {
                $uploadedFiles = $request->files->get('u_suratIjin');
                $oldFiles = $store->getUser()->getSuratIjinFile();
                $files = [];
                if (is_array($uploadedFiles) && count($uploadedFiles) > 0) {

                    if (count($oldFiles) > 0) {
                        foreach ($oldFiles as $oldFile) {
                            $deleted[] = $publicDir . $oldFile;
                        }
                    }

                    foreach ($uploadedFiles as $file) {
                        $files[] = $prefixPath . $uploader->upload($file);
                    }

                    $store->getUser()->setSuratIjinFile($files);
                }
            }

            if (!empty($request->files->get('u_dokumenFile'))) {
                $uploadedFiles = $request->files->get('u_dokumenFile');
                $oldFiles = $store->getUser()->getDokumenFile();
                $files = [];
                if (is_array($uploadedFiles) && count($uploadedFiles) > 0) {

                    if (count($oldFiles) > 0) {
                        foreach ($oldFiles as $oldFile) {
                            $deleted[] = $publicDir . $oldFile;
                        }
                    }

                    foreach ($uploadedFiles as $file) {
                        $files[] = $prefixPath . $uploader->upload($file);
                    }

                    $store->getUser()->setDokumenFile($files);
                }
            }

            foreach ($deleted as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            if (isset($formData['u_id']) && !empty($formData['u_id'])) {
                if ($this->overwriteOwner || empty($store->getUser())) {
                    if ($user = $this->validateUserToStoreAssignment(abs($formData['u_id']))) {
                        $store->setUser($user);
                    }
                }
            }

            $validator = $this->getValidator();
            $storeErrors = $validator->validate($store);
            $userErrors = $validator->validate($user);

            if ($store->getStatus() === 'PENDING' && empty($store->getNote())) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $store, 'note', '', null, null, new Assert\NotBlank(), null);

                $storeErrors->add($constraint);
            }

            if (count($deliveryCouriers) < 1) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $store, 'deliveryCouriers', '', null, null, new Assert\NotBlank(), null);

                $storeErrors->add($constraint);
            }

            if (count($storeErrors) === 0) {

                $em->persist($store);
                $em->flush();


                $createdAt = date_format($store->getCreatedAt(), 'dmy');
                $shop_id = 'BM-'.$createdAt.'-'.$store->getId();
                $store->setShopId($shop_id);
                $em->persist($store);
                $em->flush();

                $em->persist($user);
                $em->flush();

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.store_updated', ['%name%' => $store->getName()])
                );

                if ($formData['btn_action'] === 'save_exit') {
                    $redirect = $this->generateUrl($this->getAppRoute());
                }

                $this->removeUserStoresDataFromCache();
            } else {
                $errors = [];

                foreach ($storeErrors as $error) {
                    $errors['s_' . $error->getPropertyPath()] = $error->getMessage();
                }

                foreach ($userErrors as $error) {
                    $errors['u_' . $error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);

            }
        }

        return $redirect;
    }

    protected function actDeleteData(): array
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $request = $this->getRequest();
        $storeId = abs($request->request->get('store', '0'));
        /** @var StoreRepository $repository */
        $repository = $this->getRepository($this->entity);
        /** @var Store $store */
        $store = $repository->find($storeId);
        $translator = $this->getTranslator();
        $response = [
            'status' => false,
            'message' => $translator->trans('message.error.delete', ['%name%' => 'store']),
        ];

        if ($store instanceof Store) {
            $this->checkAuthorizedAdminCabang($store->getProvinceId());

            $storeName = $store->getName();

            $em = $this->getEntityManager();
            $em->remove($store);
            $em->flush();

            $this->removeUserStoresDataFromCache();

            $response['status'] = true;
            $response['message'] = $translator->trans('message.success.delete', ['%name%' => $storeName]);
        }

        return $response;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $action = $request->request->get('btn_action', 'invalid');
        $stores = [];
        $proceed = false;
        $sql = null;
        /** @var StoreRepository $repository */
        $repository = $this->getRepository($this->entity);
        $now = new DateTime('now');

        if ($this->isAuthorizedToManage()) {
            foreach ($ids as $key => $id) {
                $id = abs($id);
                $ids[$key] = $id;

                $store = $repository->find($id);

                if ($store instanceof Store) {
                    $stores[] = $store->getName();
                }
            }

            switch ($action) {
                case 'delete':
                    $sql = 'DELETE from App\Entity\Store t WHERE t.id IN (%s)';
                    $sql = sprintf($sql, implode(', ', $ids));
                    $proceed = true;
                    break;
                case 'activate':
                    $sql = 'UPDATE App\Entity\Store t SET t.isActive = 1, t.status = \'%s\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                    $sql = sprintf($sql, 'ACTIVE', $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                    $proceed = true;
                    break;
                case 'deactivate':
                    $sql = 'UPDATE App\Entity\Store t SET t.isActive = 0, t.status = \'%s\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                    $sql = sprintf($sql, 'INACTIVE', $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                    $proceed = true;
                    break;
            }

            if ($proceed) {
                /** @var EntityManager $em */
                $em = $this->getEntityManager();
                $query = $em->createQuery($sql);
                $query->execute();

                $this->removeUserStoresDataFromCache();

                $success = sprintf('message.success.%s', $action);

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans($success, ['%name%' => implode(', ', $stores)])
                );
            }
        } else {
            $this->addFlash('error', $this->getTranslator()->trans('message.error.403'));
        }
    }

    private function validateUserToStoreAssignment(int $userId)
    {
        $user = $this->getRepository(User::class)->findOneBy([
            'id' => $userId,
            'isActive' => true,
            'role' => 'ROLE_USER_SELLER',
        ]);

        if ($user instanceof User) {
            /** @var StoreRepository $repository */
            $repository = $this->getRepository(Store::class);
            $exist = $repository->count(['user' => $user]);

            // One user <==> One store
            if ($exist < 1) {
                return $user;
            }
        }

        return false;
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $status = array_values($this->getParameter('store_status'));

        $umkm = $this->getParameter('business_criteria');
        $business_criteria = [];
        foreach ($umkm as $key => $value) {
            $business_criteria[] = strtolower($key);
        }

        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'id_tayang' => [
                'type' => 'text',
            ],
            'status' => [
                'type' => 'select',
                'choices' => $status,
                'multiple' => true,
            ],
            'verified' => [
                'type' => 'select',
                'choices' => $this->getParameter('verified_unverified'),
            ],
            'business_criteria' => [
                'type' => 'select',
                'choices' => $business_criteria,
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
            'jump_to_page' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'id_tayang', 'name', 'owned_by', 'status', 'umkm_category', 'verified', 'created', 'updated', 'actions']);
    }

    private function removeUserStoresDataFromCache(): void
    {
        try {
            $cache = $this->getCache();
            $cache->deleteItem('app_user_store');
        } catch (InvalidArgumentException $e) {
        }
    }

    public function changeOwner($id)
    {
        $request = $this->getRequest();
        /** @var Store $store */
        $store = $this->getRepository($this->entity)->find($id);
        $flashBag = $this->get('session.flash_bag');
        $translator = $this->getTranslator();
        /** @var User $admin */
        $admin = $this->getUser();

        if (!$store instanceof Store) {
            throw $this->createNotFoundException($translator->trans('message.error.404'));
        }

        if ($request->isMethod('POST')) {
            $redirect = $this->getAppRoute('edit');
            $newOwner = abs($request->request->get('owner_id', '0'));
            $changeReason = $request->request->get('reason', null);
            /** @var User $currentOwner */
            $currentOwner = $store->getUser();
            $previousOwner = $currentOwner->getId();

            $storeLog = new StoreOwnerLog();
            $storeLog->setStoreId($store->getId());
            $storeLog->setCurrentOwner($newOwner);
            $storeLog->setPreviousOwner($previousOwner);
            $storeLog->setUpdatedBy($admin->getId());
            $storeLog->setReason(filter_var($changeReason, FILTER_SANITIZE_STRING));

            $validator = $this->getValidator();
            $storeLogErrors = $validator->validate($storeLog);

            if (count($storeLogErrors) === 0) {
                if ($newOwner > 0
                    && $previousOwner > 0
                    && $newOwner !== $previousOwner
                    && $user = $this->validateUserToStoreAssignment($newOwner)) {
                    $store->setUser($user);

                    $em = $this->getEntityManager();
                    $em->persist($store);
                    $em->persist($storeLog);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        $translator->trans('message.success.store_updated', ['%name%' => $store->getName()])
                    );

                    $this->removeUserStoresDataFromCache();
                }
            } else {
                $errors = [];
                $redirect = $this->getAppRoute('change_owner');

                foreach ($storeLogErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);
            }

            return $this->redirectToRoute($redirect, ['id' => $id]);
        }

        $histories = [];
        $section = 'store_owner_change';

        if ($admin->getRole() === 'ROLE_SUPER_ADMIN') {
            /** @var StoreOwnerLogRepository $repository */
            $repository = $this->getRepository(StoreOwnerLog::class);
            $histories = $repository->getLogHistoriesByStore($store->getId());
        }

        return $this->view('@__main__/admin/store/change_owner.html.twig', [
            'page_title' => sprintf('title.page.%s', $section),
            'token_id' => $section,
            'histories' => $histories,
            'form_data' => $this->actReadData($id),
            'errors' => $flashBag->get('errors'),
        ]);
    }

    protected function manipulateDataPackage(): void
    {
        if (!$this->isAuthorizedToManage()) {
            $this->dataPackage->setAbleToCreate(false);
        }

        $this->dataPackage->setAbleToExport(true);
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        $data = $repository->getDataToExport($parameters);
        $writer = null;

        /** @var ProductRepository $productRepository */
        $productRepository = $this->getRepository(Product::class);

        if (count($data['data']) > 0) {
            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Name');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Slug');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Owner');
            $sheet->setCellValueByColumnAndRow(6, 1, 'NIK');
            $sheet->setCellValueByColumnAndRow(7, 1, 'NPWP');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Description');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Address');
            $sheet->setCellValueByColumnAndRow(10, 1, 'District');
            $sheet->setCellValueByColumnAndRow(11, 1, 'City');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Province');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Post Code');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(15, 1, 'Exclusive Merchant');
            $sheet->setCellValueByColumnAndRow(16, 1, 'Is PKP');
            $sheet->setCellValueByColumnAndRow(17, 1, 'Delivery Couriers');
            $sheet->setCellValueByColumnAndRow(18, 1, 'Categories');
            $sheet->setCellValueByColumnAndRow(19, 1, 'No Rekening');
            $sheet->setCellValueByColumnAndRow(20, 1, 'Phone');
            $sheet->setCellValueByColumnAndRow(21, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(22, 1, 'Updated At');

            foreach ($data['data'] as $item) {
                $deliveryCouriers = !empty($item['s_deliveryCouriers']) ? json_decode($item['s_deliveryCouriers'], true) : [];

                $categoryNames = array();
                $dataCategory = $productRepository->getProductsCategoryByStore($item['s_id']);
                foreach ($dataCategory as $category) {
                    $categoryNames[] = $category['category_name'];
                }

                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['s_id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['s_name']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['s_slug']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), trim($item['u_firstName'] . ' ' . $item['u_lastName']));
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), ($item['u_nik']));
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['u_npwp']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $this->removeHtmlTags($item['s_description']));
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['s_address']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['s_district']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['s_city']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['s_province']);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['s_postCode']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['s_isActive'] ? 'Active' : 'Inactive');
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['s_isVerified'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['s_isPKP'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), implode(', ', $deliveryCouriers));
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), implode(', ', $categoryNames));
                $sheet->setCellValueByColumnAndRow(19, ($number + 1), $item['s_nomorRekening']);
                $sheet->setCellValueByColumnAndRow(20, ($number + 1), $item['u_phoneNumber']);
                $sheet->setCellValueByColumnAndRow(21, ($number + 1), $item['s_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(22, ($number + 1), !empty($item['s_updatedAt']) ? $item['s_updatedAt']->format('Y-m-d H:i:s') : '-');

                $number++;
            }

            $sheet->getStyle('F')->getNumberFormat()->setFormatCode('0');

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }

    public function download(): BinaryFileResponse
    {
        $path = $this->getRequest()->get('path');
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

        if (!str_starts_with($path, 'uploads')) {
            throw new NotFoundHttpException();
        }

        $ext = explode('.', $path);

        if (!in_array(end($ext), $allowedExt)) {
            throw new NotFoundHttpException();
        }

        $file = $this->getParameter('public_dir_path') . '/' . $path;

        $response = new BinaryFileResponse($file);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    public function getRegisterNumber($date = null): string
    {
        if (is_null($date)) {
            $date = new DateTime();
        }

        $counter = $this->getRepository(Store::class)->getTotalRegisteredMerchantByDate($date);
        $counter += 1;

        if ($counter < 10) {
            $counter = '0' . (string)$counter;
        }

        $array_month = array(1 => "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII");
        $month = $date->format('n');
        $monthRomawi = $array_month[$month];

        $registerNumber = 'Nomor: ' . $date->format('d') . $counter . '/' . $monthRomawi . '/BUS-SKsp.Mc/' . $date->format('Y');

        return $registerNumber;
    }

    protected function removeHtmlTags($str): string
    {
        $removeTags = strip_tags($str);
        $removeTags = preg_replace("/&#?[a-z0-9]+;/i", "", $removeTags);
        return $removeTags;
    }

    public function handleUploadedFile($file, $dir, $oldFile = null): string
    {
        $publicDir = $this->getParameter('public_dir_path');
        $ext_type = array('gif', 'jpg', 'jpe', 'jpeg', 'png', 'pdf');

        $uploadedFile = filter_var($file, FILTER_SANITIZE_STRING);

        $tempFile = $publicDir . $uploadedFile;
        $newDir = $publicDir . '/' . $dir . '/';

        $ext = pathinfo($tempFile, PATHINFO_EXTENSION);
        $fname = pathinfo($tempFile, PATHINFO_FILENAME);
        
        if (!file_exists($newDir)) {
            if (!@mkdir($newDir, 0755, true) && !is_dir($newDir)) {
                throw new \ErrorException($this->getTranslator()->trans('message.error.create_dir'));
            }
        }
        $localPath = ltrim($uploadedFile, '/');
        $remoteDir = $dir . '/';
        if (file_exists($tempFile) && in_array($ext, $ext_type)) {
            $newName = $fname;
            $filename = $newName . '.' . $ext;
            $uploadFile = $this->sftpUploader->upload($localPath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $filename);
            
            if ($uploadFile){
                $path = $publicDir . '/' . $localPath;
                unlink($path);
                rmdir($newDir);
            }
        }

        return '';
    }
}
