<?php

namespace App\Controller\Banner;

use App\Controller\AdminController;
use App\Entity\Banner;
use Cocur\Slugify\Slugify;
use App\Repository\BannerRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\VarDumper\VarDumper;

use Symfony\Component\HttpFoundation\Response;


class AdminBannerController extends AdminController
{
    protected $key = 'banner';
    protected $entity = Banner::class;
    protected $allowedPosition = ['left', 'right', 'top'];

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'name', 'context', 'position', 'status', 'created', 'updated', 'actions']);
    }

    protected function actFetchData(Request $request): array
    {

        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        $buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'b.id']);

         /** @var BannerRepository $repository */
        $repository = $this->getRepository(Banner::class);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $banners = $results['data'];
        $data = [];

        try {
            foreach ($banners as $banner) {
                $bannerId = (int) $banner['b_id'];
                $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $bannerId]);
                $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $bannerId]);

                $checkbox = "<input value=\"$bannerId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
                $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
                $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$bannerId\">$buttonDelete</a>";

                $data[] = [
                    $checkbox,
                    ucfirst($banner['b_name']),
                    ucfirst($banner['b_context']),
                    ucfirst($banner['b_position']),
                    $banner['b_status'] === 'active' ? 'Active' : 'Inactive',
                    !empty($banner['b_createdAt']) ? $banner['b_createdAt']->format('d M Y') : '-',
                    !empty($banner['b_updatedAt']) ? $banner['b_updatedAt']->format('d M Y') : '-',
                    $buttons,
                ];
            }
        }catch (\Throwable $throwable) {

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
        $bannerRepository = $this->getRepository(Banner::class);
        $banner = $bannerRepository->find($id);

        return $banner;
    }

    public function save()
    {
        $request = $this->getRequest();
        $formData = $this->getRequest()->request;
        $newPath = 'uploads/banner';
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute('create'));
        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData->all());
        $errors = [];

        $bannerName = $formData->get('banner_name', null);
        $position = $formData->get('position', null);
        $categoryId = $formData->get('category_id', null);
        $keyword = $formData->get('keyword', '');
        $isActive = $formData->get('is_active', false);
        $bannerFile = $formData->get('banner_imageTmp', null);
        $externalUrl = $formData->get('external_url', null);
        $childButtons = $formData->get('button', null);

        $bannerRepository = $this->getRepository(Banner::class);

        $route = 'search';
        $parameters = null;

        if (!empty($categoryId)) {
            $parameters = ['keywords' => $keyword, 'category1' => [(int) $categoryId => (int) $categoryId]];
        }

        if ((empty($categoryId) && !empty($externalUrl)) || (empty($categoryId) && empty($externalUrl))) {
            $route = null;
        }

        if (!empty($externalUrl)) {
            $route = 'external';
        }

        if (empty($categoryId)) {
            $categoryId = null;
        }

        $status = !empty($isActive) ? 'active' : 'inactive';

        $banner = new Banner();
        $banner->setName($bannerName);
        $banner->setPosition($position);
        $banner->setRoute($route);
        $banner->setParameters($parameters);
        $banner->setStatus($status);
        $banner->setContext('homepage');
        $banner->setExternalUrl($externalUrl);
        $banner->setButtons(null);
        $banner->setKeyword($keyword);
        $banner->setCategoryId($categoryId);

        // if (!empty($bannerFile)) {
        //     $uploadedFile = $this->handleUploadedFile($bannerFile, $newPath);
        //     if (!empty($uploadedFile)) {
        //         $banner->setImage($uploadedFile);
        //     }
        // }

        $file = $request->files->get('file');
        if ($file) {
            $localFilePath = $file->getPathname();
            $remoteDir = "uploads/banner/";
            $fileName = uniqid() .".". $file->getClientOriginalExtension();
            $remoteFilePath = $fileName;

            $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
            $banner->setImage($remoteDir . $fileName);
        }

        $bannerErrors = $this->getValidator()->validate($banner);

        $isPositionActive = $bannerRepository->checkIfPositionActive($position) && $isActive;

        if ($isPositionActive) {
            $message = $translator->trans('global.not_valid', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $banner, 'is_active', '', null, null, new Assert\NotBlank(), null);

            $bannerErrors->add($constraint);
        }

