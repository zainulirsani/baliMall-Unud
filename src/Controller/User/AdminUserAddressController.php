<?php

namespace App\Controller\User;

use App\Controller\AdminController;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Repository\UserRepository;

class AdminUserAddressController extends AdminController
{
    public function save()
    {
        $this->isAjaxRequest('POST');

        $translator = $this->getTranslator();
        $request = $this->getRequest();
        $title = $request->request->get('title', '');
        $address = $request->request->get('address', '');
        $city = $request->request->get('city', '');
        $cityId = abs($request->request->get('city_id', '0'));
        $district = $request->request->get('district', '');
        //$districtId = abs($request->request->get('district_id', '0'));
        $province = $request->request->get('province', '');
        $provinceId = abs($request->request->get('province_id', '0'));
        //$country = $request->request->get('country', '');
        //$countryId = abs($request->request->get('country_id', '0'));
        $postCode = $request->request->get('post_code', '');
        $userId = abs($request->request->get('user_id', '0'));
        $response = [
            'status' => false,
            'type' => 'save',
            'message' => $translator->trans('message.error.create_user_address'),
        ];

        $addressLat = $request->request->get('address_lat');
        $addressLng = $request->request->get('address_lng');

        $user = $this->getRepository(User::class)->find($userId);

        if ($user instanceof User) {
            $userAddress = new UserAddress();
            $userAddress->setUser($user);
            $userAddress->setTitle(filter_var($title, FILTER_SANITIZE_STRING));
            $userAddress->setAddress(filter_var($address, FILTER_SANITIZE_STRING));
            $userAddress->setCity(filter_var($city, FILTER_SANITIZE_STRING));
            $userAddress->setCityId($cityId);
            $userAddress->setDistrict(filter_var($district, FILTER_SANITIZE_STRING));
            $userAddress->setDistrictId(0);
            $userAddress->setProvince(filter_var($province, FILTER_SANITIZE_STRING));
            $userAddress->setProvinceId($provinceId);
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);
            $userAddress->setPostCode(filter_var($postCode, FILTER_SANITIZE_STRING));

            if (!empty($addressLat) && !empty($addressLng)) {
                $userAddress->setAddressLat($addressLat);
                $userAddress->setAddressLng($addressLng);
            }

            $validator = $this->getValidator();
            $userAddressErrors = $validator->validate($userAddress);

            if (count($userAddressErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($userAddress);
                $em->flush();

                $response['status'] = true;

                $response['message'] = $translator->trans('message.success.user_address_created');

                $response['content'] = $this->renderView('@__main__/admin/user/address/content.html.twig', [
                    'address' => $userAddress,
                    'type' => 'edit',
                    'addresses_count' => count($user->getAddresses())
                ]);

            } else {
                $errors = [];

                foreach ($userAddressErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $response['errors'] = $errors;
            }
        }

        return $this->view('', $response, 'json');
    }

    public function edit($id = null)
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $addressId = abs($request->request->get('address_id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        /** @var UserRepository $user */
        $user = $this->getRepository(User::class)->find($userId);
        /** @var UserAddress $userAddress */
        $userAddress = $this->getRepository(UserAddress::class)->findOneBy([
            'user' => $user,
            'id' => $addressId,
        ]);

        $response = [
            'status' => false,
            'address' => [],
        ];

        if ($userAddress instanceof UserAddress) {
            $response['status'] = true;
            $response['address'] = [
                'id' => $userAddress->getId(),
                'user_id' => $userId,
                'title' => $userAddress->getTitle(),
                'address' => $userAddress->getAddress(),
                'city' => $userAddress->getCity(),
                'city_id' => $userAddress->getCityId(),
                'district' => $userAddress->getDistrict(),
                'district_id' => $userAddress->getDistrictId(),
                'province' => $userAddress->getProvince(),
                'province_id' => $userAddress->getProvinceId(),
                'county' => $userAddress->getCountry(),
                'county_id' => $userAddress->getCountryId(),
                'post_code' => $userAddress->getPostCode(),
                'address_lat' => $userAddress->getAddressLat(),
                'address_lng' => $userAddress->getAddressLng(),
            ];
        }

        return $this->view('', $response, 'json');
    }

    public function update($id = null)
    {
        $this->isAjaxRequest('POST');

        $translator = $this->getTranslator();
        $request = $this->getRequest();
        $title = $request->request->get('title', '');
        $address = $request->request->get('address', '');
        $city = $request->request->get('city', '');
        $cityId = abs($request->request->get('city_id', '0'));
        $district = $request->request->get('district', '');
        //$districtId = abs($request->request->get('district_id', '0'));
        $province = $request->request->get('province', '');
        $provinceId = abs($request->request->get('province_id', '0'));
        //$country = $request->request->get('country', '');
        //$countryId = abs($request->request->get('country_id', '0'));
        $postCode = $request->request->get('post_code', '');
        $addressId = abs($request->request->get('address_id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        $response = [
            'status' => false,
            'type' => 'update',
            'message' => $translator->trans('message.error.update_user_address'),
        ];

        $addressLat = $request->request->get('address_lat');
        $addressLng = $request->request->get('address_lng');

        $user = $this->getRepository(User::class)->find($userId);
        $userAddress = $this->getRepository(UserAddress::class)->find($addressId);

        if ($user instanceof User && $userAddress instanceof UserAddress) {
            /** @var User $tempUser */
            $tempUser = $userAddress->getUser();

            if ((int) $tempUser->getId() === (int) $userId) {
                $userAddress->setTitle(filter_var($title, FILTER_SANITIZE_STRING));
                $userAddress->setAddress(filter_var($address, FILTER_SANITIZE_STRING));
                $userAddress->setCity(filter_var($city, FILTER_SANITIZE_STRING));
                $userAddress->setCityId($cityId);
                $userAddress->setDistrict(filter_var($district, FILTER_SANITIZE_STRING));
                $userAddress->setDistrictId(0);
                $userAddress->setProvince(filter_var($province, FILTER_SANITIZE_STRING));
                $userAddress->setProvinceId($provinceId);
                $userAddress->setCountry('ID');
                $userAddress->setCountryId(0);
                $userAddress->setPostCode(filter_var($postCode, FILTER_SANITIZE_STRING));

                if (!empty($addressLat) && !empty($addressLng)) {
                    $userAddress->setAddressLat(round($addressLat, 8));
                    $userAddress->setAddressLng(round($addressLng, 8));
                }

                $validator = $this->getValidator();
                $userAddressErrors = $validator->validate($userAddress);

                if (count($userAddressErrors) === 0) {
                    $em = $this->getEntityManager();
                    $em->persist($userAddress);
                    $em->flush();

                    $response['status'] = true;
                    $response['message'] = $translator->trans('message.success.user_address_updated');
                    $response['address_id'] = $addressId;
                    $response['content'] = $this->renderView('@__main__/admin/user/address/content_detail.html.twig', [
                        'address' => $userAddress,
                        'type' => 'edit',
                        'addresses_count' => count($user->getAddresses())
                    ]);
                } else {
                    $errors = [];

                    foreach ($userAddressErrors as $error) {
                        $errors[$error->getPropertyPath()] = $error->getMessage();
                    }

                    $response['errors'] = $errors;
                }
            }
        }

        return $this->view('', $response, 'json');
    }

    public function delete()
    {
        $this->isAjaxRequest('POST');

        $translator = $this->getTranslator();
        $request = $this->getRequest();
        $addressId = abs($request->request->get('address_id', '0'));
        $userId = abs($request->request->get('user_id', '0'));
        $response = [
            'deleted' => false,
            'message' => $translator->trans('message.error.delete_user_address'),
        ];

        $user = $this->getRepository(User::class)->find($userId);
        $userAddress = $this->getRepository(UserAddress::class)->find($addressId);

        if ($user instanceof User && $userAddress instanceof UserAddress) {
            /** @var User $tempUser */
            $tempUser = $userAddress->getUser();

            if ((int) $tempUser->getId() === (int) $userId) {
                $em = $this->getEntityManager();
                $em->remove($userAddress);
                $em->flush();

                $response['deleted'] = true;
                $response['message'] = $translator->trans('message.success.user_address_deleted');
                $response['address_id'] = $addressId;
            }
        }

        return $this->view('', $response, 'json');
    }
}
