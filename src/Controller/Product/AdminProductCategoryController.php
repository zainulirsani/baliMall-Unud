<?php

namespace App\Controller\Product;

use App\Controller\AdminController;
use App\Entity\ProductCategory;
use App\EventListener\ProductCategoryEntityListener;
use App\Repository\ProductCategoryRepository;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;

class AdminProductCategoryController extends AdminController
{
    protected $key = 'product_category';
    protected $entity = ProductCategory::class;

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
                'order_by' => 'pc.id',
                'sort_by' => 'DESC',
                'search' => filter_var($search, FILTER_SANITIZE_STRING),
            ];

            /** @var ProductCategoryRepository $repository */
            $repository = $this->getRepository($this->entity);
            $items = $repository->getDataForSelectOptions($parameters);
        }

        return $this->view('', ['items' => $items], 'json');
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'name', 'parent_category', 'status', 'featured', 'fee', 'created', 'updated', 'actions']);
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        //$buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'pc.id']);

        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $categories = $results['data'];
        $data = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category['pc_id'];
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $categoryId]);
            //$urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $categoryId]);
            $status = (int) $category['pc_status'] === 1 ? 'label.active' : 'label.inactive';
            $featured = (int) $category['pc_featured'] === 1 ? 'label.yes' : 'label.no';
            $fee = $category['pc_fee'] != null && $category['pc_fee'] != "" ? $category['pc_fee'] : 0;
            $checkbox = "<input value=\"$categoryId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
            $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
            $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$categoryId\">$buttonDelete</a>";

            $data[] = [
                $checkbox,
                $category['pc_name'],
                $category['pcp_name'],
                $translator->trans($status),
                $translator->trans($featured),
                $fee,
                !empty($category['pc_createdAt']) ? $category['pc_createdAt']->format('d M Y H:i') : '-',
                !empty($category['pc_updatedAt']) ? $category['pc_updatedAt']->format('d M Y H:i') : '-',
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
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());
        $random = random_int(1, 999999);

        $uploadDekstopImage = $request->files->get('file_image');
        $uploadMobileImage = $request->files->get('file_image_mobile');
        $valid_extension = ['jpg','png','jpeg','gif'];

        $noImage = 'dist/img/no-image.png';

        // if (isset($formData['pc_slug']) && !empty($formData['pc_slug'])) {
        //     $formData['pc_slug'] = (new Slugify())->slugify($formData['pc_slug']);
        // }

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $formData['pc_desktopImage'] = $noImage;
        $formData['pc_mobileImage'] = $noImage;

        if (isset($formData['pc_desktopImageTmp']) && !empty($formData['pc_desktopImageTmp'])) {
            $desktopImage = filter_var($formData['pc_desktopImageTmp'], FILTER_SANITIZE_STRING);
            $formData['pc_desktopImage'] = ltrim($desktopImage, '/');
        }

        if (isset($formData['pc_mobileImageTmp']) && !empty($formData['pc_mobileImageTmp'])) {
            $mobileImage = filter_var($formData['pc_mobileImageTmp'], FILTER_SANITIZE_STRING);
            $formData['pc_mobileImage'] = ltrim($mobileImage, '/');
        }

        $productCategory = new ProductCategory();
        $productCategory->setParentId(abs($formData['pc_parentId']));
        $productCategory->setName(filter_var($formData['pc_name'], FILTER_SANITIZE_STRING));
        $productCategory->setSlug(filter_var($formData['pc_slug'], FILTER_SANITIZE_STRING));
        $productCategory->setHeading(filter_var($formData['pc_heading'], FILTER_SANITIZE_STRING));
        $productCategory->setDescription($formData['pc_description']);
        // $productCategory->setDesktopImage($formData['pc_desktopImage']);
        // $productCategory->setMobileImage($formData['pc_mobileImage']);
        $productCategory->setStatus((bool) $formData['pc_status']);
        $productCategory->setFeatured($formData['pc_featured'] === 'yes');
        $productCategory->setWithTax($formData['pc_withTax'] === 'yes');
        $productCategory->setSort((int) $formData['pc_sort']);
        $productCategory->setSlugCheck(true);
        $productCategory->setClassName(filter_var($formData['pc_className'], FILTER_SANITIZE_STRING));
        $productCategory->setDirSlug($random);

        $validator = $this->getValidator();
        $productCategoryErrors = $validator->validate($productCategory);

        if ($uploadDekstopImage != null) {
            $extension = $uploadDekstopImage->getClientOriginalExtension();
            if (!in_array($extension,$valid_extension)) {
                $message = $this->getTranslator()->trans('global.file_not_valid', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $productCategory, 'file_image', '', null, null, new Assert\NotBlank(), null);

                $productCategoryErrors->add($constraint);
            } else {
                $localFilePath = $uploadDekstopImage->getPathname(); 
                $remoteDir = "uploads/product_categories/" . $random . '/';
                $fileName = uniqid() . '-' . $uploadDekstopImage->getClientOriginalName();
                $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $fileName);
                $productCategory->setDesktopImage($remoteDir . $fileName);
            }
        } 

        if ($uploadMobileImage != null) {
            $extension = $uploadMobileImage->getClientOriginalExtension();
            if (!in_array($extension,$valid_extension)) {
                $message = $this->getTranslator()->trans('global.file_not_valid', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $productCategory, 'file_image', '', null, null, new Assert\NotBlank(), null);

                $productCategoryErrors->add($constraint);
            } else {

                $localFilePath = $uploadMobileImage->getPathname(); 
                $remoteDir = "uploads/product_categories/" . $random . '/';
                $fileName = uniqid() . '-' . $uploadMobileImage->getClientOriginalName();
                $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $fileName);
                $productCategory->setMobileImage($remoteDir . $fileName);
            }
        }

        // To allow DuplicateSlugValidator work
        if (count($productCategoryErrors) > 0) {
            $productCategoryErrors = $validator->validate($productCategory);
        }

        if (count($productCategoryErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($productCategory);
            $em->flush();

            // $this->appGenericEventDispatcher(new GenericEvent($productCategory, [
            //     'em' => $em,
            // ]), 'app.product_category_save', new ProductCategoryEntityListener());

            $this->addFlash(
                'success',
                $translator->trans('message.success.product_category_created', ['%name%' => $productCategory->getName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $productCategory->getId()]);
            }

            $this->removeProductCategoriesDataFromCache();

            if ($productCategory->getParentId() === 0) {
                $this->removeProductCategoriesParentDataFromCache();
            }
        } else {
            $errors = [];

            foreach ($productCategoryErrors as $error) {
                $errors['pc_'.$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    protected function actUpdateData(Request $request, $id): string
    {
        $formData = $request->request->all();
        $uploadDekstopImage = $request->files->get('file_image');
        $uploadMobileImage = $request->files->get('file_image_mobile');
        $valid_extension = ['jpg','png','jpeg','gif'];
        // dd($uploadDekstopImage, $uploadMobileImage);
        /** @var ProductCategory $productCategory */
        $productCategory = $this->getRepository($this->entity)->find($id);
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);
        if ($productCategory instanceof ProductCategory) {
            $noImage = 'dist/img/no-image.png';

            $formData['pc_desktopImage'] = $noImage;
            $formData['pc_mobileImage'] = $noImage;

            // if (isset($formData['pc_slug']) && !empty($formData['pc_slug'])) {
            //     $formData['pc_slug'] = (new Slugify())->slugify($formData['pc_slug']);
            // }

            // if (isset($formData['pc_desktopImageTmp']) && !empty($formData['pc_desktopImageTmp'])) {
            //     $desktopImage = filter_var($formData['pc_desktopImageTmp'], FILTER_SANITIZE_STRING);
            //     $formData['pc_desktopImage'] = ltrim($desktopImage, '/');
            // }

            // if (isset($formData['pc_mobileImageTmp']) && !empty($formData['pc_mobileImageTmp'])) {
            //     $mobileImage = filter_var($formData['pc_mobileImageTmp'], FILTER_SANITIZE_STRING);
            //     $formData['pc_mobileImage'] = ltrim($mobileImage, '/');
            // }

            $productCategory->setParentId(abs($formData['pc_parentId']));
            $productCategory->setName(filter_var($formData['pc_name'], FILTER_SANITIZE_STRING));
            $productCategory->setSlug(filter_var($formData['pc_slug'], FILTER_SANITIZE_STRING));
            $productCategory->setHeading(filter_var($formData['pc_heading'], FILTER_SANITIZE_STRING));
            $productCategory->setDescription($formData['pc_description']);
            // $productCategory->setDesktopImage($formData['pc_desktopImage']);
            // $productCategory->setMobileImage($formData['pc_mobileImage']);
            $productCategory->setStatus((bool) $formData['pc_status']);
            $productCategory->setFeatured($formData['pc_featured'] === 'yes');
            $productCategory->setWithTax($formData['pc_withTax'] === 'yes');
            $productCategory->setSort((int) $formData['pc_sort']);
            $productCategory->setSlugCheck(true);
            $productCategory->setClassName(filter_var($formData['pc_className'], FILTER_SANITIZE_STRING));

            if (isset($formData['pc_fee'])) {
                if ($formData['pc_fee'] >= 0 && $formData['pc_fee'] <=100) {
                    $productCategory->setFee((int) $formData['pc_fee']);
                }
            }

            $validator = $this->getValidator();
            $productCategoryErrors = $validator->validate($productCategory);

            if ($uploadDekstopImage != null) {
                $extension = $uploadDekstopImage->getClientOriginalExtension();
                if (!in_array($extension,$valid_extension)) {
                    $message = $this->getTranslator()->trans('global.file_not_valid', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $productCategory, 'file_image', '', null, null, new Assert\NotBlank(), null);

                    $productCategoryErrors->add($constraint);
                } else {
                    $localFilePath = $uploadDekstopImage->getPathname(); 
                    $remoteDir = "uploads/product_categories/" . $formData['pc_dirSlug'] . '/';
                    $fileName = uniqid() . '-' . $uploadDekstopImage->getClientOriginalName();
                    $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $fileName);
                    $productCategory->setDesktopImage($remoteDir . $fileName);
                }
            }

            if ($uploadMobileImage != null) {
                $extension = $uploadMobileImage->getClientOriginalExtension();
                if (!in_array($extension,$valid_extension)) {
                    $message = $this->getTranslator()->trans('global.file_not_valid', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $productCategory, 'file_image', '', null, null, new Assert\NotBlank(), null);

                    $productCategoryErrors->add($constraint);
                } else {

                    $localFilePath = $uploadMobileImage->getPathname(); 
                    $remoteDir = "uploads/product_categories/" . $formData['pc_dirSlug'] . '/';
                    $fileName = uniqid() . '-' . $uploadMobileImage->getClientOriginalName();
                    $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $fileName);
                    $productCategory->setMobileImage($remoteDir . $fileName);
                }
            }

            // To allow DuplicateSlugValidator work
            if (count($productCategoryErrors) > 0) {
                $productCategoryErrors = $validator->validate($productCategory);
            }

            if (count($productCategoryErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($productCategory);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.product_category_updated', ['%name%' => $productCategory->getName()])
                );

                if ($formData['btn_action'] === 'save_exit') {
                    $redirect = $this->generateUrl($this->getAppRoute());
                }

                $this->removeProductCategoriesDataFromCache();

                if ($productCategory->getParentId() === 0) {
                    $this->removeProductCategoriesParentDataFromCache();
                }
            } else {
                $errors = [];

                foreach ($productCategoryErrors as $error) {
                    $errors['pc_'.$error->getPropertyPath()] = $error->getMessage();
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
        $request = $this->getRequest();
        $productCategoryId = abs($request->request->get('product_category', '0'));
        $productCategory = $this->getRepository($this->entity)->find($productCategoryId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'product category']),
        ];

        if ($productCategory instanceof ProductCategory) {
            $productCategoryName = $productCategory->getName();
            $productCategoryParent = $productCategory->getParentId();

            $em = $this->getEntityManager();
            $em->remove($productCategory);
            $em->flush();

            $this->removeProductCategoriesDataFromCache();

            if ($productCategoryParent === 0) {
                $this->removeProductCategoriesParentDataFromCache();
            }

            $response['status'] = true;
            $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $productCategoryName]);
        }

        return $response;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        $action = $request->request->get('btn_action', 'invalid');
        $productCategories = [];
        $productCategoriesParent = [];
        $proceed = false;
        $sql = null;
        $now = new DateTime('now');

        foreach ($ids as $key => $id) {
            $id = abs($id);
            $ids[$key] = $id;

            $productCategory = $this->getRepository($this->entity)->find($id);

            if ($productCategory instanceof ProductCategory) {
                $productCategories[] = $productCategory->getName();

                if ($productCategory->getParentId() === 0) {
                    $productCategoriesParent[] = $productCategory->getId();
                }
            }
        }

        switch ($action) {
            case 'delete':
                $sql = 'DELETE from App\Entity\ProductCategory t WHERE t.id IN (%s)';
                $sql = sprintf($sql, implode(', ', $ids));
                $proceed = true;
                break;
            case 'activate':
                $sql = 'UPDATE App\Entity\ProductCategory t SET t.status = 1, t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
            case 'deactivate':
                $sql = 'UPDATE App\Entity\ProductCategory t SET t.status = 0, t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
        }

        if ($proceed) {
            /** @var EntityManager $em */
            $em = $this->getEntityManager();
            $query = $em->createQuery($sql);
            $query->execute();

            $this->removeProductCategoriesDataFromCache();

            if (count($productCategoriesParent) > 0) {
                $this->removeProductCategoriesParentDataFromCache();
            }

            $success = sprintf('message.success.%s', $action);

            $this->addFlash(
                'success',
                $this->getTranslator()->trans($success, ['%name%' => implode(', ', $productCategories)])
            );
        }
    }

    private function removeProductCategoriesDataFromCache(): void
    {
        try {
            $cache = $this->getCache();
            $cache->deleteItem('app_product_category');
            $cache->deleteItem('app_product_category_with_parent');
            $cache->deleteItem('app_product_category_search_filter');
        } catch (InvalidArgumentException $e) {
        }
    }

    private function removeProductCategoriesParentDataFromCache(): void
    {
        try {
            $cache = $this->getCache();
            $cache->deleteItem('app_product_category_parent');
            $cache->deleteItem('app_product_category_search_filter');
        } catch (InvalidArgumentException $e) {
        }
    }

    protected function getDefaultData(): array
    {
        $productCategoryData = [];
        $productCategoryDataFilter = [];
        $productCategories = $this->getProductCategoriesWithParentsData();

        foreach ($productCategories as $item) {
            $productCategoryData[] = [
                'id' => $item['id'],
                'text' => (empty($item['parent_id'])) ? $item['text'] : sprintf('%s >> %s', $item['parent_text'], $item['text']),
            ];

            $productCategoryDataFilter[$item['id']] = (empty($item['parent_id'])) ? $item['text'] : sprintf('%s >> %s', $item['parent_text'], $item['text']);
        }

        $data = parent::getDefaultData();
        $data['product_category_data'] = $productCategoryData;
        $data['product_category_data_filter'] = $productCategoryDataFilter;
        $data['product_category_parent_data'] = $this->getProductCategoriesParentData();

        return $data;
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $defaultData = $this->getDefaultData();

        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'parent_category' => [
                'type' => 'select',
                'collections' => $defaultData['product_category_data_filter'],
            ],
            'status' => [
                'type' => 'select',
                'choices' => $this->getParameter('active_inactive'),
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
        ]);
    }
}