//        if (empty($externalUrl) && empty($categoryId)) {
//            $message = $translator->trans('global.not_valid', [], 'validators');
//            $constraint = new ConstraintViolation($message, $message, [], $banner, 'category_id', '', null, null, new Assert\NotBlank(), null);
//
//            $bannerErrors->add($constraint);
//        }

        // if (empty($bannerFile)) {
        //     $message = $translator->trans('global.not_valid', [], 'validators');
        //     $constraint = new ConstraintViolation($message, $message, [], $banner, 'banner_file', '', null, null, new Assert\NotBlank(), null);

        //     $bannerErrors->add($constraint);
        // }

        if (count($bannerErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($banner);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.banner_created', ['%name%' => $banner->getName()])
            );

            $flashBag->set('form_data', []);

            if ($formData->get('btn_action') === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }

        }else {

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));

            foreach ($bannerErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('errors', $errors);
        }

        return $this->redirect($redirect);
    }

    protected function actEditData(int $id)
    {
        $bannerRepository = $this->getRepository(Banner::class);

        return $bannerRepository->find($id);
    }

    protected function actUpdateData(Request $request, int $id): string
    {
        $formData = $request->request;

        $newPath = 'uploads/banner';
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);

        $translator = $this->getTranslator();
        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData->all());

        $bannerName = $formData->get('banner_name', null);
        $position = $formData->get('position', null);
        $categoryId = $formData->get('category_id', null);
        $keyword = $formData->get('keyword', '');
        $isActive = $formData->get('is_active', false);
        $bannerFile = $formData->get('banner_imageTmp', null);
        $externalUrl = $formData->get('external_url', null);
        $childButtons = $formData->get('button', null);

        $bannerRepository = $this->getRepository(Banner::class);

        $route = 'search';
        $parameters = null;

        if (!empty($categoryId)) {
            $parameters = ['keywords' => $keyword, 'category1' => [(int) $categoryId => (int) $categoryId]];
        }

        if ((empty($categoryId) && !empty($externalUrl)) || (empty($categoryId) && empty($externalUrl))) {
            $route = null;
        }

        if (!empty($externalUrl)) {
            $route = 'external';
        }

        if (empty($categoryId)) {
            $categoryId = null;
        }

        $status = !empty($isActive) ? 'active' : 'inactive';
        $banner = $bannerRepository->find($id);
        $banner->setName($bannerName);
        $banner->setPosition($position);
        $banner->setRoute($route);
        $banner->setParameters($parameters);
        $banner->setStatus($status);
        $banner->setContext('homepage');
        $banner->setExternalUrl($externalUrl);
        $banner->setButtons(null);
        $banner->setKeyword($keyword);
        $banner->setCategoryId($categoryId);

        // if (!empty($bannerFile)) {
        //     $uploadedFile = $this->handleUploadedFile($bannerFile, $newPath);
        //     if (!empty($uploadedFile)) {
        //         $banner->setImage($uploadedFile);
        //     }
        // }

        $file = $request->files->get('file');
        // $document = $request->files->get('document');
        if ($file) {
            // VarDumper::dump($file->getPathname());
            $localFilePath = $file->getPathname();
            $remoteDir = "uploads/banner/";
            $fileName = uniqid() .".". $file->getClientOriginalExtension();
            $remoteFilePath = $fileName;

            $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
            $banner->setImage($remoteDir . $fileName);

            // upload document
            // $localFilePath = $document->getPathname();
            // $remoteDir = "uploads/documents/";
            // $documentName = uniqid() .".". $document->getClientOriginalExtension();
            // $remoteFilePath = $documentName;
            // $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
        }

        $bannerErrors = $this->getValidator()->validate($banner);

        $isPositionActive = $bannerRepository->checkIfPositionActive($position, $id) && $isActive;

        if ($isPositionActive) {
            $message = $translator->trans('global.not_valid', [], 'validators');
            $constraint = new ConstraintViolation($message, $message, [], $banner, 'is_active', '', null, null, new Assert\NotBlank(), null);

            $bannerErrors->add($constraint);
        }

