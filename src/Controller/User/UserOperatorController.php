<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Operator;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;

class UserOperatorController extends PublicController
{
    public function index()
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var ChatRepository $repository */
        $repository = $this->getRepository(Operator::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'order_by' => 'c.createdAt',
            'sort_by' => 'DESC',
            'user' => $user,
        ];

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));

            $pagination = new Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page);

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $operator = $adapter->getQuery()->getScalarResult();
        } catch (Exception $e) {

            $operator = [];
            $pagination = $html = null;
        }

        return $this->view('@__main__/public/user/operator/index.html.twig', [
            'operator' => $operator,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);

    }

    public function new()
    {
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_operator_save';

        BreadcrumbService::add(['label' => $this->getTranslation('label.add_workunit')]);

        return $this->view('@__main__/public/user/operator/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'store_owner' => $this->getUser()->getRole() === 'ROLE_USER_SELLER'
        ]);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_operator_index';
        $roles = $this->getParameter('admin_merchant_roles');

        if ($request->isMethod('POST')) {
            $validator = $this->getValidator();
            $formData = $request->request->all();
            $user = $this->getUser();

            $role = 'ROLE_SATKER';
            $workUnit = $formData['work_unit'] ?? null;

            if ($user->getRole() === 'ROLE_USER_SELLER') {
                $role = $formData['role'];

                if (!in_array($role, $roles) || $role === 'ROLE_ADMIN_MERCHANT_OWNER') {
                    $route = 'user_operator_new';

                    $errors['role'] = 'Invalid role';

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);

                    return $this->redirectToRoute($route);
                }

                $workUnit = null;
            }

            $userRepository = $this->getRepository(User::class);
            $userData = $userRepository->find($user->getId());

            $operator = new Operator();
            $operator->setOwner($userData);
            $operator->setFullname(filter_var($formData['fullname'], FILTER_SANITIZE_STRING));
            $operator->setPhone(filter_var($formData['phone'], FILTER_SANITIZE_STRING));
            $operator->setAddress(filter_var($formData['address'], FILTER_SANITIZE_STRING));
            $operator->setRole($role);

            if (isset($formData['work_unit'])) {
                $operator->setWorkUnit(filter_var($workUnit, FILTER_SANITIZE_STRING));
            }

            $operatorErrors = $validator->validate($operator);

            if ($user->getRole() !== 'ROLE_USER_SELLER') {
                if (empty($formData['work_unit'])) {
                    $message = $this->getTranslator()->trans('global.not_empty', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $operator, 'work_unit', '', null, null, new NotBlank(), null);

                    $operatorErrors->add($constraint);
                }
            }

            if (count($operatorErrors) === 0) {

                $em = $this->getEntityManager();
                $em->persist($operator);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_operator_created')
                );
            } else {
                $errors = [];
                $route = 'user_operator_new';

                foreach ($operatorErrors as $error) {
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
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_operator_update';
        /** @var User $user */
        $repository = $this->getRepository(Operator::class);
        $operator = $repository->findOneBy([
            'id' => $id,
        ]);

        $formData = [
            'id' => $operator->getId(),
            'fullname' => $operator->getFullname(),
            'role' => $operator->getRole(),
            'address' => $operator->getAddress(),
            'phone' => $operator->getPhone(),
            'work_unit' => $operator->getWorkUnit(),
        ];

        if ($formData['role'] === 'ROLE_ADMIN_MERCHANT_OWNER') {
            return $this->redirectToRoute('user_operator_index');
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.operator_update')]);

        return $this->view('@__main__/public/user/operator/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'is_edit' => true,
        ]);
    }

    public function update($id): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $redirect = 'user_operator_index';

        if ($request->isMethod('POST')) {
            $validator = $this->getValidator();
            $roles = $this->getParameter('admin_merchant_roles');
            $user = $this->getUser();

            $formData = $request->request->all();
            $repository = $this->getRepository(Operator::class);
            $operator = $repository->find($id);

            $operator->setFullname(filter_var($formData['fullname'], FILTER_SANITIZE_STRING));
            $operator->setAddress(filter_var($formData['address'], FILTER_SANITIZE_STRING));
            $operator->setPhone(filter_var($formData['phone'], FILTER_SANITIZE_STRING));

            if (isset($formData['work_unit'])) {
                $operator->setWorkUnit(filter_var($formData['work_unit'], FILTER_SANITIZE_STRING));
            }

            if (isset($formData['role']) && $user && $user->getRole() === 'ROLE_USER_SELLER') {
                $role = $formData['role'];

                if (!in_array($role, $roles)) {
                    $route = 'user_operator_new';

                    $errors['role'] = 'Invalid role';

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);

                    return $this->redirectToRoute($route);
                }

                $operator->setRole($role);
            }

            $operatorErrors = $validator->validate($operator);

            if ($user->getRole() !== 'ROLE_USER_SELLER') {
                if (empty($formData['work_unit'])) {
                    $message = $this->getTranslator()->trans('global.not_empty', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $operator, 'work_unit', '', null, null, new NotBlank(), null);

                    $operatorErrors->add($constraint);
                }
            }

            if (count($operatorErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($operator);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_operator_updated')
                );
            } else {
                $errors = [];

                foreach ($operatorErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('errors', $errors);

                $redirect = 'user_operator_edit';
            }
        }

        return $this->redirectToRoute($redirect, ['id' => $id]);
    }

    public function select()
    {
        $session = $this->getSession();
        $admin_session_key = 'admin_merchant';

        if ($session->has($admin_session_key)) {
            return $this->redirectToRoute('user_dashboard');
        }

        $request = $this->getRequest();
        $user = $this->getUser();

        $admin = $user->getOperators() ?? [];

        if ($request->getMethod() === 'POST') {
            $this->isAjaxRequest('POST');

            $response = [
                'error' => true,
                'code' => 200,
            ];

            $formData = $request->request;
            $uid = abs($formData->get('uid', 0));
            $operatorId = abs($formData->get('id', 0));

            if ($uid === (int)$user->getId()) {
                $repository = $this->getRepository(Operator::class);
                $operator = $repository->find($operatorId);

                if (!empty($operator) && $operator->getOwner()->getId() === $user->getId()) {

                    $session->set($admin_session_key, $operator);

                    $response['error'] = false;
                }
            }

            return new JsonResponse($response, $response['code']);
        }

        return $this->view('@__main__/public/user/operator/select.html.twig', [
            'admin' => $admin
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate('user_operator_index', $query);
        };
    }
}
