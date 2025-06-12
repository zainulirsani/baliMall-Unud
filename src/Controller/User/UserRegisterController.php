<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Newsletter;
use App\Entity\Operator;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Entity\Store;
use App\EventListener\UserEntityListener;
use App\Helper\StaticHelper;
use App\Repository\NewsletterRepository;
use App\Service\RajaOngkirService;
use App\Service\SwiftMailerService;
use App\Utility\GoogleMailHandler;
use Cocur\Slugify\Slugify;
use DateTime;
use Exception;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;

class UserRegisterController extends PublicController
{
    private $enableRoleVendor = true;
    private $enableRoleGovernment = true;
    private $enableRoleBusiness = true;
    private $allowedRoles = ['government', 'vendor', 'business'];

    public function register(UserPasswordEncoderInterface $encoder)
    {
        $request = $this->getRequest();
        $session = $this->getSession();
        $formData = [];
        $errors = [];

        $tokenId = 'user_register';
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $regAs = $request->query->get('as', null);

        if (!in_array($regAs, $this->allowedRoles, false)) {
            $regAs = 'government';
        }
        
        if (!empty($regAs)) {
            if (!$this->enableRoleVendor && $regAs === 'vendor') {
                $regAs = 'user';
            }

            if (!$this->enableRoleGovernment && $regAs === 'government') {
                $regAs = 'user';
            }

            if (!$this->enableRoleBusiness && $regAs === 'business') {
                $regAs = 'user';
            }
        }

        if (null !== $session->get('_security_main')) {
            return $this->redirectToRoute('user_dashboard');
        }

        if (null !== $session->get('_security_admin')) {
            $admin = $session->get('_security_admin');
            /** @var UsernamePasswordToken $admin */
            $admin = unserialize($admin, ['allowed_classes' => true]);
            $admin = $admin->getUser();
            $redirect = 'homepage';

            if ($admin instanceof User) {
                switch ($admin->getRole()) {
                    case 'ROLE_USER':
                        $redirect = 'user_dashboard';
                        break;
                    case 'ROLE_ADMIN':
                    case 'ROLE_SUPER_ADMIN':
                        $redirect = 'admin_dashboard';
                        break;
                }
            }

            return $this->redirectToRoute($redirect);
        }

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $name = StaticHelper::splitFullName($formData['full_name']);
            $email = filter_var($formData['email'], FILTER_SANITIZE_EMAIL);
            $emailCanonical = GoogleMailHandler::validate($email);
            $activationCode = StaticHelper::secureRandomCode();
            $userRole = 'ROLE_USER_GOVERNMENT';

            if (isset($formData['reg_as']) && !empty($formData['reg_as'])) {
                if ($this->enableRoleVendor && $formData['reg_as'] === 'vendor') {
                    $userRole = 'ROLE_USER_SELLER';
                } elseif ($this->enableRoleGovernment && $formData['reg_as'] === 'government') {
                    $userRole = 'ROLE_USER_GOVERNMENT';
                } elseif ($this->enableRoleBusiness && $formData['reg_as'] === 'business') {
                    $userRole = 'ROLE_USER_BUSINESS';
                }
            }
 
            $user = new User();
            $user->setUsername($emailCanonical);
            $user->setEmail($email);
            $user->setEmailCanonical($emailCanonical);
            $user->setPassword($formData['password']);
            $user->setRole($userRole);
            $user->setIsActive(false);
            $user->setIsDeleted(false);
            $user->setActivationCode($activationCode);
            $user->setFirstName($name['first_name']);
            $user->setLastName($name['last_name']);
            $user->setGender(filter_var($formData['gender'], FILTER_SANITIZE_STRING));
            $user->setPhoneNumber(filter_var($formData['phone'], FILTER_SANITIZE_STRING));
            $user->setIpAddress();

            try {
                $user->setDob(new DateTime($formData['dob']));
            } catch (Exception $e) {
            }

            if (isset($formData['newsletter']) && $formData['newsletter'] === 'yes') {
                $user->setNewsletter(true);
            }

            if (isset($formData['tnc']) && $formData['tnc'] === 'yes') {
                $user->setTnc('checked');
            }

            if (!empty($formData['photo_profile_temp'])) {
                $photoProfile = filter_var($formData['photo_profile_temp'], FILTER_SANITIZE_STRING);
                $photoProfile = ltrim($photoProfile, '/');
                $formData['photo_profile_src'] = $photoProfile;

                $user->setPhotoProfile($photoProfile);
            }

            $validator = $this->getValidator();
            $userAddress = new UserAddress();
            $userAddress->setTitle('Alamat');
            $userAddress->setAddress($formData['address']);
            $userAddress->setPostCode($formData['post_code']);
            $userAddress->setCity($formData['city']);
            $userAddress->setCityId(abs($formData['city_id']));
            $userAddress->setDistrict(null);
            $userAddress->setDistrictId(0);
            $userAddress->setProvince($formData['province']);
            $userAddress->setProvinceId(abs($formData['province_id']));
            $userAddress->setCountry('ID');
            $userAddress->setCountryId(0);

            if ($userRole === 'ROLE_USER_GOVERNMENT') {
                $user->setNip(filter_var($formData['nip'], FILTER_SANITIZE_STRING));
                $user->setPpName(filter_var($formData['pp_name'], FILTER_SANITIZE_STRING));
                $user->setPpkName(filter_var($formData['ppk_name'], FILTER_SANITIZE_STRING));

                $userErrors = $validator->validate($user, null, ['Default', 'b2g']);
            } elseif ($userRole === 'ROLE_USER_BUSINESS') {
                $user->setNik(filter_var($formData['nik'], FILTER_SANITIZE_STRING));
                $user->setCompanyPhone(filter_var($formData['company_phone'], FILTER_SANITIZE_STRING));
                $user->setCompanyRole(filter_var($formData['company_role'], FILTER_SANITIZE_STRING));

                $userAddress->setTitle(filter_var($formData['company_name'], FILTER_SANITIZE_STRING));

                $userErrors = $validator->validate($user, null, ['Default', 'b2b']);
            } else {
                $userErrors = $validator->validate($user);
            }

            $userAddressError = $validator->validate($userAddress);

            $operatorError = [];

            if ($userRole === 'ROLE_USER_SELLER') {
                $operator = new Operator();
                $fullname = trim($formData['first_name']) .' '.trim($formData['last_name']);
                $operator->setFullname(filter_var($fullname, FILTER_SANITIZE_STRING));
                $operator->setRole('ROLE_ADMIN_MERCHANT_OWNER');
                $operator->setAddress(filter_var($formData['address'], FILTER_SANITIZE_STRING));
                $operator->setPhone(filter_var($formData['phone'], FILTER_SANITIZE_STRING));
                $operator->setOwner($user);

                $operatorError = $validator->validate($operator);
            }

            if (count($userErrors) === 0 && count($userAddressError) === 0 && count($operatorError) === 0) {
                $password = $encoder->encodePassword($user, $formData['password']);
                $user->setPassword($password);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $userAddress->setUser($user);

                $em->persist($userAddress);
                $em->flush();

                $this->appGenericEventDispatcher(new GenericEvent($user, [
                    'em' => $em,
                ]), 'front.user_register', new UserEntityListener());

                if ((int) $user->getNewsletter() === 1) {
                    /** @var NewsletterRepository $repository */
                    $repository = $this->getRepository(Newsletter::class);
                    $exist = $repository->findOneBy(['email' => $user->getEmailCanonical()]);

                    if (!$exist) {
                        $newsletter = new Newsletter();
                        $newsletter->setEmail($user->getEmailCanonical());

                        $em->persist($newsletter);
                        $em->flush();
                    }
                }

                $translator = $this->getTranslator();
                $dataRegister = [
                    'name' => $user->getFirstName(),
                    'link' => $this->generateUrl('email_activation', ['code' => $user->getActivationCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
                $bodyEmail = $this->renderView('@__main__/email/registration.html.twig', $dataRegister);
                $subject = $translator->trans('message.info.activate_your_account');
                $contentEmail = [
                    'to' => $user->getEmail(),
                    'from' => $this->getParameter('mail_sender'),
                    'subject' => $subject,
                    'body' => $bodyEmail,
                    'content_type' => 'text/html',
                ];

                $this->get(SwiftMailerService::class)->send($contentEmail);

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.user_registration_alt')
                );

                return $this->redirectToRoute('login');
            }

            // Add user error messages into its own array
            foreach ($userErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }

            foreach ($userAddressError as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        //--- B2G
        if (isset($formData['nip'])) {
            $formData['u_nip'] = $formData['nip'];
        }

        if (isset($formData['pp_name'])) {
            $formData['u_ppName'] = $formData['pp_name'];
        }

        if (isset($formData['ppk_name'])) {
            $formData['u_ppkName'] = $formData['ppk_name'];
        }
        //--- B2G

        //--- B2B
        if (isset($formData['nik'])) {
            $formData['u_nik'] = $formData['nik'];
        }

        if (isset($formData['company_name'])) {
            $formData['u_companyName'] = $formData['company_name'];
        }

        if (isset($formData['company_phone'])) {
            $formData['u_companyPhone'] = $formData['company_phone'];
        }

        if (isset($formData['company_role'])) {
            $formData['u_companyRole'] = $formData['company_role'];
        }
        //--- B2B

        return $this->view('@__main__/public/user/register.html.twig', [
            'page_title' => 'title.register',
            'form_data' => $formData,
            'token_id' => $tokenId,
            'errors' => $errors,
            'reg_as' => $regAs,
            'date_picker' => true,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
        ]);
    }

    public function registerVendor(UserPasswordEncoderInterface $encoder)
    {
        $request = $this->getRequest();
        $session = $this->getSession();
        $formData = [];
        $errors = [];

        $tokenId = 'user_register';
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $regAs = $request->query->get('as', null); 

        if (!empty($regAs)) {
            if (!$this->enableRoleVendor && $regAs === 'vendor') {
                $regAs = 'user';
            }
        }

        if (null !== $session->get('_security_main')) {
            return $this->redirectToRoute('user_dashboard');
        }

        if (null !== $session->get('_security_admin')) {
            $admin = $session->get('_security_admin');
            /** @var UsernamePasswordToken $admin */
            $admin = unserialize($admin, ['allowed_classes' => true]);
            $admin = $admin->getUser();
            $redirect = 'homepage';

            if ($admin instanceof User) {
                switch ($admin->getRole()) {
                    case 'ROLE_USER':
                        $redirect = 'user_dashboard';
                        break;
                    case 'ROLE_ADMIN':
                    case 'ROLE_SUPER_ADMIN':
                        $redirect = 'admin_dashboard';
                        break;
                }
            }

            return $this->redirectToRoute($redirect);
        }

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $name = StaticHelper::splitFullName($formData['full_name']);
            $address = filter_var($formData['email'], FILTER_SANITIZE_EMAIL);
            $emailCanonical = GoogleMailHandler::validate($address);
            $activationCode = StaticHelper::secureRandomCode();
            $userRole = 'ROLE_USER';

            if (isset($formData['reg_as']) && !empty($formData['reg_as'])) {
                if ($this->enableRoleVendor && $formData['reg_as'] === 'vendor') {
                    $userRole = 'ROLE_USER_SELLER';
                }
            }

            $user = new User();
            $user->setUsername($emailCanonical);
            $user->setEmail($address);
            $user->setEmailCanonical($emailCanonical);
            $user->setPassword($formData['password']);
            $user->setRole($userRole);
            $user->setIsActive(false);
            $user->setIsDeleted(false);
            $user->setActivationCode($activationCode);
            $user->setFirstName($name['first_name']);
            $user->setLastName($name['last_name']);
            $user->setPhoneNumber(filter_var($formData['phone'], FILTER_SANITIZE_STRING));
            $user->setIpAddress();

            if (isset($formData['tnc']) && $formData['tnc'] === 'yes') {
                $user->setTnc('checked');
            }
            
            $operator = new Operator();
            $fullname = trim($name['first_name']) .' '.trim($name['last_name']);
            $operator->setFullname(filter_var($fullname, FILTER_SANITIZE_STRING));
            $operator->setRole('ROLE_ADMIN_MERCHANT_OWNER');
            $operator->setAddress(' ');
            $operator->setPhone(filter_var($formData['phone'], FILTER_SANITIZE_STRING));
            $operator->setOwner($user);

            $validator = $this->getValidator();

            $userErrors = $validator->validate($user);
            $operatorError = $validator->validate($operator);

            if (count($userErrors) === 0 && count($operatorError) === 0) {
                $password = $encoder->encodePassword($user, $formData['password']);
                $user->setPassword($password);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->persist($operator);
                $em->flush();

                $this->appGenericEventDispatcher(new GenericEvent($user, [
                    'em' => $em,
                ]), 'front.user_register', new UserEntityListener());

                $translator = $this->getTranslator();
                $dataRegister = [
                    'name' => $user->getFirstName(),
                    'link' => $this->generateUrl('email_activation', ['code' => $user->getActivationCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
                $bodyEmail = $this->renderView('@__main__/email/registration.html.twig', $dataRegister);
                $subject = $translator->trans('message.info.activate_your_account');
                $contentEmail = [
                    'to' => $user->getEmail(),
                    'from' => $this->getParameter('mail_sender'),
                    'subject' => $subject,
                    'body' => $bodyEmail,
                    'content_type' => 'text/html',
                ];

                $this->get(SwiftMailerService::class)->send($contentEmail);

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.user_registration_alt')
                );

                return $this->redirectToRoute('login');
             }else {
                 // Add user error messages into its own array

                 foreach ($userErrors as $error) {
                     $errors[$error->getPropertyPath()] = $error->getMessage();
                 }

                 $flashBag = $this->get('session.flash_bag');
                 $flashBag->set('errors', $errors);

                 return $this->redirectToRoute('register', ['as' => 'vendor']);

             }
        }

        return $this->view('@__main__/public/user/register-merchant.html.twig', [
            'page_title' => 'title.register',
            'form_data' => $formData,
            'token_id' => $tokenId,
            'errors' => $errors,
            'reg_as' => $regAs,
            'date_picker' => true,
            'province_data' => $rajaOngkir->getProvince(),
            'city_data' => $this->manipulateCitiesData(),
        ]);
    }


}
