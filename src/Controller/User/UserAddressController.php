<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Repository\UserAddressRepository;
use App\Service\BreadcrumbService;
use App\Service\RajaOngkirService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserAddressController extends PublicController
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

        /** @var UserAddressRepository $repository */
        $repository = $this->getRepository(UserAddress::class);
        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $keywords = $request->query->get('keywords', null);
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            'order_by' => 'ua.id',
            'sort_by' => 'DESC',
        ];

        if (!empty($keywords)) {
            $parameters['keywords'] = filter_var($keywords, FILTER_SANITIZE_STRING);
        }

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
            $addresses = $adapter->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $addresses = [];
            $pagination = $html = null;
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.address')]);

        return $this->view('@__main__/public/user/address/index.html.twig', [
            'addresses' => $addresses,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    public function new()
    {
        $flashBag = $this->get('session.flash_bag');
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $tokenId = 'user_address_save';

        BreadcrumbService::add(['label' => $this->getTranslation('label.address_add')]);

        return $this->view('@__main__/public/user/address/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
        ]);
    }

    public function save(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_address_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            $userAddress = new UserAddress();
            $userAddress->setTitle(filter_var($formData['title'], FILTER_SANITIZE_STRING));
            $userAddress->setAddress($formData['address']);
            $userAddress->setPostCode(filter_var($formData['post_code'], FILTER_SANITIZE_STRING));
            $userAddress->setCity(filter_var($formData['city'], FILTER_SANITIZE_STRING));
            $userAddress->setCityId((int) $formData['city_id']);
            $userAddress->setDistrict(filter_var($formData['district'], FILTER_SANITIZE_STRING));
            $userAddress->setDistrictId(0);
            $userAddress->setProvince(filter_var($formData['province'], FILTER_SANITIZE_STRING));
            $userAddress->setProvinceId((int) $formData['province_id']);
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);
            $userAddress->setAddressLat($formData['lat']);
            $userAddress->setAddressLng($formData['lng']);

            $validator = $this->getValidator();
            $userAddressErrors = $validator->validate($userAddress);

            if (count($userAddressErrors) === 0) {
                /** @var User $user */
                $user = $this->getUser();
                $userAddress->setUser($user);

                $em = $this->getEntityManager();
                $em->persist($userAddress);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_address_created')
                );
            } else {
                $errors = [];
                $route = 'user_address_new';

                foreach ($userAddressErrors as $error) {
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
        $addressId = abs($request->request->get('id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        if ($userId === (int) $user->getId()) {
            /** @var UserAddressRepository $repository */
            $repository = $this->getRepository(UserAddress::class);
            /** @var UserAddress $userAddress */
            $userAddress = $repository->findOneBy([
                'id' => $addressId,
                'user' => $user,
            ]);

            $em = $this->getEntityManager();
            $em->remove($userAddress);
            $em->flush();

            $response['deleted'] = true;
        }

        return $this->view('', $response, 'json');
    }

    public function edit($id)
    {
        $flashBag = $this->get('session.flash_bag');
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $tokenId = 'user_address_update';
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserAddressRepository $repository */
        $repository = $this->getRepository(UserAddress::class);
        /** @var UserAddress $userAddress */
        $userAddress = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        $formData = [
            'id' => $userAddress->getId(),
            'title' => $userAddress->getTitle(),
            'address' => $userAddress->getAddress(),
            'post_code' => $userAddress->getPostCode(),
            'city' => $userAddress->getCity(),
            'city_id' => $userAddress->getCityId(),
            'district' => $userAddress->getDistrict(),
            'district_id' => $userAddress->getDistrictId(),
            'province' => $userAddress->getProvince(),
            'province_id' => $userAddress->getProvinceId(),
            'county' => $userAddress->getCountry(),
            'county_id' => $userAddress->getCountryId(),
            'addressLat' => $userAddress->getAddressLat(),
            'addressLng' => $userAddress->getAddressLng()
        ];

        BreadcrumbService::add(['label' => $this->getTranslation('label.address_update')]);

        return $this->view('@__main__/public/user/address/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
        ]);
    }

    public function update($id): RedirectResponse
    {
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            /** @var User $user */
            $user = $this->getUser();
            /** @var UserAddressRepository $repository */
            $repository = $this->getRepository(UserAddress::class);
            /** @var UserAddress $userAddress */
            $userAddress = $repository->findOneBy([
                'id' => $id,
                'user' => $user,
            ]);

            $userAddress->setTitle(filter_var($formData['title'], FILTER_SANITIZE_STRING));
            $userAddress->setAddress($formData['address']);
            $userAddress->setPostCode(filter_var($formData['post_code'], FILTER_SANITIZE_STRING));
            $userAddress->setCity(filter_var($formData['city'], FILTER_SANITIZE_STRING));
            $userAddress->setCityId((int) $formData['city_id']);
            $userAddress->setDistrict(filter_var($formData['district'], FILTER_SANITIZE_STRING));
            $userAddress->setDistrictId(0);
            $userAddress->setProvince(filter_var($formData['province'], FILTER_SANITIZE_STRING));
            $userAddress->setProvinceId((int) $formData['province_id']);
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);
            $userAddress->setAddressLat($formData['lat']);
            $userAddress->setAddressLng($formData['lng']);

            $validator = $this->getValidator();
            $userAddressErrors = $validator->validate($userAddress);

            if (count($userAddressErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($userAddress);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_address_updated')
                );
            } else {
                $errors = [];

                foreach ($userAddressErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirectToRoute('user_address_edit', ['id' => $id]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            $query = ['page' => $page];

            if (isset($parameters['keywords'])) {
                $query['keywords'] = $parameters['keywords'];
            }

            return $this->get('router')->generate('user_address_index', $query);
        };
    }
}