//        if (empty($externalUrl) && empty($categoryId)) {
//            $message = $translator->trans('global.not_valid', [], 'validators');
//            $constraint = new ConstraintViolation($message, $message, [], $banner, 'category_id', '', null, null, new Assert\NotBlank(), null);
//
//            $bannerErrors->add($constraint);
//        }

        // if (empty($bannerFile)) {
        //     $message = $translator->trans('global.not_valid', [], 'validators');
        //     $constraint = new ConstraintViolation($message, $message, [], $banner, 'banner_file', '', null, null, new Assert\NotBlank(), null);

        //     $bannerErrors->add($constraint);
        // }

        if (count($bannerErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($banner);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.banner_updated', ['%name%' => $banner->getName()])
            );

            if ($formData->get('btn_action') === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }

        }else {
            $errors = [];

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));

            foreach ($bannerErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('errors', $errors);
        }

        return $redirect;
    }

    public function showImage(string $path): Response
    {
        // Tentukan direktori di server SFTP tempat gambar disimpan
        $remoteDirectory = $_ENV['SFTP_REMOTE_DIR'];
        $imageName = $path;
        // VarDumper::dump($path);
        
        // Ambil gambar dari server SFTP
        $imageData = $this->sftpUploader->getImageData($remoteDirectory . $imageName);
        
        // Periksa apakah gambar ditemukan
        if ($imageData === false) {
            throw $this->createNotFoundException('Image not found');
        }
        
        // Tentukan tipe konten gambar
        $response = new Response($imageData);
        // $response->headers->set('Content-Type', 'image/jpeg'); 

        // preview pdf
        // $response->headers->set('Content-Type', 'application/pdf'); 

        // download
        // $response->headers->set('Content-Disposition', 'attachment; filename="test.pdf"');
        
        return $response;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        $action = $request->request->get('btn_action', 'invalid');
        $sql = null;
        $proceed = false;
        $bannerList = [];
        $isActiveBannerExist = false;

        foreach ($ids as $key => $id) {
            $id = abs($id);
            $ids[$key] = $id;

            $banner = $this->getRepository(Banner::class)->find($id);

            if ($banner instanceof Banner) {
                $bannerList[] = $banner->getName();

                $checkBannerActive = $this->getRepository(Banner::class)->checkIfPositionActive($banner->getPosition());

                if ($checkBannerActive) {
                    $isActiveBannerExist = true;
                    break;
                }
            }
        }

        if ($isActiveBannerExist && $action === 'activate') {
            $this->addFlash(
                'warning',
                $this->getTranslator()->trans('message.error.banner_active_exist')
            );

        }else {
            switch ($action) {
                case 'delete':
                    $sql = 'UPDATE App\Entity\Banner t SET t.status = \'deleted\' WHERE t.id IN (%s)';
                    $proceed = true;
                    break;
                case 'activate':
                    $sql = 'UPDATE App\Entity\Banner t SET t.status = \'active\' WHERE t.id IN (%s)';
                    $proceed = true;
                    break;
                case 'deactivate':
                    $sql = 'UPDATE App\Entity\Banner t SET t.status = \'inactive\' WHERE t.id IN (%s)';
                    $proceed = true;
                    break;
            }

            if ($proceed) {
                $sql = sprintf($sql, implode(', ', $ids));
                /** @var EntityManager $em */
                $em = $this->getEntityManager();
                $query = $em->createQuery($sql);
                $query->execute();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.'.$action, ['%name%' => implode(', ', $bannerList)])
                );
            }
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

        $data['position'] = ['top','left','right'];

        return $data;
    }

    protected function actDeleteData(): array
    {
        $request = $this->getRequest();
        $bannerId = abs($request->get('banner'));
        $bannerRepository = $this->getRepository(Banner::class);
        $banner = $bannerRepository->find($bannerId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'banner']),
        ];

        if ($banner instanceof Banner) {
            $banner->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->persist($banner);
            $em->flush();

            $response['status'] = true;
            $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $banner->getName()]);
        }

        return $response;
    }

    public function handleUploadedFile($file, $dir, $oldFile = null): string
    {
        $publicDir = $this->getParameter('public_dir_path');
        $slugger = new Slugify();
        $ext_type = array('jpg', 'jpeg', 'png');

        $uploadedFile = filter_var($file, FILTER_SANITIZE_STRING);

        $tempFile = $publicDir . $uploadedFile;
        $newDir = $publicDir . '/' . $dir;

        $ext = pathinfo($tempFile, PATHINFO_EXTENSION);
        $fname = pathinfo($tempFile, PATHINFO_FILENAME);

        if (!file_exists($newDir)) {
            if (!@mkdir($newDir, 0755, true) && !is_dir($newDir)) {
                throw new \ErrorException($this->getTranslator()->trans('message.error.create_dir'));
            }
        }

        if (file_exists($tempFile) && in_array($ext, $ext_type)) {
            // $safeName = $slugger->slugify($fname);
            $safeName = "logoarysmikro-mss3";
            $newName = $safeName . '-' . uniqid('', false);
            $filename = $newName . '.' . $ext;

            if (rename($tempFile, $newDir . '/' . $filename)) {

                if (!empty($oldFile)) {
                    $path = $publicDir . '/' . $oldFile;
                    if (is_file($oldFile)) {
                        unlink($path);
                    }
                }

                return $dir . '/' . $filename;
            }
        }

        return '';
    }
}
