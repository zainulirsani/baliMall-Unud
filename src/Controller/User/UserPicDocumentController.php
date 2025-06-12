<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\User;
use App\Entity\Kldi;
use App\Entity\UserPicDocument;
use App\Repository\UserPicDocumentRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helper\StaticHelper;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\Annotation\Route;

class UserPicDocumentController extends PublicController
{
    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();

        if($user->getRoles()[0] != "ROLE_USER_GOVERNMENT") {
            return $this->redirectToRoute('login');
        } else {
            if ($user->getSubRole() != 'PPK' && $user->getSubRole() == null && $user->getSubRole() == "PP") {
                return $this->redirectToRoute('login');
            } else if($user->getSubRole() == 'TREASURER') {
                return $this->redirectToRoute('login');
            }
        }
        
        /** @var UserPicDocumentRepository $repository */
        $repository = $this->getRepository(UserPicDocument::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        //$keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            'order_by' => 'upd.id',
            'sort_by' => 'DESC',
        ];

        //if (!empty($keywords)) {
        //    $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
        //}

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
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.data_pic')]);

        return $this->view('@__main__/public/user/user_pic_document/index.html.twig', [
            'documents' => $documents,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }


    public function new()
    {
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_pic_save';
        $repository = $this->getRepository(Kldi::class);

        BreadcrumbService::add(['label' => $this->getTranslation('label.add_data_pic')]);

        return $this->view('@__main__/public/user/user_pic_document/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'klpd' => $repository->findAll(),
            'token_id' => $tokenId,
        ]);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_pic_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $userPicDocument = new UserPicDocument();
            $userPicDocument->setName(filter_var($formData['pic_name'], FILTER_SANITIZE_STRING));
            $userPicDocument->setUnit(filter_var($formData['pic_unit'], FILTER_SANITIZE_STRING));
            $userPicDocument->setEmail(filter_var($formData['pic_email'], FILTER_SANITIZE_STRING));
            $userPicDocument->setAddress(filter_var($formData['pic_address'], FILTER_SANITIZE_STRING));
            $userPicDocument->setNotelp(filter_var($formData['pic_telp'], FILTER_SANITIZE_STRING));
            $userPicDocument->setSatker(filter_var($formData['pic_satker'], FILTER_SANITIZE_STRING));
            $userPicDocument->setKlpd(filter_var($formData['pic_kldi'], FILTER_SANITIZE_STRING));


            $validator = $this->getValidator();
            $userPicDocumentErrors = $validator->validate($userPicDocument);

            if (count($userPicDocumentErrors) === 0) {
                /** @var User $user */
                $user = $this->getUser();
                $userPicDocument->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($userPicDocument);
                $em->flush();

                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall Pemberitahuan untuk PIC');
                    $mailToSeller->setMailTemplate('@__main__/email/new_user_ppk_treasurer.html.twig');
                    $mailToSeller->setMailRecipient($formData['pic_email']);
                    $mailToSeller->setMailData([
                        'name' => $formData['pic_name'],
                        'pp' => $this->getUser()->getUsername(),
                        'satker' => $formData['pic_satker'],
                        'klpd' => $formData['pic_kldi'],
                        'type' => 'pic',
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {
                    dd($exception);
                }

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_pic_created')
                );
            } else {
                $errors = [];
                $route = 'user_pic_new';

                foreach ($userPicDocumentErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('form_data', $formData);
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute($route);
    }

    public function edit($id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPicDocumentRepository $repository */
        $repository = $this->getRepository(UserPicDocument::class);
        $kldiRepository = $this->getRepository(Kldi::class);
        /** @var UserPicDocument $userTaxDocument */
        $userPicDocument = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (empty($userPicDocument)) {
            throw new NotFoundHttpException(sprintf('Unable to find tax document with id "%s" for user id "%s"', $id, $user->getId()));
        }
        
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_tax_edit';

        $formData = [
            'id' => $userPicDocument->getId(),
            'name' => $userPicDocument->getName(),
            'unit' => $userPicDocument->getUnit(),
            'email' => $userPicDocument->getEmail(),
            'notelp' => $userPicDocument->getNotelp(),
            'address' => $userPicDocument->getAddress(),
            'kldi' => $userPicDocument->getKlpd(),
            'satker' => $userPicDocument->getSatker(),
        ];

        BreadcrumbService::add(['label' => $this->getTranslation('label.data_pic')]);

        return $this->view('@__main__/public/user/user_pic_document/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'klpd' => $kldiRepository->findAll(),
            'token_id' => $tokenId,
        ]);
    }

    public function update(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_pic_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            $repository = $this->getRepository(UserPicDocument::class);
            $userPicDocument = $repository->find($formData['id']);
            $userPicDocument->setName(filter_var($formData['pic_name'], FILTER_SANITIZE_STRING));
            $userPicDocument->setUnit(filter_var($formData['pic_unit'], FILTER_SANITIZE_STRING));
            $userPicDocument->setEmail(filter_var($formData['pic_email'], FILTER_SANITIZE_STRING));
            $userPicDocument->setAddress(filter_var($formData['pic_address'], FILTER_SANITIZE_STRING));
            $userPicDocument->setNotelp(filter_var($formData['pic_telp'], FILTER_SANITIZE_STRING));

            $validator = $this->getValidator();
            $userPicDocumentErrors = $validator->validate($userPicDocument);

            if (count($userPicDocumentErrors) === 0) {
                /** @var User $user */
                $user = $this->getUser();
                $userPicDocument->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($userPicDocument);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_pic_updated')
                );
            } else {
                $errors = [];
                $route = 'user_tax_new';

                foreach ($userPicDocumentErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('form_data', $formData);
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute($route);
    }


    public function delete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $picId = abs($request->request->get('id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        if ($userId === (int) $user->getId()) {
            /** @var UserPicDocumentRepository $repository */
            $repository = $this->getRepository(UserPicDocument::class);
            /** @var UserPicDocument $userPicDocument */
            $userPicDocument = $repository->findOneBy([
                'id' => $picId,
                'user' => $user,
            ]);

                $em = $this->getEntityManager();
                $em->remove($userPicDocument);
                $em->flush();

                // Do not delete file as it might be used in order detail
                //unlink($file);

                $response['deleted'] = true;
            
        }

        return $this->view('', $response, 'json');
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate('user_tax_index', $query);
        };
    }
}
