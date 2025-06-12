<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\User;
use App\Entity\UserTaxDocument;
use App\Repository\UserTaxDocumentRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserTaxDocumentController extends PublicController
{
    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();

        if($user->getRoles()[0] != "ROLE_USER_GOVERNMENT") { 
            return $this->redirectToRoute('login');
        } else {
            if($user->getSubRole() != 'PPK' && $user->getSubRole() != null && $user->getSubRole() != "PP") {
                return $this->redirectToRoute('login');
            }
        }

        /** @var UserTaxDocumentRepository $repository */
        $repository = $this->getRepository(UserTaxDocument::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        //$keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            'order_by' => 'utd.id',
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

        BreadcrumbService::add(['label' => $this->getTranslation('label.tax_document')]);

        return $this->view('@__main__/public/user/tax/index.html.twig', [
            'documents' => $documents,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    public function new()
    {
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_tax_save';

        BreadcrumbService::add(['label' => $this->getTranslation('label.add_tax_document')]);

        return $this->view('@__main__/public/user/tax/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
        ]);
    }

    public function update(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_tax_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            $email = $formData['email'];
            $phone = $formData['phone'];
            $sameAsProfile = (isset($formData['use_profile']) && abs($formData['use_profile']) === 1);

            if ($sameAsProfile) {
                $email = $user->getEmailCanonical();

                if (!empty($user->getPhoneNumber())) {
                    $phone = $user->getPhoneNumber();
                }
            }
            $repository = $this->getRepository(UserTaxDocument::class);
            $userTaxDocument = $repository->find($formData['id']);
            $userTaxDocument->setTitle(filter_var($formData['title'], FILTER_SANITIZE_STRING));
            $userTaxDocument->setNumber(filter_var($formData['number'], FILTER_SANITIZE_STRING));
            $userTaxDocument->setEmail(filter_var($email, FILTER_SANITIZE_STRING));
            $userTaxDocument->setPhone(filter_var($phone, FILTER_SANITIZE_STRING));
            $userTaxDocument->setSameAsProfile($sameAsProfile);

            if (isset($formData['image_temp'])) {
                $image = filter_var($formData['image_temp'], FILTER_SANITIZE_STRING);
                $image = ltrim($image, '/');

                $userTaxDocument->setImage($image);
            }

            $validator = $this->getValidator();
            $userTaxDocumentErrors = $validator->validate($userTaxDocument);

            if (count($userTaxDocumentErrors) === 0) {
                /** @var User $user */
                $user = $this->getUser();
                $userTaxDocument->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($userTaxDocument);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_tax_created')
                );
            } else {
                $errors = [];
                $route = 'user_tax_new';

                foreach ($userTaxDocumentErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('form_data', $formData);
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute($route);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_tax_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            $email = $formData['email'];
            $phone = $formData['phone'];
            $sameAsProfile = (isset($formData['use_profile']) && abs($formData['use_profile']) === 1);

            if ($sameAsProfile) {
                $email = $user->getEmailCanonical();

                if (!empty($user->getPhoneNumber())) {
                    $phone = $user->getPhoneNumber();
                }
            }

            $userTaxDocument = new UserTaxDocument();
            $userTaxDocument->setTitle(filter_var($formData['title'], FILTER_SANITIZE_STRING));
            $userTaxDocument->setNumber(filter_var($formData['number'], FILTER_SANITIZE_STRING));
            $userTaxDocument->setEmail(filter_var($email, FILTER_SANITIZE_STRING));
            $userTaxDocument->setPhone(filter_var($phone, FILTER_SANITIZE_STRING));
            $userTaxDocument->setSameAsProfile($sameAsProfile);

            if (isset($formData['image_temp'])) {
                $image = filter_var($formData['image_temp'], FILTER_SANITIZE_STRING);
                $image = ltrim($image, '/');

                $userTaxDocument->setImage($image);
            }

            $validator = $this->getValidator();
            $userTaxDocumentErrors = $validator->validate($userTaxDocument);

            if (count($userTaxDocumentErrors) === 0) {
                /** @var User $user */
                $user = $this->getUser();
                $userTaxDocument->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($userTaxDocument);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_tax_created')
                );
            } else {
                $errors = [];
                $route = 'user_tax_new';

                foreach ($userTaxDocumentErrors as $error) {
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
        $taxId = abs($request->request->get('id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        if ($userId === (int) $user->getId()) {
            /** @var UserTaxDocumentRepository $repository */
            $repository = $this->getRepository(UserTaxDocument::class);
            /** @var UserTaxDocument $userTaxDocument */
            $userTaxDocument = $repository->findOneBy([
                'id' => $taxId,
                'user' => $user,
            ]);
            $file = $this->getParameter('public_dir_path').'/'.$userTaxDocument->getImage();

            if (is_file($file)) {
                $em = $this->getEntityManager();
                $em->remove($userTaxDocument);
                $em->flush();

                // Do not delete file as it might be used in order detail
                //unlink($file);

                $response['deleted'] = true;
            }
        }

        return $this->view('', $response, 'json');
    }

    public function edit($id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserTaxDocumentRepository $repository */
        $repository = $this->getRepository(UserTaxDocument::class);
        /** @var UserTaxDocument $userTaxDocument */
        $userTaxDocument = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (empty($userTaxDocument)) {
            throw new NotFoundHttpException(sprintf('Unable to find tax document with id "%s" for user id "%s"', $id, $user->getId()));
        }
        
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_tax_edit';

        $formData = [
            'id' => $userTaxDocument->getId(),
            'title' => $userTaxDocument->getTitle(),
            'number' => $userTaxDocument->getNumber(),
            'email' => $userTaxDocument->getEmail(),
            'phone' => $userTaxDocument->getPhone(),
            'image' => $userTaxDocument->getImage(),
            'use_profile' => $userTaxDocument->getSameAsProfile(),
        ];

        BreadcrumbService::add(['label' => $this->getTranslation('label.tax_document')]);

        return $this->view('@__main__/public/user/tax/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
        ]);
    }

    public function detail($id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserTaxDocumentRepository $repository */
        $repository = $this->getRepository(UserTaxDocument::class);
        /** @var UserTaxDocument $userTaxDocument */
        $userTaxDocument = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (empty($userTaxDocument)) {
            throw new NotFoundHttpException(sprintf('Unable to find tax document with id "%s" for user id "%s"', $id, $user->getId()));
        }

        $formData = [
            'id' => $userTaxDocument->getId(),
            'title' => $userTaxDocument->getTitle(),
            'number' => $userTaxDocument->getNumber(),
            'email' => $userTaxDocument->getEmail(),
            'phone' => $userTaxDocument->getPhone(),
            'image' => $userTaxDocument->getImage(),
            'use_profile' => $userTaxDocument->getSameAsProfile(),
        ];

        BreadcrumbService::add(['label' => $this->getTranslation('label.tax_document')]);

        return $this->view('@__main__/public/user/tax/view.html.twig', [
            'form_data' => $formData,
        ]);
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
