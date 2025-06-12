<?php

namespace App\Controller\Balimall;

use App\Controller\PublicController;
use App\Entity\User;
use App\Helper\StaticHelper;
use App\Utility\GoogleMailHandler;
use ErrorException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserController extends PublicController
{
    private $role = 'ROLE_USER_SELLER';

    public function portalLogin(): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
            'token' => null,
            'code' => 200,
        ];

        try {
            $this->checkValidClient();

            $input = json_decode(file_get_contents('php://input'), true);
            $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL) ? $input['email'] : '__invalid_email__';

            $user = $this->fetchUserEntity([
                'email' => $email,
                'isActive' => true,
                'role' => $this->role,
            ]);

            if ($user instanceof User) {
                if (!empty($user->getStores())) {
                    $token = $user->getBalimallToken();

                    if (empty($token)) {
                        do {
                            $token = StaticHelper::secureRandomCode();
                            $exist = $this->fetchUserEntity(['balimallToken' => $token]);
                            $found = $exist ? 'no' : 'yes';
                        } while ($found === 'no');

                        $user->setBalimallToken($token);

                        $em = $this->getEntityManager();
                        $em->persist($user);
                        $em->flush();
                    }

                    $response['token'] = $token;
                }else {
                    $response['code'] = 404;
                    $response['success'] = false;
                    $response['error'] = 'Merchant not registered on Tokodaring';
                }
            }else {
                $response['code'] = 404;
                $response['success'] = false;
                $response['error'] = 'User not found on Tokodaring!';
            }
        }catch (\Throwable $throwable) {
            $response['code'] = 500;
            $response['success'] = false;
            $response['error'] = 'Tidak dapat login ke Tokodaring. Terjadi kesalahan pada sistem';
        }

        return new JsonResponse($response, $statusCode);
    }

    public function login():RedirectResponse
    {
        $request = $this->getRequest();
        $token = $request->query->get('token', '');
        $route = 'homepage';

        if (!empty($token)){
            /** @var User $user */
            $user = $this->fetchUserEntity([
                'role' => $this->role,
                'isActive' => true,
                'balimallToken' => $token,
            ]);

            if ($user instanceof User) {
//                $route = 'user_operator_select';
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

                $session = $this->get('session');

                $this->get('security.token_storage')->setToken($token);

                if ($session->has('_security_main')) {
                    $session->remove('_security_main');
                }

                $session->set('_security_main', serialize($token));

                $user->setBalimallToken(null);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();
            }
        }

        return $this->redirectToRoute($route);
    }

    public function portalLogout(): JsonResponse
    {
        $statusCode = 200;
        $response = [
            'success' => true,
            'error' => null,
        ];

        try {
            $this->checkValidClient();

            $input = json_decode(file_get_contents('php://input'), true);
            $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL) ? $input['email'] : '__invalid_email__';

            if (!empty($email)) {
                $user = $this->fetchUserEntity([
                    'email' => $email,
                    'role' => $this->role,
                ]);

                if (empty($user)) {
                    $statusCode = 404;
                    $response['error'] = 'User not found';
                    $response['success'] = false;
                }else {
                    $user->setBalimallToken(null);

                    $em = $this->getEntityManager();
                    $em->persist($user);
                    $em->flush();
                }
            }else {
                $statusCode = 500;
                $response['error'] = 'Invalid user';
                $response['success'] = false;
            }

        }catch (\Throwable $throwable) {
            $statusCode = 500;
            $response['success'] = false;
            $response['error'] = 'Terjadi kesalahan pada sistem';
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * @throws ErrorException
     */
    private function checkValidClient(): void
    {
        $request = $this->getRequest();
        $clientId = $request->headers->get('X-Client-ID', '');
        $balimallId = getenv('BALIMALL_CLIENT_ID');

        if ($balimallId !== $clientId ) {
            throw new ErrorException('Invalid Client Credentials!');
        }
    }

    private function fetchUserEntity(array $parameters): ?User
    {
        return $this->getRepository(User::class)->findOneBy($parameters);
    }
}
