<?php

namespace App\Controller\Site;

use App\Controller\PublicController;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\HttpClientException;
use App\Helper\StaticHelper;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\HttpClientService;
use App\Service\SwiftMailerService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HelperPageController extends PublicController
{
    public function timeRemaining()
    {
        $this->isAjaxRequest('POST');

        $response = ['status' => false];

        if ($this->getUser()) {
            $requestTime = time();
            $LoginTime = (int) $this->getSession()->get('login_time');
            $expirationTime = $this->getParameter('session_expiration');
            $timeRemaining = $expirationTime - ($requestTime - $LoginTime);

            $response['status'] = true;
            $response['data'] = $timeRemaining;
        }

        return $this->view('', $response, 'json');
    }

    public function setActiveLocale()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $locale = strtolower($request->request->get('locale', 'id'));
        $current = $this->getLocale();

        if ($current !== $locale) {
            $this->setLocale($locale);
            $this->getSession()->set('_current_locale', $locale);
        }

        return $this->view('', ['status' => $current !== $locale], 'json');
    }

    public function geocode()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $address = $request->request->get('address', null);
        $lat = $request->request->get('lat', null);
        $lng = $request->request->get('lng', null);
        $placeId = $request->request->get('place_id', null);
        $search = $request->request->get('search', null);
        $save = false;
        $response = [];
        $options = [
            'timeout' => 10, // Set timeout connection to 10 seconds
            'query' => [
                'key' => $this->getParameter('google_map_server_key'),
                'language' => 'id',
            ]
        ];

        if (!empty($address)) {
            $options['query']['address'] = filter_var($address, FILTER_SANITIZE_STRING);
        }

        if (!empty($lat) && !empty($lng)) {
            $options['query']['latlng'] = sprintf('%s,%s', $lat, $lng);
        }

        if (!empty($placeId)) {
            $options['query']['place_id'] = filter_var($placeId, FILTER_SANITIZE_STRING);
        }

        if (!empty($search) && $search === 'search_query') {
            $save = true;
        }

        try {
            $geocode = HttpClientService::run('https://maps.googleapis.com/maps/api/geocode/json', $options);

            if (!$geocode['error']) {
                $city = $district = $province = $country = $postal = '';
                $provinceData = $this->getProvinceList('ID', 'id');

                foreach ($geocode['data']['results'] as $key => $component) {
                    foreach ($component['address_components'] as $num => $value) {
                        if (isset($value['types'][0]) && $value['types'][0] === 'administrative_area_level_4') {
                            $city = $value['long_name'];
                        }

                        if (isset($value['types'][0]) && $value['types'][0] === 'administrative_area_level_3') {
                            $city = $value['long_name'];
                        }

                        if (isset($value['types'][0]) && $value['types'][0] === 'administrative_area_level_2') {
                            $district = $value['long_name'];
                        }

                        if (isset($value['types'][0]) && $value['types'][0] === 'administrative_area_level_1') {
                            $province = $value['long_name'];
                        }

                        if (isset($value['types'][0]) && $value['types'][0] === 'country') {
                            $country = $value['long_name'];
                        }

                        if (isset($value['types'][0]) && $value['types'][0] === 'postal_code') {
                            $postal = $value['long_name'];
                        }
                    }

                    $response[$key] = [
                        'lat' => $component['geometry']['location']['lat'],
                        'lng' => $component['geometry']['location']['lng'],
                        'place_id' => $component['place_id'],
                        'formatted_address' => $component['formatted_address'],
                        'address_components' => [
                            'city' => $city,
                            'district' => $district,
                            'province' => $province,
                            'province_en' => array_search($province, $provinceData, false),
                            'postal' => $postal,
                            'country' => $country,
                        ]
                    ];
                }

                if (count($response) > 0) {
                    if ($save) {
                        $this->getSession()->set('temp_address_search', $response[0]['formatted_address']);
                    } else {
                        $this->getSession()->remove('temp_address_search');
                    }
                }
            }
        } catch (HttpClientException $e) {
            $response['status'] = false;
            $response['message'] = $this->getTranslator()->trans('message.error.timeout');
        }

        return $this->view('', $response, 'json');
    }

    public function emailActivation($code): RedirectResponse
    {
        $redirect = 'homepage';

        if (($code && !empty($code)) && !$this->getUser()) {
            /** @var UserRepository $repository */
            $repository = $this->getRepository(User::class);
            /** @var User $user */
            $user = $repository->findOneBy(['activationCode' => $code]);

            if ($user instanceof User) {
                $redirect = 'login';

                $user->setIsActive(true);
                $user->setIsDeleted(false);
                $user->setActivationCode(null);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $dataUser = [
                    'name' => $user->getFirstName(),
                    'link' => $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'subject' => $this->getTranslator()->trans('message.success.activation'),
                ];
                $bodyEmail = $this->renderView('@__main__/email/activation.html.twig', $dataUser);
                $contentEmail = [
                    'to' => $user->getEmail(),
                    'from' => $this->getParameter('mail_sender'),
                    'subject' => $dataUser['subject'],
                    'body' => $bodyEmail,
                    'content_type' => 'text/html',
                ];

                $this->get(SwiftMailerService::class)->send($contentEmail);

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.activation')
                );
            }
        }

        return $this->redirectToRoute($redirect);
    }

    public function emailCheck()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $userId = abs($request->request->get('user_id', '0'));
        $email = $request->request->get('email', null);
        $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        $message = 'user.email_not_valid';
        $status = false;

        if ($validEmail) {
            $email = StaticHelper::checkEmailCanonical($email);
            /** @var UserRepository $repository */
            $repository = $this->getRepository(User::class);
            $exist = $repository->checkExistingEmail($email, $userId);
            $message = '';

            if (!$exist) {
                $message = 'user.email_valid';
                $status = true;
            }
        }

        $response = [
            'status' => $status,
            'message' => $this->getTranslator()->trans($message, [], 'validators'),
        ];

        return $this->view('', $response, 'json');
    }

    public function forgotPassword()
    {
        $request = $this->getRequest();
        $tokenId = 'user_forgot_pass';
        $messages = [];

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', null);
            $email = StaticHelper::checkEmailCanonical($email);
            $message = 'message.error.invalid_email';
            $translator = $this->getTranslator();

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                /** @var UserRepository $repository */
                $repository = $this->getRepository(User::class);
                /** @var User $user */
                $user = $repository->findOneBy(['emailCanonical' => $email]);
                //$message = 'message.error.invalid_user_alt';

                $roles = $this->getParameter('user_roles');
                unset($roles['ROLE_ADMIN']);
                $reserved = array_keys($roles);

                if ($user instanceof User
                    && (in_array($user->getRole(), $reserved, false)
                        && (int) $user->getIsActive() === 1
                        && (int) $user->getIsDeleted() === 0)) {
                    $code = $user->getForgotPasswordCode();

                    if (empty($code)) {
                        $code = StaticHelper::secureRandomCode();
                        $user->setForgotPasswordCode($code);

                        $em = $this->getEntityManager();
                        $em->persist($user);
                        $em->flush();
                    }

                    $message = 'message.success.send_mail_recover_alt';
                    $dataRecover = [
                        'name' => $user->getFirstName(),
                        'link' => $this->generateUrl('recover_password', ['code' => $code], UrlGeneratorInterface::ABSOLUTE_URL),
                    ];
                    $subject = $translator->trans('message.info.recover_password');
                    $bodyEmail = $this->renderView('@__main__/email/forgot_password.html.twig', $dataRecover);
                    $contentEmail = [
                        'to' => $user->getEmail(),
                        'from' => $this->getParameter('mail_sender'),
                        'subject' => $subject,
                        'body' => $bodyEmail,
                        'content_type' => 'text/html',
                    ];

                    $this->get(SwiftMailerService::class)->send($contentEmail);
                }
            }

            $messages['email'] = $translator->trans($message);
        }

        return $this->view('@__main__/public/site/helper_page/forgot_password.html.twig', [
            'page_title' => 'title.forgot_password',
            'token_id' => $tokenId,
            'messages' => $messages,
        ]);
    }

    public function recoverPassword(UserPasswordEncoderInterface $encoder, $code)
    {
        $request = $this->getRequest();
        $tokenId = 'user_recover_pass';
        $messages = [];
        /** @var UserRepository $repository */
        $repository = $this->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy(['forgotPasswordCode' => $code]);

        if (!$user instanceof User || $this->getUser()) {
            return $this->redirectToRoute('user_dashboard');
        }

        if ($request->isMethod('POST')) {
            $translator = $this->getTranslator();
            $password = $request->request->get('password', null);
            $confirmPassword = $request->request->get('confirm_password', null);
            $message = 'message.error.empty_values';

            if (!empty($password) && !empty($confirmPassword)) {
                $message = 'message.info.password_not_match';

                if ($password === $confirmPassword) {
                    $password = $encoder->encodePassword($user, $password);

                    $user->setPassword($password);
                    $user->setForgotPasswordCode(null);

                    $em = $this->getEntityManager();
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        $translator->trans('message.success.recovery')
                    );

                    return $this->redirectToRoute('login');
                }
            }

            $messages['password'] = $translator->trans($message);
        }

        return $this->view('@__main__/public/site/helper_page/recover_password.html.twig', [
            'page_title' => 'title.forgot_password',
            'token_id' => $tokenId,
            'messages' => $messages,
            'code' => $code,
        ]);
    }

    public function findProduct()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $term = $request->request->get('term', '');
        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $products = $repository->getDataForComparison(['term' => $term]);
        $response = [
            'status' => false,
            'index' => abs($request->request->get('index', '1')),
            'data' => null,
        ];

        if (count($products) > 0) {
            $response['status'] = true;
            $response['data'] = $products;
        }

        return $this->view('', $response, 'json');
    }
}
