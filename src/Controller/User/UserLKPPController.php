<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\User;
use App\Helper\StaticHelper;
use App\Repository\UserRepository;
use App\Utility\GoogleMailHandler;
use ErrorException;
use Hashids\Hashids;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserLKPPController extends PublicController
{
    private $debugger = true;

    public function login(LoggerInterface $logger): RedirectResponse
    {
        $request = $this->getRequest();
        $nonce = $request->query->get('nonce', '');
        $category = (array) $request->query->get('category1', '');
        $canal = $request->get('kanal', '');
        $route = 'homepage';
        $routeParameters = [];

        $logger->error('LKPP Login', [$request->query->all(), $request->headers]);

        if (!empty($nonce)) {
            /** @var User $user */
            $user = $this->fetchUserEntity([
                'role' => 'ROLE_USER_GOVERNMENT',
                'isActive' => true,
                'lkppToken' => $nonce,
            ]);

            $logger->error('Nonce not empty');
            $logger->error('User lkpp', [$user]);

            if ($user instanceof User) {
                $logger->error('User exist');

                $expiration = $user->getLkppTokenExpiration();

                if (!empty($expiration) && time() < strtotime($expiration->format('Y-m-d H:i:s'))) {
                    $logger->error('Expiration passed');
                    $route = 'user_dashboard';
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    $session = $this->get('session');

                    $this->get('security.token_storage')->setToken($token);
                    $session->set('_security_main', serialize($token));

                    $user->setLkppLoginStatus('logged_in');

                    $em = $this->getEntityManager();
                    $em->persist($user);
                    $em->flush();

                    if (count($category) > 0) {
                        $logger->error('Category not empty');
                        $route = 'search';
                        $routeParameters = ['category1' => $category];

                        $session->set('user_lkpp_access_category', $category);
                    }

                    $restricted = $this->getParameter('lkpp_restricted_categories');
                    $logger->error('LKPP route parameters after login', $routeParameters);

                    if (is_array($restricted) && count($restricted) > 0) {
                        $logger->error('Restricted category not empty');
                        $restrictedData = (getenv('APP_URL') === 'https://tokodaring.balimall.id') ? $restricted['prod'] : $restricted['stage'];
                        $cacheKey = 'user_lkpp_restricted_categories';

                        $session->set($cacheKey, $restrictedData);

                        try {
                            $cache = $this->getCache();
                            /** @var CacheItem $restrictedCategories */
                            $restrictedCategories = $cache->getItem($cacheKey);

                            // if (!$restrictedCategories->isHit()) {
                                $restrictedCategories->set($restrictedData);
                                $cache->save($restrictedCategories);
                            // }
                        } catch (InvalidArgumentException $e) {
                        }
                    }

                    $merchantClassification = $this->getParameter('lkpp_restricted_merchant_classification');

                    if (is_array($merchantClassification) && count($merchantClassification) > 0) {
                        $canalCacheKey = 'user_lkpp_restricted_merchant_classification';

                        try {
                            $restrictedMerchantClassificationData = $merchantClassification[$canal];
                        }catch (\Throwable $throwable) {
                            $restrictedMerchantClassificationData = [];
                        }

                        $session->set($canalCacheKey, $restrictedMerchantClassificationData);

                        try {
                            $cache = $this->getCache();
                            $restrictedClasification = $cache->getItem($canalCacheKey);

                            $restrictedClasification->set($restrictedMerchantClassificationData);
                            $cache->save($restrictedClasification);

                        } catch (InvalidArgumentException $e) {
                        }
                    }
                }
            }
        }

        $logger->error('LKPP Login response', [$route, $routeParameters]);

        return $this->redirectToRoute($route, $routeParameters);
    }

    public function portalLogin(LoggerInterface $logger): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
            'data' => null,
        ];


        try {
            //$this->checkAllowedIp();
            $this->checkValidClient();

            $request = $this->getRequest();
            $verticalType = $request->headers->get('X-Vertical-Type', '');

            $logger->error('LKPP Portal Login', [$request->request->all(), $request->headers]);

            if ($verticalType === 'GET ACCESS TOKEN') {
                $this->internalLogger($logger, __METHOD__);

                $input = json_decode(file_get_contents('php://input'), true);
                $payload = $input['payload'] ?? [];
                $username = $payload['userName'] ?? '__lkpp_invalid_user__';
                $usernameKey = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'emailCanonical' : 'username';
                $lpseId = $payload['lpseId'] ?? '0';
                $lkppJwtToken = $input['token'] ?? null;
                $lkppRole = $payload['role'] ?? null;
                $lkppInstanceId = $payload['idInstansi'] ?? null;
                $lkppInstanceName = $payload['namaInstansi'] ?? null;
                $lkppWorkunitId = $payload['idSatker'] ?? null;
                $lkppWorkunitName = $payload['namaSatker'] ?? null;

                /** @var User $user */
                $user = $this->fetchUserEntity([
                    $usernameKey => GoogleMailHandler::validate($username),
                    'isActive' => true,
                    'role' => 'ROLE_USER_GOVERNMENT',
                ]);

                $logger->error('User LKPP Portal Login', [$user]);

                if (!empty($user)) {
                    if (!empty($user->getLkppLpseId())) {
                        if ((int) $user->getLkppLpseId() === (int) $lpseId) {
                            $token = $user->getLkppToken();
                            $expiration = !empty($user->getLkppTokenExpiration()) ? $user->getLkppTokenExpiration()->format('Y-m-d H:i:s') : null;

                            if (empty($token) || (!empty($expiration) && time() > strtotime($expiration))) {
                                do {
                                    $token = StaticHelper::secureRandomCode();
                                    $exist = $this->fetchUserEntity(['lkppToken' => $token]);
                                    $found = $exist ? 'no' : 'yes';
                                } while ($found === 'no');

                                $user->setLkppRole($lkppRole);
                                $user->setLkppToken($token);
                                $user->setLkppTokenExpiration();
                                $user->setLkppLoginStatus(null);
                                $user->setLkppJwtToken($lkppJwtToken);

                                $user->setLkppInstanceId($lkppInstanceId);
                                $user->setLkppInstanceName($lkppInstanceName);
                                $user->setLkppWorkunitId($lkppWorkunitId);
                                $user->setLkppWorkunitName($lkppWorkunitName);

                                $em = $this->getEntityManager();
                                $em->persist($user);
                                $em->flush();
                            }

                            $response['data'] = ['token' => $token];
                        } else {
                            $statusCode = 404;
                            $response['success'] = false;
                            $response['error'] = 'User not found!';
                        }
                    } else {
                        $statusCode = 409;
                        $response['success'] = false;
                        $response['error'] = 'User not linked to PAX!';
                    }
                } else {
                    $statusCode = 404;
                    $response['success'] = false;
                    $response['error'] = 'User not found!';
                }
            } else {
                $statusCode = 500;
                $response['success'] = false;
                $response['error'] = 'Invalid Vertical Type';
            }
        } catch (ErrorException $e) {
            $statusCode = 500;
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }

        return new JsonResponse($response, $statusCode);
    }

    public function portalLogout(LoggerInterface $logger): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
        ];

        try {
            //$this->checkAllowedIp();
            $this->checkValidClient();
            $this->internalLogger($logger, __METHOD__);

            $input = json_decode(file_get_contents('php://input'), true);
            $payload = $input['payload'] ?? [];
            $email = $payload['email'] ?? 'lkpp.invalid.user@balimall';

            if ($email === 'lkpp.invalid.user@balimall') {
                $statusCode = 500;
                $response['success'] = false;
                $response['error'] = 'Invalid user!';
            } else {
                /** @var User $user */
                $user = $this->fetchUserEntity([
                    'emailCanonical' => GoogleMailHandler::validate($email),
                    'role' => 'ROLE_USER_GOVERNMENT',
                    //'lkppLoginStatus' => 'logged_in',
                ]);

                if (!empty($user)) {
                    $user->setLkppToken(null);
                    $user->updateLkppTokenExpiration();
                    $user->setLkppLoginStatus('logged_out');
                    $user->setLkppJwtToken(null);

                    $em = $this->getEntityManager();
                    $em->persist($user);
                    $em->flush();
                } else {
                    $statusCode = 404;
                    $response['success'] = false;
                    $response['error'] = 'User not found!';
                }
            }
        } catch (ErrorException $e) {
            $statusCode = 500;
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }

        return new JsonResponse($response, $statusCode);
    }

    public function checkCsvByComma(){
        $keyname_check = null;
        $data = $this->parseCSV([
            'find_in' => __DIR__.'/../../../var/lkpp',
            'file_name' => 'users.csv',
            'separator' => ',',
            'ignore_first_line' => true,
        ]);
        // dd($data);
        foreach($data[0] as $key=>$value)
        {
            $keyname_check = $key;
        }
        if(substr_count(strtolower($keyname_check), ";") >= 5){
            return ';';
        }else{
            return ',';
        }
    }

    public function portalImport(UserPasswordEncoderInterface $encoder, LoggerInterface $logger): RedirectResponse
    {
        $session = $this->getSession();

        if (!$session->has('_security_admin')) {
            return $this->redirectToRoute('homepage');
        }

        $admin = $session->get('_security_admin');
        /** @var UsernamePasswordToken $admin */
        $admin = unserialize($admin, ['allowed_classes' => true]);
        $admin = $admin->getUser();
        $role = current($admin->getRoles());

        if (!in_array($role, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return $this->redirectToRoute('homepage');
        }
        $data = $this->parseCSV([
            'find_in' => __DIR__.'/../../../var/lkpp',
            'file_name' => 'users.csv',
            'separator' => $this->checkCsvByComma(),
            'ignore_first_line' => true,
        ]);

        // dd($data);

        if (count($data) > 0) {
            /** @var UserRepository $repository */
            $repository = $this->getRepository(User::class);
            $alphabet = getenv('HASHIDS_ALPHABET');
            $hash = new Hashids(User::class, 6, $alphabet);
            $em = $this->getEntityManager();
            $today = date('Y-m-d');
            $users = [];
            $userExists = [];
            // dd($data);

            foreach ($data as $item) {
                $username = $item['Username'];
                $email = $item['Email'];
                $emailCanonical = GoogleMailHandler::validate($email);
                // Checking point #1: by email
                $exist = $repository->findOneBy(['emailCanonical' => $emailCanonical]);

                if ($exist) {
                    $message = sprintf('User with email "%s" exists!', $emailCanonical);

                    if ($exist->getRole() !== 'ROLE_USER_GOVERNMENT') {
                        $message .= ' But the role is incorrect!';
                    }

                    $logger->error($message, $item);
                    $userExists[] = $emailCanonical;
                } else {
                    // Checking point #2: by username -- just in case
                    $exist = $repository->findOneBy(['username' => GoogleMailHandler::validate($username)]);
                    // dd($exist);
                    if ($exist) {
                        $userExists[] = $username;

                        $logger->error(sprintf('User with username "%s" exists with email: "%s"!', $username, $exist->getEmailCanonical()), $item);
                    } else {
                        $kldi = $item['Kldi'] ?? '';
                        $lpseId = $item['LPSE'] ?? '';
                        $ppName = $item['Nama PP'] ?? '';
                        $phoneNumber = $item['Phone_number'] ?? '';
                        $lkppEmployeeId = $item['Employee_id'] ?? '';
                        $lkppGroups = $item['Groups'] ?? '';
                        $lkppWorkUnit = $item['Satker'] ?? '';

                        $name = StaticHelper::splitFullName($ppName);
                        $plainPassword = 'lkpp-'.$lpseId.'-'.$today;

                        $user = new User();
                        $user->setUsername($username);
                        $user->setEmail($email);
                        $user->setEmailCanonical($emailCanonical);
                        $user->setRole('ROLE_USER_GOVERNMENT');
                        $user->setIsActive(true);
                        $user->setIsDeleted(false);
                        $user->setFirstName($name['first_name']);
                        $user->setLastName($name['last_name']);
                        $user->setPhoneNumber(filter_var($phoneNumber, FILTER_SANITIZE_STRING));
                        $user->setPpName(filter_var($ppName, FILTER_SANITIZE_STRING));
                        // $user->setPpkName(filter_var($kldi, FILTER_SANITIZE_STRING));
                        $user->setLkppLpseId(filter_var($lpseId, FILTER_SANITIZE_STRING));
                        $user->setLkppEmployeeId(filter_var($lkppEmployeeId, FILTER_SANITIZE_STRING));
                        $user->setLkppGroups(filter_var($lkppGroups, FILTER_SANITIZE_STRING));
                        $user->setLkppKLDI(filter_var($kldi, FILTER_SANITIZE_STRING));
                        $user->setLkppWorkUnit(filter_var($lkppWorkUnit, FILTER_SANITIZE_STRING));
                        $user->setNewsletter(false);
                        $user->setSubRole('PP');
                        $user->setPassword($encoder->encodePassword($user, $plainPassword));

                        $em->persist($user);

                        $users[] = $user;
                    }
                }
            }

            if (count($users) > 0) {
                $em->flush();

                foreach ($users as $user) {
                    $dirSlug = $hash->encode($user->getId());
                    /** @var User $duplicate */
                    $duplicate = $repository->findOneBy(['dirSlug' => $dirSlug]);

                    if ($duplicate instanceof User) {
                        $salt = 'App\Entity\DuplicateUser-'.date('YmdHis');
                        $duplicateHash = new Hashids($salt, 7, $alphabet);
                        $dirSlug = $duplicateHash->encode($user->getId());
                    }

                    $user->setDirSlug($dirSlug);
                    $em->persist($user);
                }

                $em->flush();
            }
        }

        if (count($userExists) > 0) {
            $this->addFlash('errors', implode(',',$userExists));

            return $this->redirectToRoute('admin_user_import_lkpp', ['role' => 'government']);
        }

        return $this->redirectToRoute('admin_user_index', ['role' => 'government']);
    }

    public function portalGetBooking(LoggerInterface $logger): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
            'data' => null,
        ];

        try {
            //$this->checkAllowedIp();
            $this->checkValidClient();

            $request = $this->getRequest();
            $verticalType = $request->headers->get('X-Vertical-Type', '');

            if ($verticalType === 'BOOKING ID FOR CLIENT') {
                $this->internalLogger($logger, __METHOD__);

                $input = json_decode(file_get_contents('php://input'), true);
                $payload = $input['payload'] ?? [];
                $name = $payload['nama'] ?? '';
                $sso = $payload['user_sso'] ?? '';
                $email = $payload['email'] ?? '';
                $phone = $payload['phone'] ?? '';

                /** @var User $user */
                $user = $this->fetchUserEntity([
                    'emailCanonical' => GoogleMailHandler::validate($email),
                    'isActive' => true,
                    'role' => 'ROLE_USER_GOVERNMENT',
                ]);

                if (null !== $user && !empty($user->getLkppLpseId())) {
                    $response['data'] = ['bookingID' => 'XXXXX'];
                } else {
                    $statusCode = 404;
                    $response['success'] = false;
                    $response['error'] = 'User not found!';
                }
            } else {
                $statusCode = 500;
                $response['success'] = false;
                $response['error'] = 'Invalid Vertical Type';
            }
        } catch (ErrorException $e) {
            $statusCode = 500;
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }

        return new JsonResponse($response, $statusCode);
    }

    public function portalSendRUP(LoggerInterface $logger): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
            'data' => null,
        ];

        try {
            //$this->checkAllowedIp();
            $this->checkValidClient();

            $request = $this->getRequest();
            $verticalType = $request->headers->get('X-Vertical-Type', '');

            if ($verticalType === 'CLIENT SEND RUP') {
                $this->internalLogger($logger, __METHOD__);

                $input = json_decode(file_get_contents('php://input'), true);
                $payload = $input['payload'] ?? [];
                //$name = $payload['nama'] ?? '';
                //$sso = $payload['user_sso'] ?? '';
                $email = $payload['email'] ?? '';
                //$phone = $payload['phone'] ?? '';
                //$satker = $payload['satker'] ?? '';
                //$kldi = $payload['kldi'] ?? '';
                //$rup = $payload['rup'] ?? '';

                /** @var User $user */
                $user = $this->fetchUserEntity([
                    'emailCanonical' => GoogleMailHandler::validate($email),
                    'isActive' => true,
                    'role' => 'ROLE_USER_GOVERNMENT',
                ]);

                if (empty($user) || empty($user->getLkppLpseId())) {
                    $statusCode = 404;
                    $response['success'] = false;
                    $response['error'] = 'User not found!';
                }
            } else {
                $statusCode = 500;
                $response['success'] = false;
                $response['error'] = 'Invalid Vertical Type';
            }
        } catch (ErrorException $e) {
            $statusCode = 500;
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * @throws ErrorException
     */
    private function checkAllowedIp(): void
    {
        $reserved = [
            //'103.105.33.92',
            //'103.105.33.123',
            //'13.212.140.164',
        ];

        if (!in_array($this->getClientIp(), $reserved, false)) {
            throw new ErrorException('Invalid IP!');
        }
    }

    /**
     * @throws ErrorException
     */
    private function checkValidClient(): void
    {
        $request = $this->getRequest();
        $clientId = $request->headers->get('X-Client-ID', '');
        $clientSecret = $request->headers->get('X-Client-Secret', '');
        $id = $this->getParameter('lkpp_client_id');
        $secret = $this->getParameter('lkpp_client_secret');

        if ($id !== $clientId || $secret !== $clientSecret) {
            throw new ErrorException('Invalid Client Credentials!');
        }
    }

    private function parseCSV(array $options): array
    {
        $rows = [];
        $keys = [];
        $separator = $options['separator'];
        $ignoreFirstLine = $options['ignore_first_line'];

        $finder = new Finder();
        $finder->files()
            ->in($options['find_in'])
            ->name($options['file_name'])
        ;

        foreach ($finder as $file) {
            if (($handle = fopen($file->getRealPath(), 'rb')) !== false) {
                $i = 0;

                while (($data = fgetcsv($handle, null, $separator)) !== false) {
                    $i++;
                    $parsedData = [];

                    if ($ignoreFirstLine && $i === 1) {
                        $keys = $data;
                        continue;
                    }

                    foreach ($keys as $index => $key){
                        $parsedData[$key] = $data[$index];
                    }

                    $rows[] = $parsedData;
                }

                fclose($handle);
            }
        }

        return $rows;
    }

    private function internalLogger(LoggerInterface $logger, $section): void
    {
        if ($this->debugger) {
            //--- DEBUG START
            $logger->error('*** LKPP Portal Log Start');
            $logger->error(sprintf('API activity at "%s" with client IP %s', $section, $this->getClientIp()));
            $logger->error('Payloads', json_decode(file_get_contents('php://input'), true));
            $logger->error('Headers', $this->getRequest()->headers->all());
            $logger->error('*** LKPP Portal Log End');
            //--- DEBUG END
        }
    }

    private function fetchUserEntity(array $parameters): ?User
    {
        return $this->getRepository(User::class)->findOneBy($parameters);
    }
}
