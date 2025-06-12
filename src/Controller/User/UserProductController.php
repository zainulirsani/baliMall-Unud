<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Notification;
use App\Entity\Product;
use App\Entity\ProductFile;
use App\Entity\Store;
use App\Entity\User;
use App\EventListener\ProductEntityListener;
use App\Exception\StoreInactiveException;
use App\Repository\ProductRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Cocur\Slugify\Slugify;
use App\Service\FileUploader;
use App\Service\SanitizerService;
use App\Utility\UploadHandler;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Exception;
use Hashids\Hashids;
use Midtrans\Sanitizer;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class UserProductController extends PublicController
{
    
    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();
        // dd($user->getRoles()[0] != "ROLE_USER_SELLER");

        if ($user->getRoles()[0] != "ROLE_USER_SELLER") {
            return $this->redirectToRoute('login');
        }

        try {
            $this->checkForInvalidStoreAccess();
        } catch (StoreInactiveException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('user_dashboard');
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('item_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'order_by' => 'p.id',
            'sort_by' => 'DESC',
            'store' => $this->getUserStore(),
        ];

        if (!empty($keywords)) {
            $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
            $parameters['find_in_set'] = explode(' ', $keywords);
        }

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $pagination = new Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page);

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $products = $adapter->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $products = [];
            $pagination = $html = null;
        }

        BreadcrumbService::add(['label' => $this->getTranslation('title.page.product')]);

        return $this->view('@__main__/public/user/product/index.html.twig', [
            'products' => $products,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    public function new()
    {
        try {
            $this->checkForInvalidStoreAccess();
        } catch (StoreInactiveException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('user_dashboard');
        }

        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_product_save';

        BreadcrumbService::add(['label' => $this->getTranslation('title.page.product_create')]);

        $userStore = $this->getUserStore();

        $productCategories = [];
        $selectedProductCategories = [];

        try {
            $selectedProductCategories = $userStore->getProductCategories();
        } catch (\Throwable $e) {
        }

        if (count($selectedProductCategories) > 0) {
            $productCategories = array_filter($this->getProductCategoriesAlternateData(), function ($k) use ($userStore, $selectedProductCategories) {
                return in_array($k['id'], $selectedProductCategories);
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $this->view('@__main__/public/user/product/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'text_editor' => true,
            'product_category_data' => $productCategories,
        ]);
    }

    public function excel_new()
    {
        try {
            $this->checkForInvalidStoreAccess();
        } catch (StoreInactiveException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('user_dashboard');
        }

        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_product_save';

        BreadcrumbService::add(['label' => $this->getTranslation('title.page.product_create')]);

        $userStore = $this->getUserStore();

        $productCategories = array_filter($this->getProductCategoriesAlternateData(), function ($k) use ($userStore) {
            return in_array($k['id'], $userStore->getProductCategories());
        }, ARRAY_FILTER_USE_BOTH);

        return $this->view('@__main__/public/user/product/form_excel.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'text_editor' => true,
            'product_category_data' => $productCategories,
        ]);
    }

    public function saveExcel(): RedirectResponse
    {
        // dd('excel masuk');
        $request  = $this->getRequest();
        $em       = $this->getEntityManager();
        $repo     = $this->getRepository(Product::class);
        $flashBag = $this->get('session.flash_bag');
        $route    = 'user_product_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            $prefixPath = 'uploads/';
            $uploader = $this->get(FileUploader::class);

            $uploader->setTargetDirectory('excel_product/');
            $uploadedFile  = $request->files->get('product_excel');
            $sup_extension = ['csv', 'xlsx', 'xls'];
            if (!in_array($uploadedFile->getClientOriginalExtension(), $sup_extension)) {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.format_excel_only')
                );
                return $this->redirectToRoute('user_product_excel_new');
            }
            $filePath = $prefixPath . $uploader->upload($uploadedFile, false);

            $remoteDir      = "uploads/excel_product/";
            $originalName   = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newName        = $originalName . '-' . uniqid('', false);
            $remoteFilePath = $newName . '.' . $uploadedFile->getClientOriginalExtension();

            $this->sftpUploader->upload($filePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);

            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($filePath);

            $store = $this->getUserStore();

            $header = ['No', 'Nama', 'Kata Kunci', 'Deskripsi', 'Persediaan', 'Harga Jual tanpa PPN', 'Harga Reseller tanpa Ppn', 'Berat (kg)', 'PDN/NonPdn/Lokal', 'Image 1', 'Image 2', 'Image 3', 'Image 4'];
            // cek format
            $sesuai_format = true;
            foreach ($spreadsheet->getActiveSheet()->toArray()[0] as $key => $value) {
                if (isset($header[$key])) {
                    if ($value != $header[$key]) {
                        $sesuai_format = false;
                    }
                } else {
                    $sesuai_format = false;
                }
            }
            if ($sesuai_format == false) {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.format_excel_import')
                );
                return $this->redirectToRoute('user_product_excel_new');
            }
            $ada_kosong = false;
            foreach ($spreadsheet->getActiveSheet()->toArray() as $key => $data_excel) {
                if ($key > 0) {
                    for ($i = 1; $i < 9; $i++) {
                        if ($data_excel[$i] == "" && $data_excel[0] != "") {
                            $ada_kosong = true;
                        }
                    }
                }
            }

            if ($ada_kosong) {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.data_excel_empty')
                );
                return $this->redirectToRoute('user_product_excel_new');
            }


            // cek doble product
            $belum_ada_data = false;
            foreach ($spreadsheet->getActiveSheet()->toArray() as $key => $data_excel) {
                if ($key > 0) {
                    if ($data_excel[1] != null) {
                        $slug = str_replace(' ', '-', $data_excel[1]);
                        $cek_data = $repo->findBy(['slug' => $slug]);
                        if (count($cek_data) == 0) {
                            $belum_ada_data = true;
                        } else {
                            foreach ($cek_data as $key => $value) {
                                $product_store  = $value->getStore();
                                $belum_ada_data = $product_store->getId() == $store->getId() ? ($value->getStatus() != 'deleted' ? false : true) : true;
                                if (!$belum_ada_data) {
                                    $this->addFlash(
                                        'warning',
                                        $this->getTranslator()->trans('message.error.product_exist', ['%product%' => $data_excel[1]])
                                    );
                                    return $this->redirectToRoute('user_product_index');
                                }
                            }
                        }
                    }
                }
            }

            if ($belum_ada_data) {
                // set image
                $image_all = [];
                foreach ($spreadsheet->getActiveSheet()->toArray() as $key => $data_excel) {
                    if ($key > 0) {
                        if ($data_excel[1] != null) {
                            $image_cell = [];
                            $img_utama  = 'J' . ($key + 1);
                            $img_2      = 'K' . ($key + 1);
                            $img_3      = 'L' . ($key + 1);
                            $img_4      = 'M' . ($key + 1);

                            // untuk menentukan gambar utama
                            foreach ($spreadsheet->getActiveSheet()->getDrawingCollection() as $no => $drawing) {
                                $coordinate = $drawing->getCoordinates();
                                if ($coordinate == $img_utama) {
                                    $image_cell[count($image_cell)] = $drawing;
                                }
                            }

                            // untuk menentukan gambar lainnya
                            foreach ($spreadsheet->getActiveSheet()->getDrawingCollection() as $no => $drawing) {
                                $coordinate = $drawing->getCoordinates();
                                if ($coordinate == $img_2 || $coordinate == $img_3 || $coordinate == $img_4) {
                                    $image_cell[count($image_cell)] = $drawing;
                                }
                            }

                            $image_all[$key] = $image_cell;
                        }
                    }
                }

                foreach ($spreadsheet->getActiveSheet()->toArray() as $key => $data_excel) {
                    if ($key > 0) {
                        if ($data_excel[1] != null) {
                            $formData['p_status'] = 'new_product'; // Update: kalau merchant buat product baru, statusnya menjadi new_product
                            $slug = str_replace(' ', '-', $data_excel[1]);
                            $product = new Product();
                            $product->setName(filter_var($data_excel[1], FILTER_SANITIZE_STRING));
                            $product->setSlug($slug);
                            $product->setStore($store);
                            $product->setKeywords(filter_var($data_excel[2], FILTER_SANITIZE_STRING));
                            $product->setDescription($data_excel[3], FILTER_SANITIZE_STRING);
                            $product->setQuantity(floatval(str_replace(".", "", $data_excel[4])));
                            $product->setPrice(floatval(str_replace(".", "", $data_excel[5])));
                            $product->setBasePrice(floatval(str_replace(".", "", $data_excel[6])));
                            $product->setWeight((float) convertToFloat($data_excel[7]));
                            $pdnType = strtolower($data_excel[8]) == 'pdn' ? 'pdn' : (strtolower($data_excel[8]) == 'non pdn' ? 'non_pdn' : 'lokal');
                            $product->setIsPdn(filter_var($pdnType, FILTER_SANITIZE_STRING));
                            $product->setStatus(filter_var('new_product', FILTER_SANITIZE_STRING));
                            $product->setUnit(filter_var('unit', FILTER_SANITIZE_STRING));
                            $product->setFeatured(false);

                            if (isset($formData['p_category'])) {
                                if (is_array($formData['p_category'])) {
                                    $product->setCategory(implode(',', $formData['p_category']));
                                } else {
                                    $product->setCategory($formData['p_category']);
                                }
                            }

                            $em->persist($product);
                            $em->flush();


                            $product->setSku('BM-P-' . $product->getId());
                            $em->persist($product);
                            $em->flush();

                            foreach ($image_all[$key] as $no => $drawing) {
                                if ($drawing instanceof MemoryDrawing) {
                                    ob_start();
                                    call_user_func(
                                        $drawing->getRenderingFunction(),
                                        $drawing->getImageResource()
                                    );
                                    $imageContents = ob_get_contents();
                                    ob_end_clean();
                                    switch ($drawing->getMimeType()) {
                                        case MemoryDrawing::MIMETYPE_PNG:
                                            $extension = 'png';
                                            break;
                                        case MemoryDrawing::MIMETYPE_GIF:
                                            $extension = 'gif';
                                            break;
                                        case MemoryDrawing::MIMETYPE_JPEG:
                                            $extension = 'jpg';
                                            break;
                                    }
                                } else {
                                    if ($drawing->getPath()) {
                                        // Check if the source is a URL or a file path
                                        // if ($drawing->getIsURL()) {
                                        //     $imageContents = file_get_contents($drawing->getPath());
                                        //     $filePath = tempnam(sys_get_temp_dir(), 'Drawing');
                                        //     // $filePath = $prefixPath;
                                        //     file_put_contents($filePath , $imageContents);
                                        //     $mimeType = mime_content_type($filePath);
                                        //     // You could use the below to find the extension from mime type.
                                        //     // https://gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c#gistcomment-2722664
                                        //     $extension = File::mime2ext($mimeType);
                                        //     unlink($filePath);
                                        // }
                                        // else {
                                        $zipReader = fopen($drawing->getPath(), 'r');
                                        $imageContents = '';
                                        while (!feof($zipReader)) {
                                            $imageContents .= fread($zipReader, 1024);
                                        }
                                        fclose($zipReader);
                                        $extension = $drawing->getExtension();
                                        // }
                                    }
                                }

                                $myFileName = 'product_excel_' . $key . '_' . $no . '.' . $extension;
                                file_put_contents($prefixPath . '/products' . '/' . $myFileName, $imageContents);

                                $localDirFilePath = $prefixPath . '/products' . '/' . $myFileName;
                                $remoteDirFile    = "uploads/products/";

                                // save media to sftp server media
                                $this->sftpUploader->upload($localDirFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDirFile, $myFileName);

                                $productFile = new ProductFile();
                                $productFile->setProduct($product);
                                $productFile->setFileName($myFileName);
                                $productFile->setFileType('image');
                                $productFile->setFileMimeType('image/' . $extension);
                                $productFile->setFilePath($prefixPath . '/products' . '/' . $myFileName);

                                $em->persist($productFile);
                                $em->flush();

                                unlink($localDirFilePath);
                            }

                            $validator = $this->getValidator();
                            $productErrors = $validator->validate($product);

                            if (count($productErrors) === 0) {
                                /** @var Store $store */
                                $store = $this->getUserStore();
                                $product->setStore($store);


                                $notification = new Notification();
                                $notification->setSellerId(0);
                                $notification->setBuyerId(0);
                                $notification->setIsSentToSeller(false);
                                $notification->setIsSentToBuyer(false);
                                $notification->setIsAdmin(true);
                                $notification->setTitle($this->getTranslation('notifications.product_add'));
                                $notification->setContent($this->getTranslation('notifications.product_add_text', ['%name%' => $product->getName()]));
                                $notification->setUrl($this->generateUrl('admin_product_view', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL));

                                $em->persist($notification);
                                $em->flush();



                                // $this->appGenericEventDispatcher(new GenericEvent($product, [
                                //     'em' => $em,
                                //     'images' => $images,
                                // ]), 'front.user_product_save', new ProductEntityListener());
                                if ($key == 1) {
                                    $this->addFlash(
                                        'success',
                                        $this->getTranslator()->trans('message.success.user_product_created_alt')
                                    );
                                }
                            } else {
                                $errors = [];
                                $route = 'user_product_excel_new';

                                foreach ($productErrors as $error) {
                                    $errors['p_' . $error->getPropertyPath()] = $error->getMessage();

                                    if ($error->getConstraint()->message === 'product.price_check') {
                                        $errors['p_price'] = $error->getMessage();
                                    }
                                }

                                $flashBag->set('form_data', $formData);
                                $flashBag->set('errors', $errors);
                            }
                        }
                    }
                }
            }
            unlink($filePath);
        }

        return $this->redirectToRoute($route);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $repo    = $this->getRepository(Product::class);
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_product_index';

        $sanitizer = $this->get(SanitizerService::class);

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $formData['p_status'] = 'new_product'; // Update: kalau merchant buat product baru, statusnya menjadi new_product
            $slug = str_replace(' ', '-', $formData['p_name']);
            $store = $this->getUserStore();

            $cek_data = $repo->findBy(['slug' => $slug]);
            $belum_ada_data = false;
            if (count($cek_data) == 0) {
                $belum_ada_data = true;
            } else {
                foreach ($cek_data as $key => $value) {
                    $product_store  = $value->getStore();
                    $belum_ada_data = $product_store->getId() == $store->getId() ? ($value->getStatus() != 'deleted' ? false : true) : true;
                    // $belum_ada_data = $value->getStatus() != 'deleted' ? false : true;
                }
            }

            $product = new Product();
            $product->setName(filter_var($formData['p_name'], FILTER_SANITIZE_STRING));
            if (!empty($formData['p_sku'])) {
                $product->setSku($formData['p_sku']);
            }
            $product->setSlug($slug);
            $product->setKeywords(filter_var($formData['p_keywords'], FILTER_SANITIZE_STRING));
            $product->setDescription($sanitizer->sanitize($formData['p_description']));
            $product->setIsPdn($formData['p_isPdn']);
            //$product->setNote(filter_var($formData['p_note'], FILTER_SANITIZE_STRING));
            $product->setQuantity(floatval(str_replace(".", "", $formData['p_quantity'])));
            $product->setPrice(floatval(str_replace(".", "", $formData['p_price'])));
            $product->setBasePrice(floatval(str_replace(".", "", $formData['p_basePrice'])));
            $product->setWeight((float) convertToFloat($formData['p_weight']));
            $product->setStatus(filter_var($formData['p_status'], FILTER_SANITIZE_STRING));
            $product->setUnit(filter_var($formData['p_unit'], FILTER_SANITIZE_STRING));
            $product->setFeatured(false);

            if (isset($formData['p_category'])) {
                if (is_array($formData['p_category'])) {
                    $product->setCategory(implode(',', $formData['p_category']));
                } else {
                    $product->setCategory($formData['p_category']);
                }
            }

            $validator = $this->getValidator();
            $productErrors = $validator->validate($product);

            if ($belum_ada_data) {
                if (count($productErrors) === 0) {
                    /** @var Store $store */
                    $product->setStore($store);

                    $em = $this->getEntityManager();
                    $em->persist($product);
                    $em->flush();

                    // if (empty($formData('p_sku'))) {
                    $product->setSku('BM-P-' . $product->getId());
                    $em->persist($product);
                    $em->flush();
                    // }

                    $notification = new Notification();
                    $notification->setSellerId(0);
                    $notification->setBuyerId(0);
                    $notification->setIsSentToSeller(false);
                    $notification->setIsSentToBuyer(false);
                    $notification->setIsAdmin(true);
                    $notification->setTitle($this->getTranslation('notifications.product_add'));
                    $notification->setContent($this->getTranslation('notifications.product_add_text', ['%name%' => $product->getName()]));
                    $notification->setUrl($this->generateUrl('admin_product_view', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL));

                    $em->persist($notification);
                    $em->flush();

                    $images = [];

                    if (isset($formData['p_imagesTmp']['filePath'])) {
                        $uploaded = false;

                        foreach ($formData['p_imagesTmp']['filePath'] as $key => $image) {
                            if (!empty($image) && $image !== 'dist/img/bg.jpg') {
                                $uploaded = true;
                                $mimeType = $formData['p_imagesTmp']['fileMimeType'][$key];
                                $fileType = explode('/', $mimeType);
                                $fileType = $fileType[0] ?? 'image';

                                $productFile = new ProductFile();
                                $productFile->setProduct($product);
                                $productFile->setFileName($formData['p_imagesTmp']['fileName'][$key]);
                                $productFile->setFileType($fileType);
                                $productFile->setFileMimeType($mimeType);
                                $productFile->setFilePath(ltrim($image, '/'));

                                $em->persist($productFile);
                                $em->flush();

                                $images[] = $productFile;
                            }
                        }
                    }

                    $this->appGenericEventDispatcher(new GenericEvent($product, [
                        'em' => $em,
                        'images' => $images,
                    ]), 'front.user_product_save', new ProductEntityListener());

                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_product_created_alt')
                    );
                } else {
                    $errors = [];
                    $route = 'user_product_new';

                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();

                        if ($error->getConstraint()->message === 'product.price_check') {
                            $errors['p_price'] = $error->getMessage();
                        }
                    }

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);
                }
            } else {
                $errors = [];
                $route = 'user_product_new';
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.product_exist', ['%product%' => $formData['p_name']])
                );
            }
        }
        return $this->redirectToRoute($route);
    }

    public function delete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $productId = abs($request->request->get('id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        if ($userId === (int) $user->getId()) {
            /** @var Store $store */
            $store = $this->getUserStore();
            /** @var ProductRepository $repository */
            $repository = $this->getRepository(Product::class);
            /** @var Product $userProduct */
            $userProduct = $repository->findOneBy([
                'id' => $productId,
                'store' => $store,
            ]);

            if (strpos($userProduct->getSlug(), '[' . $userProduct->getDirSlug() . ']') === false) {
                $slug = sprintf('%s-[%s]', $userProduct->getSlug(), $userProduct->getDirSlug());

                $userProduct->setSlug($slug);
            }

            $userProduct->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->persist($userProduct);
            $em->flush();

            $response['deleted'] = true;
        }

        return $this->view('', $response, 'json');
    }

    public function edit($id)
    {
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_product_update';
        /** @var Store $userStore */
        $userStore = $this->getUserStore();

        if (empty($userStore)) {
            return $this->redirectToRoute('user_product_index');
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $formData = $repository->getFrontDataWithDetailsById($id, $userStore->getId());
        $formData['p_imagesTmp'] = $flashBag->get('imagesTmp');

        if (empty($formData['p_dirSlug'])) {
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(Product::class, 6, $alphabet);
            $dirSlug = $encoder->encode($id);
            /** @var Product $duplicate */
            $duplicate = $repository->findOneBy(['dirSlug' => $dirSlug]);

            if ($duplicate instanceof Product) {
                $salt = 'App\Entity\DuplicateProduct-' . date('YmdHis');
                $encoder = new Hashids($salt, 7, $alphabet);
                $dirSlug = $encoder->encode($id);
            }

            $formData['p_dirSlug'] = $dirSlug;
        }

        BreadcrumbService::add(['label' => $this->getTranslation('title.page.product_update')]);

        $productCategories = array_filter($this->getProductCategoriesAlternateData(), function ($k) use ($userStore) {
            return in_array($k['id'], $userStore->getProductCategories());
        }, ARRAY_FILTER_USE_BOTH);

        return $this->view('@__main__/public/user/product/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'text_editor' => true,
            'product_category_data' => $productCategories,
        ]);
    }

    public function update($id): RedirectResponse
    {
        $request = $this->getRequest();
        $templated = 'user_product_index';

        $sanitizer = new SanitizerService();
        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            /** @var User $user */
            $user = $this->getUser();
            /** @var ProductRepository $repository */
            $repository = $this->getRepository(Product::class);
            /** @var Product $product */
            $product = $repository->findOneBy([
                'id' => $id,
                'store' => $this->getUserStore(),
            ]);
            $slug = str_replace(' ', '-', $formData['p_name']);
            $diff = $this->getProductDiff($product, $formData, (int) $user->getId());
            $onlyQtyUpdated = false;
            $changeName = false;
            if ($formData['p_name'] != $product->getName()) {
                $changeName = true;
            }
            $store = $this->getUserStore();
            $cek_data = $repository->findBy(['slug' => $slug]);
            $belum_ada_data = false;
            if (count($cek_data) == 0) {
                $belum_ada_data = true;
            } else {
                foreach ($cek_data as $key => $value) {
                    $product_store  = $value->getStore();
                    $belum_ada_data = $product_store->getId() == $store->getId() && $value->getId() != $id ? ($value->getStatus() != 'deleted' ? false : true) : true;
                    // $belum_ada_data = $value->getStatus() != 'deleted' ? false : true;
                }
            }
            $formData['p_status'] = $product->getStatus();

            if (count($diff['diff']) > 0) {
                if (count($diff['diff']) === 3 && isset($diff['diff']['p_quantity']) && (int) $diff['diff']['p_quantity'] >= 0) {
                    $onlyQtyUpdated = true;
                } else {
                    // Jika produk yang baru di input merchant belum di approve oleh admin,
                    // tapi dia melakukan update pada product maka statusnya di tetapkan sebagai new product bukan menjadi status product_updated
                    $formData['p_status'] = ($product->getStatus() === 'new_product') ? $product->getStatus() : 'product_updated';
                }

                if ($product->getStatus() === 'product_updated' && !empty($product->getPreviousChanges())) {
                    $previousChanges = $product->getPreviousChanges();

                    $newPreviousChanges = array_merge($diff['diff'], $previousChanges);

                    $product->setPreviousChanges($newPreviousChanges);
                } else {
                    $product->setPreviousChanges($diff['diff']);
                }

                $product->setPreviousValues($diff['data']);
            }

            $product->setName(filter_var($formData['p_name'], FILTER_SANITIZE_STRING));
            if (!empty($formData['p_sku'])) {
                $product->setSku($formData['p_sku']);
            }
            $product->setSlug($slug);
            $product->setKeywords(filter_var($formData['p_keywords'], FILTER_SANITIZE_STRING));
            $product->setDescription($sanitizer->sanitize($formData['p_description']));
            $product->setIsPdn($formData['p_isPdn']);
            //$product->setNote(filter_var($formData['p_note'], FILTER_SANITIZE_STRING));
            //            if($product->getQuantity() !== $formData['p_quantity']){
            //                if($product->getStatus() !== 'publish'){
            //                    $product->setStatus($product->getStatus());
            //                }
            //            }
            $product->setQuantity(floatval(str_replace(".", "", $formData['p_quantity'])));
            $product->setPrice(floatval(str_replace(".", "", $formData['p_price'])));
            $product->setBasePrice(floatval(str_replace(".", "", $formData['p_basePrice'])));
            $product->setWeight((float) convertToFloat($formData['p_weight']));
            $product->setStatus(filter_var($formData['p_status'], FILTER_SANITIZE_STRING));
            $product->setUnit(filter_var($formData['p_unit'], FILTER_SANITIZE_STRING));

            if (isset($formData['p_category'])) {
                if (is_array($formData['p_category'])) {
                    $product->setCategory(implode(',', $formData['p_category']));
                } else {
                    $product->setCategory($formData['p_category']);
                }
            }

            if (empty($product->getDirSlug())) {
                $alphabet = getenv('HASHIDS_ALPHABET');
                $encoder = new Hashids(Product::class, 6, $alphabet);
                $dirSlug = $encoder->encode($id);
                /** @var Product $duplicate */
                $duplicate = $repository->findOneBy(['dirSlug' => $dirSlug]);

                if ($duplicate instanceof Product) {
                    $salt = 'App\Entity\DuplicateProduct-' . date('YmdHis');
                    $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                    $dirSlug = $duplicateEncoder->encode($id);
                }

                $product->setDirSlug($dirSlug);
            }

            $validator = $this->getValidator();
            $productErrors = $validator->validate($product);

            if ($belum_ada_data) {

                if (count($productErrors) === 0) {
                    $em = $this->getEntityManager();

                    if (count($diff['diff']) > 0) {
                        $productViewUrl = $this->generateUrl('admin_product_view', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

                        $notification = new Notification();
                        $notification->setSellerId(0);
                        $notification->setBuyerId(0);
                        $notification->setIsSentToSeller(false);
                        $notification->setIsSentToBuyer(false);
                        $notification->setIsAdmin(true);
                        $notification->setTitle($this->getTranslation('notifications.product_change'));
                        $notification->setContent($this->getTranslation('notifications.product_change_text', ['%name%' => $product->getName()]));
                        $notification->setUrl($productViewUrl);

                        $em->persist($notification);
                    }

                    $em->persist($product);
                    $em->flush();

                    if (isset($formData['p_imagesTmp']['filePath'])) {
                        $repository = $this->getRepository(ProductFile::class);
                        $publicDir = $this->getParameter('public_dir_path');
                        $uploaded = 0;
                        $deleted = [];

                        foreach ($formData['p_imagesTmp']['filePath'] as $key => $image) {
                            // Old image is deleted and new image is provided
                            if (!empty($image) && $image !== 'dist/img/bg.jpg') {
                                $uploaded++;
                                $imageId = 0;
                                $mimeType = $formData['p_imagesTmp']['fileMimeType'][$key];
                                $fileType = explode('/', $mimeType);
                                $fileType = $fileType[0] ?? 'image';

                                if (isset($formData['p_imagesTmp']['fileId'][$key])) {
                                    $imageId = abs($formData['p_imagesTmp']['fileId'][$key]);
                                }

                                if ($imageId > 0) {
                                    /** @var ProductFile $productFile */
                                    $productFile = $repository->find($imageId);
                                    $oldFile = $formData['p_imagesTmp']['fileOld'][$key];

                                    if (!empty($oldFile) && $productFile->getFilePath() !== $image) {
                                        $deleted[] = $publicDir . '/' . $oldFile;

                                        $productFile->setProduct($product);
                                        $productFile->setFileName($formData['p_imagesTmp']['fileName'][$key]);
                                        $productFile->setFileType($fileType);
                                        $productFile->setFileMimeType($mimeType);
                                        $productFile->setFilePath(ltrim($image, '/'));

                                        $em->persist($productFile);
                                    }
                                } else {
                                    $productFile = new ProductFile();
                                    $productFile->setProduct($product);
                                    $productFile->setFileName($formData['p_imagesTmp']['fileName'][$key]);
                                    $productFile->setFileType($fileType);
                                    $productFile->setFileMimeType($mimeType);
                                    $productFile->setFilePath(ltrim($image, '/'));

                                    $em->persist($productFile);
                                }
                            }

                            // Image is deleted and no new image is provided
                            if (empty($image) && $image !== 'dist/img/bg.jpg') {
                                $uploaded++;
                                $imageId = isset($formData['p_imagesTmp']['fileId'][$key]) ? abs($formData['p_imagesTmp']['fileId'][$key]) : 0;

                                if ($imageId > 0) {
                                    /** @var ProductFile $productFile */
                                    $productFile = $repository->find($imageId);
                                    $oldFile = $formData['p_imagesTmp']['fileOld'][$key];

                                    if (!empty($oldFile)) {
                                        $deleted[] = $publicDir . '/' . $oldFile;

                                        $em->remove($productFile);
                                    }
                                }
                            }
                        }

                        if ($uploaded > 0) {
                            $em->flush();
                            //foreach ($deleted as $file) {
                            //if (is_file($file)) {
                            //unlink($file);
                            //}
                            //}
                        }
                    }

                    $msg = 'message.success.user_product_created_alt';

                    if ($onlyQtyUpdated || count($diff['diff']) == 0) {
                        $msg = 'message.success.user_product_updated';
                    }

                    if ($changeName) {
                        $msg = 'Produk berhasil diubah, Silahkan melakukan copy ulang link produk dibawah ini';
                    }

                    $this->addFlash(
                        'success',
                        $changeName ? $this->generateUrl('store_product_page', [
                            'store' => $product->getStore()->getSlug(),
                            'product' => $product->getSlug(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL) : $this->getTranslator()->trans($msg),
                    );
                } else {
                    $errors = [];


                    foreach ($productErrors as $error) {
                        $errors['p_' . $error->getPropertyPath()] = $error->getMessage();

                        if ($error->getConstraint()->message === 'product.price_check') {
                            $errors['p_price'] = $error->getMessage();
                        }
                    }
                    $flashBag = $this->get('session.flash_bag');
                    $flashBag->set('errors', $errors);
                    $flashBag->set('imagesTmp', $formData['p_imagesTmp']);
                    $templated = 'user_product_edit';
                }
            } else {
                $errors = [];
                $route = 'user_product_new';
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('message.error.product_exist', ['%product%' => $formData['p_name']])
                );
            }
        }

        return $this->redirectToRoute($templated, ['id' => $id]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate('user_product_index', $query);
        };
    }

    // private function generateSlug(string $productName, int $productId = 0, ?ProductRepository $repository = null): string
    // {
    //     $productSlug = (new Slugify())->slugify($productName);

    //     if ($repository instanceof ProductRepository) {
    //         while (true) {
    //             $existing = $repository->checkSlug($productSlug, $productId);

    //             if ($existing > 0) {
    //                 $parts = explode('-', $productSlug);
    //                 $counter = count($parts);
    //                 $index = $counter - 1;
    //                 $last = end($parts);

    //                 if (is_numeric($last) && isset($parts[$index])) {
    //                     $parts[$index] = $existing + 1;
    //                     $productSlug = implode('-', $parts);
    //                 } else {
    //                     $productSlug .= '-'.$existing;
    //                 }
    //             } else {
    //                 break;
    //             }
    //         }
    //     }

    //     return $productSlug;
    // }

    private function getProductCategoriesAlternateData(): array
    {
        $productCategories = $this->getProductCategoriesFeatured(0, 'no', 'yes');

        return productCategoryConversionData($productCategories);
    }
}
