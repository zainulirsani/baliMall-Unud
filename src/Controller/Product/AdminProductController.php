<?php

namespace App\Controller\Product;

use App\Controller\AdminController;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductFile;
use App\Entity\Store;
use App\Entity\User;
use App\EventListener\ProductEntityListener;
use App\Helper\StaticHelper;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Service\QrCodeGenerator;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Hashids\Hashids;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\SftpUploader;

class AdminProductController extends AdminController
{
    protected $key = 'product';
    protected $entity = Product::class;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator, SftpUploader $sftpUploader)
    {
        parent::__construct($authorizationChecker, $translator, $validator, $sftpUploader);

        $this->authorizedRoles = ['ROLE_ADMIN_PRODUCT', 'ROLE_SUPER_ADMIN'];
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $stores = [];
        $categories = [];
        $jenis_produk = [];

        $userStores = $this->getUserStoresData();
        $productCategories = $this->getProductCategoriesData();
        $translator = $this->getTranslator();

        foreach ($userStores as $userStore) {
            $stores[$userStore['id']] = $userStore['text'];
        }

        foreach ($productCategories as $productCategory) {
            $categories[$productCategory['id']] = $productCategory['text'];
        }

        foreach ($this->getParameter('pdn_options') as $jenis) {
            $jenis_produk[] = $translator->trans(''.$jenis.'_product');
        }

        $this->dataTable->setFilters([
            'store' => [
                'type' => 'select',
                'collections' => $stores,
            ],
            'id_product_tayang' => [
                'type' => 'text',
            ],
            'status' => [
                'type' => 'select',
                'choices' => $this->getParameter('publish_draft'),
                'multiple' => true,
            ],
            'pdn_or_non_product' => [
                'type' => 'select',
                'choices' => $jenis_produk,
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
            'keywords' => [
                'type' => 'text',
            ],
            'category' => [
                'type' => 'select',
                'collections' => $categories,
            ],
            'price_min' => [
                'type' => 'text',
            ],
            'price_max' => [
                'type' => 'text',
            ],
            'year' => [
                'type' => 'text',
            ],
            'updated_at' => [
                'type' => 'checkbox',
            ],
            'jump_to_page' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'id_product_tayang', 'name', 'store_name', 'category', 'pdn_or_non_product', 'selling_price', 'base_price', 'stock', 'status', 'created', 'updated', 'actions']);
    }

    public function create()
    {
        $this->prepareTemplateSection();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);

        $storeRepository = $this->getRepository(Store::class);

        $stores = $storeRepository->getAllStores();

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'stores' => $stores,
        ]);
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

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        $buttonQuickSave = $translator->trans('button.quick_save');
        $buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');
        $buttonQrcode = $translator->trans('button.qrcode');
        $statusPublish = $translator->trans('label.publish');
        $statusDraft = $translator->trans('label.draft'); // Kalau di BE -> Draft adalah Unpublish
        $statusDraftPending = $translator->trans('label.draft_pending'); // Sedangkan Pending adalah Draft/Pending (permintaan client menambah status Draft/Pending)
        $statusNewProduct = $translator->trans('label.new_product');
        $statusProductUpdated = $translator->trans('label.product_updated');
        $statusDeleted = $translator->trans('button.delete');
        $filterJenis = $request->request->get('pdn_or_non_product', null);
        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'p.id']);

        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();
        if (isset($filterJenis) && !empty($filterJenis)) {
            $parameters['pdn_or_non_product'] = str_replace('_product', '', $filterJenis);
        }


        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $products = $results['data'];
        $data = [];

        foreach ($products as $product) {
            $productId = (int)$product['p_id'];
            $productName = $product['p_name'];
            $jenis_produk = $translator->trans('label.'.$product['p_is_pdn'].'_product');
            $productPrice = $product['p_price'];
            $productStatus = $product['p_status'];
            $urlProductView = '';
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $productId]);
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $productId]);

            if (!empty($product['s_slug'])) {
                $urlProductView = $this->generateUrl('store_product_page', ['store' => $product['s_slug'], 'product' => $product['p_slug']], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            //$status = $product['p_status'] === 'publish' ? 'label.publish' : 'label.draft';
            //$status = $translator->trans($product['p_status']);
            $status = '<select id="s-' . $productId . '">';
            $status .= '<option value="publish" ' . ($productStatus === 'publish' ? 'selected' : '') . '>' . $statusPublish . '</option>';
            $status .= '<option value="draft" ' . ($productStatus === 'draft' ? 'selected' : '') . '>' . $statusDraft . '</option>';
            $status .= '<option value="pending" ' . ($productStatus === 'pending' ? 'selected' : '') . '>' . $statusDraftPending . '</option>';
            $status .= '<option value="new_product" ' . ($productStatus === 'new_product' ? 'selected' : '') . '>' . $statusNewProduct . '</option>';
            $status .= '<option value="product_updated" ' . ($productStatus === 'product_updated' ? 'selected' : '') . '>' . $statusProductUpdated . '</option>';
            $status .= '<option value="deleted" ' . ($productStatus === 'deleted' ? 'selected' : '') . '>' . $statusDeleted . '</option>';
            $status .= '</select>';

            $checkbox = "<input value=\"$productId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
            // $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";

            $buttons = "<div class=\"btn-group-vertical\">";

            $buttons .= "<a href=\"$urlView\" class=\"btn btn-info\" title=\"$buttonView\"><i class=\"fa fa-edit\"></i> $buttonView</a>";


            if ($this->isAuthorizedToManage()) {
                $buttons .= "<a href=\"$urlEdit\" class=\"btn btn-info\" title=\"$buttonEdit\"><i class=\"fa fa-edit\"></i> $buttonEdit</a>";
                $buttons .= "<a href=\"javascript:void(0);\" class=\"btn btn-success product-quick-save\" title=\"$buttonQuickSave\" data-id=\"$productId\"><i class=\"fa fa-save\"></i> $buttonQuickSave</a>";
                $buttons .= "<a href=\"javascript:void(0);\" class=\"btn btn-primary product-qrcode\" title=\"$buttonQrcode\" data-id=\"$productId\" data-url=\"$urlProductView\" data-product=\"$productName\" data-price=\"$productPrice\"><i class=\"fa fa-qrcode\"></i> $buttonQrcode</a>";
                $buttons .= "<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" title=\"$buttonDelete\" data-id=\"$productId\"><i class=\"fa fa-trash\"></i> $buttonDelete</a>";
            } else {
                $buttons .= "<a href=\"javascript:void(0);\" class=\"btn btn-primary product-qrcode\" title=\"$buttonQrcode\" data-id=\"$productId\" data-url=\"$urlProductView\" data-product=\"$productName\" data-price=\"$productPrice\"><i class=\"fa fa-qrcode\"></i> $buttonQrcode</a>";
            }

            $buttons .= "</div>";

            $data[] = [
                $checkbox,
                $product['p_idProductTayang'] ?? '-',
                $productName,
                $product['s_name'],
                $product['pc_name'],
                $jenis_produk,
                '<input id="p-' . $productId . '" class="input-price" type="text" step="any" value="' . number_format(intval($product['p_price']), 0, '', '.') . '">',
                '<input id="bp-' . $productId . '" class="input-price" type="text" step="any" value="' . number_format(intval($product['p_basePrice']), 0, '', '.') . '">',
                '<input id="q-' . $productId . '" type="number" step="any" value="' . (int)$product['p_quantity'] . '">',
                $status,
                // "publish", "draft", "new_product", "product_updated", "deleted"
                // '<select id="s-'.$productId.'"><option value="publish" '.($status=="publish" ? "selected" : "").'>Publish</option><option value="draft" '.($status=="draft" ? "selected" : "").'>Unpublish</option><option value="pending" '.($status=="pending" ? "selected" : "").'>Draft/Pending</option><option value="new_product" '.($status=="new_product" ? "selected" : "").'>New Product</option><option value="product_updated" '.($status=="product_updated" ? "selected" : "").'>Product Updated</option><option value="deleted" '.($status=="deleted" ? "selected" : "").'>Deleted</option></select>',
                !empty($product['p_createdAt']) ? $product['p_createdAt']->format('d M Y H:i') : '-',
                !empty($product['p_updatedAt']) ? $product['p_updatedAt']->format('d M Y H:i') : '-',
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

    protected function actSaveData(Request $request): string
    {
        $formData = $request->request->all();
        // dd($formData);
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());
        $repo = $this->getRepository(Product::class);
        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $isNationalProduct = false;
        $multipleMerchantList = [];

        if (isset($formData['p_isNationalProduct']) && (bool)$formData['p_isNationalProduct'] === true) {
            $isNationalProduct = true;
        }

        if (isset($formData['p_nationalProductMerchant']) && count($formData['p_nationalProductMerchant']) > 0) {
            $multipleMerchantList = $formData['p_nationalProductMerchant'];
        }

        $countMultipleMerchant = count($multipleMerchantList);

        if ($isNationalProduct === false) {
            $countMultipleMerchant = 1;
        }


        for ($i = 0; $i < $countMultipleMerchant; $i++) {
            
            // if (isset($formData['p_slug']) && !empty($formData['p_slug'])) {
            //     $formData['p_slug'] = (new Slugify())->slugify($formData['p_slug']);
            // }

            $product = new Product();
            $product->setName(filter_var($formData['p_name'], FILTER_SANITIZE_STRING));
            $product->setKeywords(filter_var($formData['p_keywords'], FILTER_SANITIZE_STRING));
            $product->setDescription($formData['p_description']);
            $product->setIsPdn($formData['p_isPdn']);
            $product->setNote(filter_var($formData['p_note'], FILTER_SANITIZE_STRING));
            $product->setQuantity((int)$formData['p_quantity']);
            $product->setPrice(intval($formData['p_price']));
            $product->setBasePrice(intval($formData['p_basePrice']));
            $product->setWeight(convertToFloat($formData['p_weight']));
            $product->setStatus(filter_var($formData['p_status'], FILTER_SANITIZE_STRING));
            $product->setUnit(filter_var($formData['p_unit'], FILTER_SANITIZE_STRING));
            $product->setFeatured($formData['p_featured'] === 'yes');
            $product->setProductViewType($isNationalProduct ? 'produk_nasional' : null);
            $product->setIdProductTayang(filter_var($formData['p_id_product_tayang'], FILTER_SANITIZE_STRING));

            if (isset($formData['p_category'])) {
                if (is_array($formData['p_category'])) {
                    $product->setCategory(implode(',', $formData['p_category']));
                } else {
                    $product->setCategory($formData['p_category']);
                }
            }

            $belum_ada_data = false;

            if ($isNationalProduct === false && isset($formData['s_id']) && empty($formData['s_id'])) {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.merchant_not_choice')
                );

                return $this->generateUrl($this->getAppRoute('create'));
            }

            $merchantId = $isNationalProduct ?
                $multipleMerchantList[$i] : $formData['s_id'] ?? null;

            $store = $this->getRepository(Store::class)->findOneBy([
                'id' => $merchantId,
                'isActive' => true,
            ]);

            if ($store instanceof Store) {
                $product->setStore($store);
            }

            $slug = $isNationalProduct ? sprintf('%s-%s', $formData['p_slug'], $i) : $formData['p_slug'];

            $cek_data = $repo->findBy(['slug' => $slug]);

            /**
             * @TODO refactor
             */

            if (count($cek_data) == 0) {
                $belum_ada_data = true;
            } else {
                foreach ($cek_data as $key => $value) {
                    $product_store  = $value->getStore();
                    $belum_ada_data = $product_store->getId() == $store->getId() ? ($value->getStatus() != 'deleted' ? false : true) : true;
                    // $belum_ada_data = $value->getStatus() != 'deleted' ? false : true;
                }
            }

            $product->setSlug($slug);

            $validator = $this->getValidator();
            $productErrors = $validator->validate($product);

            if ($isNationalProduct && empty($multipleMerchantList)) {
                $message = $translator->trans('global.not_empty', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $product, 'nationalProductMerchant', '', null, null, new NotBlank(), null);
                $productErrors->add($constraint);
            }

            if ($belum_ada_data) {
                if (count($productErrors) === 0) {
                    $em = $this->getEntityManager();
                    $em->persist($product);
                    $em->flush();

                    $product->setSku('BM-P-'.$product->getId());
                    $em->persist($product);
                    $em->flush();
                    $product->setIdProductTayang($product->getId().'-'.$store);
                    $images = [];

                    $publicDir = $this->getParameter('public_dir_path');
                    $deleted = [];

                    if (isset($formData['p_imagesTmp']['filePath'])) {
                        foreach ($formData['p_imagesTmp']['filePath'] as $key => $image) {
                            $fileType = explode('/', $formData['p_imagesTmp']['fileMimeType'][$key]);
                            $fileType = $fileType[0] ?? 'image';

                            $productFile = new ProductFile();
                            $productFile->setProduct($product);
                            $productFile->setFileName($formData['p_imagesTmp']['fileName'][$key]);
                            $productFile->setFileType($fileType);
                            $productFile->setFileMimeType($formData['p_imagesTmp']['fileMimeType'][$key]);
                            $productFile->setFilePath(ltrim($image, '/'));

                            $em->persist($productFile);

                            $images[] = $productFile;

                            if (!empty($image)) {
                                $deleted[] = $publicDir . '/' . $image;
                            }
                        }

                        $em->flush();

                        foreach ($deleted as $file) {
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                    }

                    $this->appGenericEventDispatcher(new GenericEvent($product, [
                        'em' => $em,
                        'images' => $images,
                        'is_last' => ($countMultipleMerchant - $i === 1),
                    ]), 'app.product_save', new ProductEntityListener());


                    if ($i === 0) {
                        $this->addFlash(
                            'success',
                            $translator->trans('message.success.product_created', ['%name%' => $product->getName()])
                        );
                    }

                    if ($formData['btn_action'] === 'save' && $isNationalProduct === false) {
                        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $product->getId()]);
                    }
                } else {
                    $errors = [];
                    $redirect = $this->generateUrl($this->getAppRoute('create'));

                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();
                    }
                    if (floatval(str_replace(".", "", $formData['p_basePrice'])) > floatval(str_replace(".", "", $formData['p_price']))) {
                        $flashBag->set('warning', $this->getTranslator()->trans('message.info.reseller_price_greater'));
                        $flashBag->set('errors', $errors);
                    } else {
                        $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                        $flashBag->set('errors', $errors);
                    }
                }
            } else {
                $errors = [];
                $redirect = $this->generateUrl($this->getAppRoute('create'));

                if (isset($formData['s_id']) && !empty($formData['s_id'])) {
                    $this->addFlash(
                        'warning',
                        $this->getTranslator()->trans('message.error.product_exist', ['%product%' => $formData['p_name']])
                    );
                } else {
                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();
                    }

                    $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                    $flashBag->set('errors', $errors);
                }
            }
        }

        return $redirect;
    }

    protected function actReadData(int $id)
    {
        /** @var ProductRepository $repository */
        $repository = $this->getRepository($this->entity);
        $product = $repository->getDataWithDetailsById($id);

        if ($product) {
            $product['p_featured'] = (int)$product['p_featured'] === 1 ? 'yes' : 'no';
            //$product['p_category'] = !empty($product['p_category']) ? explode(',', $product['p_category']) : [];

            if (!empty($product['p_previousChanges'])) {
                $product['p_previousChanges'] = json_decode($product['p_previousChanges'], true);
            }
        }

        $this->checkAuthorizedAdminCabang($product['s_provinceId']);

        return $product;
    }

    protected function actEditData(int $id)
    {
        
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository($this->entity);
        // $semua_data = $repository->findAll();
        // $em       = $this->getEntityManager();
        // foreach ($semua_data as $key => $product) {
        //     if (empty($product->getSku())) {
        //         # code...
        //         $product->setSku('BM-P-'.$product->getId());
        //         $em->persist($product);
        //         $em->flush();
        //     }
        // }
        $product = $repository->getDataWithDetailsById($id);

        
        if ($product) {
            $product['p_featured'] = (int)$product['p_featured'] === 1 ? 'yes' : 'no';
            //$product['p_category'] = !empty($product['p_category']) ? explode(',', $product['p_category']) : [];
            
            if (empty($product['p_dirSlug'])) {
                $alphabet = getenv('HASHIDS_ALPHABET');
                $encoder = new Hashids($this->entity, 6, $alphabet);
                $dirSlug = $encoder->encode($product['p_id']);
                /** @var Product $duplicate */
                $duplicate = $repository->findOneBy(['dirSlug' => $dirSlug]);

                
                if ($duplicate instanceof Product) {
                    $salt = 'App\Entity\DuplicateProduct-' . date('YmdHis');
                    $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                    $dirSlug = $duplicateEncoder->encode($product['p_id']);
                }

                $product['p_dirSlug'] = $dirSlug;
            }

            if (!empty($product['p_previousChanges'])) {
                $product['p_previousChanges'] = json_decode($product['p_previousChanges'], true);
            }
        }

        $this->checkAuthorizedAdminCabang($product['s_provinceId']);

        $storeRepository = $this->getRepository(Store::class);

        $product['stores'] = $storeRepository->getAllStores();

        return $product;
    }

    protected function actUpdateData(Request $request, $id): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();

        /** @var User $user */
        $user = $this->getUser();
        /** @var Product $product */
        $product = $this->getRepository($this->entity)->find($id);
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);

        if ($product instanceof Product) {

            $this->checkAuthorizedAdminCabang($product->getStore()->getId());

            // if (isset($formData['p_slug']) && !empty($formData['p_slug'])) {
            //     $formData['p_slug'] = (new Slugify())->slugify($formData['p_slug']);
            // }

            $diff = $this->getProductDiff($product, $formData, (int)$user->getId());

            if (count($diff['diff']) > 0) {
                $product->setPreviousValues($diff['data']);
                $product->setPreviousChanges($diff['diff']);
            }

            

            $product->setName(filter_var($formData['p_name'], FILTER_SANITIZE_STRING));
            $product->setKeywords(filter_var($formData['p_keywords'], FILTER_SANITIZE_STRING));
            $product->setDescription($formData['p_description']);
            $product->setIsPdn($formData['p_isPdn']);
            $product->setNote(filter_var($formData['p_note'], FILTER_SANITIZE_STRING));
            $product->setQuantity((int)$formData['p_quantity']);
            $product->setPrice(intval($formData['p_price']));
            $product->setBasePrice(intval($formData['p_basePrice']));
            $product->setWeight((float)$formData['p_weight']);
            $product->setStatus(filter_var($formData['p_status'], FILTER_SANITIZE_STRING));
            $product->setUnit(filter_var($formData['p_unit'], FILTER_SANITIZE_STRING));
            $product->setFeatured($formData['p_featured'] === 'yes');
            $product->setAdminNote(filter_var($formData['p_adminNote'], FILTER_SANITIZE_STRING));
            $product->setIdProductTayang(filter_var($formData['p_id_product_tayang'], FILTER_SANITIZE_STRING));

            if (isset($formData['p_category'])) {
                if (is_array($formData['p_category'])) {
                    $product->setCategory(implode(',', $formData['p_category']));
                } else {
                    $product->setCategory($formData['p_category']);
                }
            }

            if (isset($formData['s_id']) && !empty($formData['s_id'])) {
                $store = $this->getRepository(Store::class)->findOneBy([
                    'id' => $formData['s_id'],
                    'isActive' => true,
                ]);

                if ($store instanceof Store) {
                    $product->setStore($store);
                }
            }

            if ($formData['p_status'] === 'publish') {
                $product->setPreviousChanges();
            }

            $slug = $formData['p_slug'];

            $cek_data = $this->getRepository($this->entity)->findBy(['slug' => $slug]);

            /**
             * @TODO refactor
             */

            if (count($cek_data) == 0) {
                $belum_ada_data = true;
            } else {
                foreach ($cek_data as $key => $value) {
                    $product_store  = $value->getStore();
                    if ($product_store != null && $store != null && $value != null) {
                        $belum_ada_data = $product_store->getId() == $store->getId() && $value->getId() != $id ? ($value->getStatus() != 'deleted' ? false : true) : true;
                    } else {
                        $belum_ada_data = true;
                    }
                }
            }

            $product->setSlug($slug);

            $validator = $this->getValidator();
            $productErrors = $validator->validate($product);

            $publicDir = $this->getParameter('public_dir_path');
            $deleted = [];

            if ($belum_ada_data) {
                if (count($productErrors) === 0) {
                    $em = $this->getEntityManager();
                    $em->persist($product);
                    $em->flush();
    
                    if (isset($formData['p_imagesTmp']['filePath'])) {
                        foreach ($formData['p_imagesTmp']['filePath'] as $key => $image) {
                            // dd($publicDir . '/' . $image);
                            $fileType = explode('/', $formData['p_imagesTmp']['fileMimeType'][$key]);
                            $fileType = $fileType[0] ?? 'image';
    
                            $productFile = new ProductFile();
                            $productFile->setProduct($product);
                            $productFile->setFileName($formData['p_imagesTmp']['fileName'][$key]);
                            $productFile->setFileType($fileType);
                            $productFile->setFileMimeType($formData['p_imagesTmp']['fileMimeType'][$key]);
                            $productFile->setFilePath(ltrim($image, '/'));
    
                            $em->persist($productFile);

                            if (!empty($image)) {
                                $deleted[] = $publicDir . '/' . $image;
                            }
                        }
    
                        $em->flush();

                        foreach ($deleted as $file) {
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                    }
    
                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.product_updated', ['%name%' => $product->getName()])
                    );
    
                    if ($formData['btn_action'] === 'save_exit') {
                        $redirect = $this->generateUrl($this->getAppRoute());
                    }
                } else {
                    $errors = [];
    
                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();
                    }
    
                    $flashBag = $this->get('session.flash_bag');
                    $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                    $flashBag->set('errors', $errors);
                }
            } else {
                $errors = [];
                $redirect = $this->generateUrl($this->getAppRoute('create'));

                if (isset($formData['s_id']) && !empty($formData['s_id'])) {
                    $this->addFlash(
                        'warning',
                        $this->getTranslator()->trans('message.error.product_exist', ['%product%' => $formData['p_name']])
                    );
                } else {
                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();
                    }

                    $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                    $flashBag->set('errors', $errors);
                }
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
        $productId = abs($request->request->get('product', '0'));
        $product = $this->getRepository($this->entity)->find($productId);
        $translator = $this->getTranslator();
        $response = [
            'status' => false,
            'message' => $translator->trans('message.error.delete', ['%name%' => 'product']),
        ];

        if ($product instanceof Product) {

            $this->checkAuthorizedAdminCabang($product->getStore()->getId());

            $productName = $product->getName();

            if (strpos($product->getSlug(), '[' . $product->getDirSlug() . ']') === false) {
                $productSlug = sprintf('%s-[%s]', $product->getSlug(), $product->getDirSlug());

                $product->setSlug($productSlug);
            }

            $product->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->persist($product);
            $em->flush();

            $response['status'] = true;
            $response['message'] = $translator->trans('message.success.delete', ['%name%' => $productName]);
        }

        return $response;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $action = $request->request->get('btn_action', 'invalid');
        $products = [];
        $proceed = false;
        $sql = null;
        $now = new DateTime('now');

        foreach ($ids as $key => $id) {
            $id = abs($id);
            $ids[$key] = $id;

            $product = $this->getRepository($this->entity)->find($id);

            if ($product instanceof Product) {
                $this->checkAuthorizedAdminCabang($product->getStore()->getId());

                $products[] = $product->getName();
            }
        }

        switch ($action) {
            case 'delete':
                $sql = 'DELETE from App\Entity\Product t WHERE t.id IN (%s)';
                $sql = sprintf($sql, implode(', ', $ids));
                $proceed = true;
                break;
            case 'activate':
                $sql = 'UPDATE App\Entity\Product t SET t.status = \'%s\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, 'publish', $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
            case 'deactivate':
                $sql = 'UPDATE App\Entity\Product t SET t.status = \'%s\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, 'draft', $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
        }

        if ($proceed) {
            // Special case for `delete` action
            if ($action === 'delete') {
                $em = $this->getEntityManager();
                /** @var ProductRepository $repository */
                $repository = $this->getRepository(Product::class);
                /** @var Product[] $products */
                $products = $repository->findBy(['id' => $ids]);

                foreach ($products as $product) {
                    if (strpos($product->getSlug(), '[' . $product->getDirSlug() . ']') === false) {
                        $slug = sprintf('%s-[%s]', $product->getSlug(), $product->getDirSlug());

                        $product->setSlug($slug);
                    }

                    $product->setStatus('deleted');

                    $em->persist($product);
                }

                $em->flush();
            } else {
                /** @var EntityManager $em */
                $em = $this->getEntityManager();
                $query = $em->createQuery($sql);
                $query->execute();
            }

            $success = sprintf('message.success.%s', $action);

            $this->addFlash(
                'success',
                $this->getTranslator()->trans($success, ['%name%' => implode(', ', $products)])
            );
        }
    }

    protected function getDefaultData(): array
    {
        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');

        $data = parent::getDefaultData();
        $data['product_category_data'] = productCategoryConversionData($productCategories);
        $data['dt_script'] = 'v2';

        return $data;
    }

    protected function manipulateDataPackage(): void
    {
        if (!$this->isAuthorizedToManage()) {
            $this->dataPackage->setAbleToCreate(false);
        }

        $this->dataPackage->setAbleToImport(true);
        $this->dataPackage->setAbleToExport(true);
    }

    protected function actImportData(UploadedFile $file): bool
    {
        $spreadsheet = IOFactory::load($file);
        $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $translator = $this->getTranslator();
        $headers = [
            'product_name' => 'A',
            'merchant_name' => 'B',
            'category' => 'C',
            'keywords' => 'D',
            'description' => 'E',
            'note' => 'F',
            'quantity' => 'G',
            'price' => 'H',
            'weight' => 'I',
            'status' => 'J',
            'featured' => 'K',
        ];

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var ProductCategoryRepository $categoryRepository */
        $categoryRepository = $this->getRepository(ProductCategory::class);
        $validator = $this->getValidator();
        $em = $this->getEntityManager();
        $slugger = new Slugify();
        $listener = new ProductEntityListener();
        $valid = 0;

        $sesuai_format = false;
        $format = $data[1];
        if (
            $format[$headers['product_name']] == 'product_name' &&
            $format[$headers['merchant_name']] == 'merchant_name' &&
            $format[$headers['category']] == 'category' &&
            $format[$headers['keywords']] == 'keywords' &&
            $format[$headers['description']] == 'description' &&
            $format[$headers['note']] == 'note' &&
            $format[$headers['quantity']] == 'quantity' &&
            $format[$headers['price']] == 'price' &&
            $format[$headers['weight']] == 'weight' &&
            $format[$headers['status']] == 'status' &&
            $format[$headers['featured']] == 'featured'
        ) {
            $sesuai_format = true;
        }

        if ($sesuai_format) {
            foreach ($data as $item) {
                if ($item[$headers['product_name']] !== 'product_name') {
                    $name = $item[$headers['product_name']];
                    $status = (strtolower($item[$headers['status']]) === 'publish') ? 'publish' : 'draft';
                    $storeName = $item[$headers['merchant_name']];
                    $categories = explode(',', $item[$headers['category']]);
                    $slug = $slugger->slugify($name);
                    $tempProduct = $repository->findOneBy(['slug' => $slug]);

                    if ($tempProduct instanceof Product) {
                        while (true) {
                            $existing = $repository->checkSlug($slug);

                            if ($existing > 0) {
                                $parts = explode('-', $slug);
                                $counter = count($parts);
                                $index = $counter - 1;
                                $last = end($parts);

                                if (is_numeric($last) && isset($parts[$index])) {
                                    $parts[$index] = $existing + 1;
                                    $slug = implode('-', $parts);
                                } else {
                                    $slug .= '-' . $existing;
                                }
                            } else {
                                break;
                            }
                        }
                    }

                    $product = new Product();
                    $product->setName(filter_var($name, FILTER_SANITIZE_STRING));
                    $product->setSlug(filter_var($slug, FILTER_SANITIZE_STRING));
                    $product->setKeywords(filter_var($item[$headers['keywords']], FILTER_SANITIZE_STRING));
                    $product->setDescription($item[$headers['description']]);
                    $product->setNote(filter_var($item[$headers['note']], FILTER_SANITIZE_STRING));
                    $product->setQuantity(abs($item[$headers['quantity']]));
                    $product->setPrice((float)$item[$headers['price']]);
                    $product->setBasePrice((float)$item[$headers['price']]);
                    $product->setWeight((float)convertToFloat($item[$headers['weight']]));
                    $product->setStatus($status);
                    $product->setFeatured(strtolower($item[$headers['featured']]) === 'yes');

                    if (is_array($categories) && count($categories) > 0) {
                        $slugs = [];

                        foreach ($categories as $category) {
                            $slugs[] = $slugger->slugify($category);
                        }

                        $ids = $categoryRepository->getCategoryIdByManySlugs($slugs);

                        if (count($ids) > 0) {
                            $product->setCategory(implode(',', $ids));
                        }
                    }

                    if (!empty($storeName)) {
                        $store = $this->getRepository(Store::class)->findOneBy([
                            'slug' => $slugger->slugify($storeName),
                            'isActive' => true,
                        ]);

                        if ($store instanceof Store) {
                            $product->setStore($store);
                        }
                    }

                    $productErrors = $validator->validate($product);

                    if (count($productErrors) === 0) {
                        $em->persist($product);
                        $valid++;

                        $listener->handle(new GenericEvent($product, [
                            'em' => $em,
                        ]));
                    }
                }
            }

            if ($valid > 0) {
                $em->flush();
            }
        }


        return $valid > 0;
    }

    public function quickSave()
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $translator = $this->getTranslator();
        $id = abs($request->request->get('id', '0'));
        $price = floatval(str_replace(".", "", $request->request->get('price', '0')));
        $basePrice = floatval(str_replace(".", "", $request->request->get('base_price', '0')));
        $quantity = abs($request->request->get('quantity', '0'));
        $statusProduct = $request->request->get('status', '');
        $status = true;
        $message = '';

        if ($id > 0) {
            if ($price < 1) {
                $status = false;
                $message = $translator->trans('message.info.qs_selling_price');
            }

            if ($basePrice < 1) {
                $status = false;
                $message = $translator->trans('message.info.qs_base_price');
            }

            if ($quantity < 0) {
                $status = false;
                $message = $translator->trans('message.info.qs_quantity');
            }

            if ($statusProduct === '') {
                $status = false;
                $message = $translator->trans('message.info.qs_status');
            }

            if ($status) {
                /** @var ProductRepository $repository */
                $repository = $this->getRepository(Product::class);
                /** @var Product $product */
                $product = $repository->find($id);

                if ($product instanceof Product) {
                    $old = [
                        'price' => (float)$product->getPrice(),
                        'base_price' => (float)$product->getBasePrice(),
                        'quantity' => $product->getQuantity(),
                        'status' => $product->getStatus(),
                    ];

                    $new = [
                        'price' => $price,
                        'base_price' => $basePrice,
                        'quantity' => (int)$quantity,
                        'status' => $statusProduct,
                    ];

                    $diff = array_diff($old, $new);

                    if (count($diff) > 0) {
                        $product->setPrice($price);
                        $product->setBasePrice($basePrice);
                        $product->setQuantity((int)$quantity);
                        $product->setStatus($statusProduct);

                        $em = $this->getEntityManager();
                        $em->persist($product);
                        $em->flush();

                        $message = $translator->trans('message.success.qs_product', ['%name%' => $product->getName()]);
                    }
                }
            }
        }

        $response = [
            'status' => $status,
            'message' => $message,
        ];

        return $this->view('', $response, 'json');
    }

    public function qrcode(): Response
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $multipleQrcode = abs($request->request->get('multiple', '0'));
        $qrFactory = $this->get(QrCodeGenerator::class);
        $productNames = [];
        $productPrices = [];
        $dataUris = [];

        if ($multipleQrcode === 1) {
            $ids = (array)$request->request->get('id', '');
            $fileName = 'qrcode_product_batch.pdf';

            if (count($ids) > 0) {
                foreach ($ids as $id) {
                    $id = abs($id);
                    $product = $this->getRepository($this->entity)->find($id);

                    if ($product instanceof Product) {
                        /** @var Store $store */
                        $store = $product->getStore();
                        $productUrl = $this->generateUrl('store_product_page', [
                            'store' => $store->getSlug(),
                            'product' => $product->getSlug(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL);

                        $productNames[] = $product->getName();
                        $productPrices[] = StaticHelper::formatForCurrency($product->getPrice());
                        $dataUris[] = $qrFactory->dataUri($productUrl);
                    }
                }
            }
        } else {
            $id = abs($request->request->get('id', '0'));
            $productUrl = $request->request->get('url', '');
            $productName = $request->request->get('product', '');
            $productPrice = $request->request->get('price', '');
            $fileName = 'qrcode_product_' . $productName . '_' . $id . '.pdf';

            $productNames[] = $productName;
            $productPrices[] = StaticHelper::formatForCurrency($productPrice);
            $dataUris[] = $qrFactory->dataUri($productUrl);
        }

        $options = new Options();
        $options->set('defaultFont', 'Arial');


        $pdf = new Dompdf($options);
        $pdf->loadHtml($this->renderView('@__main__/admin/product/print/qrcode_product.html.twig', [
            'data_uri' => $dataUris,
            'product_name' => $productNames,
            'price' => $productPrices,
        ]));
        $pdf->setPaper([0, 0, 284, 284]);
        $pdf->render();

        // $pdf->stream($fileName,array("Attachment"=>0));
        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=' . str_replace('.pdf', '', $fileName),
        ]);
        // return $pdf->stream();
        // return $pdf->render();
        // return $pdf->download($fileName);
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();
        if (isset($parameters['status'])) {
            $parameters['status'] = explode(',',$parameters['status']);
        }
        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        if (isset($parameters['status'])) {
            $parameters['status'] = explode(',',$parameters['status']);
        }
        $data = $repository->getDataToExport($parameters);
        $writer = null;
        $translator = $this->getTranslator();

        if (count($data['data']) > 0) {
            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Name');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Slug');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Category');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Merchant');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Keywords');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Description');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Quantity');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Base Price');
            $sheet->setCellValueByColumnAndRow(11, 1, 'Sell Price');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Unit Type');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Weight');
            $sheet->setCellValueByColumnAndRow(15, 1, 'Is Featured');
            $sheet->setCellValueByColumnAndRow(16, 1, 'View Count');
            $sheet->setCellValueByColumnAndRow(17, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(18, 1, 'Updated At');

            foreach ($data['data'] as $item) {
                $status = $translator->trans('label.' . $item['p_status']);

                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['p_id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['p_name']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['p_slug']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $item['pc_name']);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item['s_name']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['p_keywords']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $this->removeHtmlTags($item['p_description']));
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['p_quantity']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['p_basePrice']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['p_price']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $status);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['p_unit']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['p_weight']);
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['p_featured'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['p_viewCount']);
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), $item['p_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), !empty($item['p_updatedAt']) ? $item['p_updatedAt']->format('Y-m-d H:i:s') : '-');

                $number++;
            }

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }

    protected function removeHtmlTags($str): string
    {
        $removeTags = strip_tags($str);
        $removeTags = preg_replace("/&#?[a-z0-9]+;/i", "", $removeTags);
        return $removeTags;
    }
}
