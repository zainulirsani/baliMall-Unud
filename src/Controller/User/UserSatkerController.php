<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Satker;
use App\Entity\OrderChangeLog;
use App\Entity\Order;
use App\Entity\Store;
use App\Entity\Disbursement;
use App\Entity\UserPpkTreasurer;
use App\Entity\UserPicDocument;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Repository\UserPpkTreasurerRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Utility\GoogleMailHandler;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Helper\StaticHelper;


class UserSatkerController extends PublicController
{
    public function index()
    {
        $request = $this->getRequest();
        $type = $request->query->get('type', null);

        /** @var User $user */
        $user = $this->getUser();
        /** @var SatkerRepository $repository */
        $repository = $this->getRepository(Satker::class);
        $page = abs($request->query->get('page', '1'));
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            "id_lpse" => $user->getLkppLpseId(),
            'order_by' => 'satker.id',
            'sort_by' => 'DESC',
            'type' => $type,
        ];
        $parameters['redirect'] = 'user_satker_index';

        $testMasuk = "";
        $errorReport = "";
        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $documents = $adapter->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $documents = [];
            $pagination = $html = null;
            $errorReport = $e->getMessage();
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.data_'.$type)]);
        $dataView = $adapter->getQuery()->getArrayResult();

        // dd($documents, $parameters, $dataView, $testMasuk, $errorReport);

        return $this->view('@__main__/public/user/user_satker/index.html.twig', [
            'documents' => $documents,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'prefix' => getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID'),
            'html' => $html,
            'type' => $type,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            
            $query = $parameters;
            $query['page'] = $page;

            return $this->get('router')->generate($parameters['redirect'], $query);
        };
    }

    public function new()
    {
        $request = $this->getRequest();
        $type = $request->query->get('type', null);
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_ppk_save';

        BreadcrumbService::add(['label' => 'Tambah Data Satker']);

        return $this->view('@__main__/public/user/user_satker/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'type' => $type,
        ]);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_satker_index';
        $user_have = $this->getUser();

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $repository = $this->getRepository(Satker::class);

            $cek_data = $repository->findBy([
                'satkerName' => $formData['satker_name'],
                'user' => $user_have
            ]);

            if (count($cek_data) == 0 ) {
                $satker = new Satker();
                $satker->setSatkerName(filter_var($formData['satker_name'], FILTER_SANITIZE_STRING));


                $validator = $this->getValidator();
                $satkerErrors = $validator->validate($satker);

                if (count($satkerErrors) === 0) {
                    /** @var User $user */
                    $satker->setUser($user_have);

                    $em = $this->getEntityManager();
                    $em->persist($satker);
                    $em->flush();

                    $satker->setDigitVa(str_pad($satker->getId(), 8, "0", STR_PAD_LEFT));
                    $em->persist($satker);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_satker_created')
                    );
                } else {
                    $errors = [];
                    $route = 'user_satker_new';

                    foreach ($satkerErrors as $error) {
                        $errors[$error->getPropertyPath()] = $error->getMessage();
                    }

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('label.satker_exist')
                );
            }
        }

        return $this->redirectToRoute($route);
    }


    public function edit($id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var SatkerRepository $repository */
        $repository = $this->getRepository(Satker::class);
        $repositoryUser = $this->getRepository(User::class);
        /** @var Satker $userTaxDocument */
        $satker = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (empty($satker)) {
            throw new NotFoundHttpException(sprintf('Unable to find Satker with id "%s" for user id "%s"', $id, $user->getId()));
        }
        
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_tax_edit';

        $formData = [
            'id' => $satker->getId(),
            'satkerName' => $satker->getSatkerName(),
        ];

        BreadcrumbService::add(['label' => 'Data Satker']);

        return $this->view('@__main__/public/user/user_satker/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
        ]);
    }

    public function update(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_satker_index';
        $user_have = $this->getUser();

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $repository = $this->getRepository(Satker::class);

            $cek_data = $repository->findOneBy([
                'satkerName' => $formData['satker_name'],
                'user' => $user_have
            ]);

            if ($cek_data == null || ($cek_data->getId() == $formData['id'])) {
                $satker = $repository->findOneBy([
                    'id' => $formData['id'],
                    'user' => $user_have
                ]);

                $satker->setSatkerName(filter_var($formData['satker_name'], FILTER_SANITIZE_STRING));


                $validator = $this->getValidator();
                $satkerErrors = $validator->validate($satker);

                if (count($satkerErrors) === 0) {
                    /** @var User $user */

                    $em = $this->getEntityManager();
                    $em->persist($satker);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_satker_updated')
                    );
                } else {
                    $errors = [];
                    $route = 'user_satker_new';

                    foreach ($satkerErrors as $error) {
                        $errors[$error->getPropertyPath()] = $error->getMessage();
                    }

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('label.satker_exist')
                );
            }
        }

        return $this->redirectToRoute($route);
    }

    public function delete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $idSatker = abs($request->request->get('id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        /** @var SatkerRepository $repository */
        $repository = $this->getRepository(Satker::class);
        /** @var Satker $Satker */
        $satker = $repository->findOneBy([
            'id' => $idSatker
        ]);

        $em = $this->getEntityManager();
        $em->remove($satker);
        $em->flush();

        $response['deleted'] = true;
            

        return $this->view('', $response, 'json');
    }
}
