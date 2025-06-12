<?php

namespace App\Controller\User;

use App\Controller\AdminController;
use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Kldi;
use App\Entity\Satker;
use App\Entity\ChatMessage;
use App\Entity\User;
use App\Entity\UserPicDocument;
use App\Entity\UserPpkTreasurer;
use App\EventListener\UserEntityListener;
use App\Repository\ChatMessageRepository;
use App\Repository\UserRepository;
use App\Service\RajaOngkirService;
use App\Service\SwiftMailerService;
use App\Utility\GoogleMailHandler;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminUserController extends AdminController
{
    protected $key = 'user';
    protected $entity = User::class;
    protected $encoder;
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->authorizedRoles = ['ROLE_SUPER_ADMIN'];

        $this->encoder = $encoder;
    }

    protected function isAdminHelpdeskUser(): bool
    {
        return $this->getUser()->getRole() === 'ROLE_HELPDESK_USER';
    }

    protected function isAdminMerchant(): bool
    {
        return $this->getUser()->getRole() === 'ROLE_ADMIN_MERCHANT';
    }

    protected function prepareDataTableButton(): void
    {
        if ($this->isAdminHelpdeskUser() || $this->isAdminMerchant()) {
            $buttons = [
                'activate' => [],
                'deactivate' => [],
                'delete' => []
            ];

            $this->dataTable->setButtons($buttons);
        } else if ($this->isAuthorizedToManage()) {
            $buttons = [
                'activate' => [],
                'deactivate' => [],
                'delete' => []
            ];

            $this->dataTable->setButtons($buttons);
        } else {
            $this->dataTable->setButtons([]);
        }
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'role' => [
                'type' => 'hidden',
                'selections' => $this->getParameter('user_roles'),
                'value' => htmlspecialchars($request->query->get('role', 'buyer')),
            ],
            'status' => [
                'type' => 'select',
                'choices' => $this->getParameter('active_inactive'),
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
            'jump_to_page' => [
                'type' => 'text',
            ],
            'id_lpse' => [
                'type' => 'text',
            ],
            'sub_role' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'id_lpse', 'username', 'email', 'full_name', 'role', 'sub_role', 'status', 'created', 'updated', 'actions']);
    }

    protected function actFetchData(Request $request): array
    {
        /** @var User $admin */
        $admin = $this->getUser();
        $translator = $this->getTranslator();

        $buttonView = $translator->trans('button.view');
        $buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'u.id']);
        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }

        if ($parameters['role'] === 'buyer') {
            $parameters['roles'] = ['ROLE_USER', 'ROLE_USER_BUYER'];
        } elseif ($parameters['role'] === 'seller') {
            $parameters['roles'] = ['ROLE_USER_SELLER'];
        } elseif ($parameters['role'] === 'government') {
            $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
        } elseif ($parameters['role'] === 'business') {
            $parameters['roles'] = ['ROLE_USER_BUSINESS'];
        } elseif ($parameters['role'] === 'admin') {
            if ($admin->getRole() !== 'ROLE_SUPER_ADMIN') {
                $parameters['exclude_role'] = 'ROLE_SUPER_ADMIN';
                $parameters['roles'] = [
                    'ROLE_ADMIN',
                    'ROLE_ACCOUNTING_1',
                    'ROLE_ACCOUNTING_2',
                    'ROLE_HELPDESK_USER',
                    'ROLE_HELPDESK_MERCHANT',
                    'ROLE_ADMIN_PRODUCT',
                    'ROLE_ADMIN_MERCHANT',
                    'ROLE_ADMIN_VOUCHER',
                    'ROLE_ADMIN_MERCHANT_CABANG',
                ];
            } else {
                $parameters['roles'] = [
                    'ROLE_ADMIN',
                    'ROLE_ACCOUNTING_1',
                    'ROLE_ACCOUNTING_2',
                    'ROLE_HELPDESK_USER',
                    'ROLE_HELPDESK_MERCHANT',
                    'ROLE_ADMIN_PRODUCT',
                    'ROLE_ADMIN_MERCHANT',
                    'ROLE_ADMIN_VOUCHER',
                    'ROLE_ADMIN_MERCHANT_CABANG',
                ];
            }
        } else {
            $parameters['roles'] = ['ROLE_INVALID'];
        }

        $roleParam = $parameters['role'];

        $parameters['role'] = null;
        $parameters['search'] = null;

        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        /** @var UserRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $users = $results['data'];
        $data = [];

        foreach ($users as $user) {
            $userId = (int) $user['u_id'];
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $userId, 'role' => $roleParam]);
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $userId, 'role' => $roleParam]);
            $status = (int) $user['u_isActive'] === 1 ? 'label.active' : 'label.inactive';
            $status = $translator->trans($status);

            $checkbox = '';
            $buttons = '';

            if ($admin->getRole() === 'ROLE_SUPER_ADMIN') {
                $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
                $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";

                if ((int) $admin->getId() !== $userId) {
                    $checkbox = "<input value=\"$userId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                    $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$userId\">$buttonDelete</a>";
                }
            } elseif ($admin->getRole() === 'ROLE_ADMIN') {
                if ($user['u_role'] !== 'ROLE_SUPER_ADMIN') {
                    $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
                    //                    $buttons = "<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";

                    if ($this->isAuthorizedToManage()) {
                        if ((int) $admin->getId() !== $userId) {
                            $checkbox = "<input value=\"$userId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                            $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$userId\">$buttonDelete</a>";
                        }
                    }
                }
            } elseif ($this->isAdminMerchant()) {
                $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";

                if ($roleParam === 'seller') {
                    $checkbox = "<input value=\"$userId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                    $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
                }
            } elseif ($this->isAdminHelpdeskUser()) {
                $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";

                if ($roleParam === 'buyer' || $roleParam === 'business' || $roleParam === 'government') {
                    $checkbox = "<input value=\"$userId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                    $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
                }
            } else {
                $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
            }

            $data[] = [
                $checkbox,
                $user['u_lkppLpseId'] ?? '-',
                $user['u_username'],
                $user['u_email'],
                trim($user['u_firstName'] . ' ' . $user['u_lastName']),
                $user['u_role'],
                $user['u_subRole'] ?? '-',
                $status,
                !empty($user['u_createdAt']) ? $user['u_createdAt']->format('d M Y H:i') : '-',
                !empty($user['u_updatedAt']) ? $user['u_updatedAt']->format('d M Y H:i') : '-',
                $buttons,
            ];
        }

        return [
            'draw' => $parameters['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    public function create()
    {
        $this->prepareTemplateSection();
        $rajaOngkir = $this->get(RajaOngkirService::class);

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'province_data' => $rajaOngkir->getProvince()
        ]);
    }

    public function saveUser(UserPasswordEncoderInterface $encoder): RedirectResponse
    {
        if (!$this->isAuthorizedToManage() && !$this->isAdminHelpdeskUser() && !$this->isAdminMerchant()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $request = $this->getRequest();
        $redirect = $this->generateUrl($this->getAppRoute('create'));

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $translator = $this->getTranslator();
            $errors = [];

            $flashBag = $this->get('session.flash_bag');
            $flashBag->set('form_data', $formData);

            $email = filter_var($formData['u_email'], FILTER_SANITIZE_EMAIL);
            $emailCanonical = GoogleMailHandler::validate($email);
            $role = filter_var($formData['u_role'], FILTER_SANITIZE_STRING);
            $isUserTesting = (isset($formData['u_isUserTesting']) && (int)$formData['u_isUserTesting'] === 1);

            $repository = $this->getRepository(User::class);
            $cek_data = $repository->findBy(['email' => $email]);
            $cek_username = $repository->findBy(['username' => $formData['u_username']]);

            if (count($cek_data) > 0 || count($cek_username) > 0) {
                $flashBag->set('warning', $this->getTranslator()->trans('label.user_exist'));
                return $this->redirect($redirect);
            }

            if ($this->isAdminMerchant() && $role !== 'ROLE_USER_SELLER') {
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.action_not_allowed'));
                return $this->redirect($redirect);
            }

            if ($this->isAdminHelpdeskUser() && ($role !== 'ROLE_USER' && $role !== 'ROLE_USER_BUSINESS' && $role !== 'ROLE_USER_GOVERNMENT')) {
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.action_not_allowed'));
                return $this->redirect($redirect);
            }

            $user = new User();
            $user->setUsername($formData['u_username']);
            $user->setEmail($email);
            $user->setEmailCanonical($emailCanonical);
            $user->setPassword($formData['u_password']);
            //$user->setDirSlug(filter_var($formData['u_dirSlug'], FILTER_SANITIZE_STRING));
            $user->setRole($role);
            $user->setIsActive((bool) abs($formData['u_isActive']));
            $user->setIsDeleted(false);
            $user->setFirstName(filter_var($formData['u_firstName'], FILTER_SANITIZE_STRING));
            $user->setLastName(filter_var($formData['u_lastName'], FILTER_SANITIZE_STRING));
            $user->setDescription(filter_var($formData['u_description'], FILTER_SANITIZE_STRING));
            $user->setPhoneNumber(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_STRING));
            $user->setGender(filter_var($formData['u_gender'], FILTER_SANITIZE_STRING));
            $user->setTnc('yes');
            $user->setIsUserTesting($isUserTesting);

            if (!empty($formData['u_dob'])) {
                try {
                    $user->setDob(new DateTime(filter_var($formData['u_dob'], FILTER_SANITIZE_STRING)));
                } catch (Exception $e) {
                }
            }

            if ($role === 'ROLE_USER_GOVERNMENT') {
                $user->setSubRole(filter_var($formData['u_subRole'], FILTER_SANITIZE_STRING));
                if ($formData['u_subRole'] == 'PPK') {
                    $user->setSubRoleTypeAccount(filter_var($formData['u_subRoleTypeAccount'], FILTER_SANITIZE_STRING));
                }
            }

            $publicDir = $this->getParameter('public_dir_path');
            $deleted = [];

            if (isset($formData['u_photoProfileTmp']) && !empty($formData['u_photoProfileTmp'])) {
                $photoProfile = filter_var($formData['u_photoProfileTmp'], FILTER_SANITIZE_STRING);
                $photoProfile = ltrim($photoProfile, '/');

                $formData['u_photoProfile'] = '';
                $formData['photo_profile_src'] = $photoProfile;
                if (!empty($formData['photo_profile_src'])) {
                    $deleted[] = $publicDir . '/' . $formData['photo_profile_src'];
                }

                $user->setPhotoProfile($photoProfile);
            }

            $validator = $this->getValidator();
            $userErrors = $validator->validate($user);

            if ($role === 'ROLE_ADMIN_MERCHANT_CABANG') {
                if (!empty($formData['u_adminMerchantBranchProvince'])) {
                    $user->setAdminMerchantBranchProvince(abs($formData['u_adminMerchantBranchProvince']));
                } else {
                    $message = $translator->trans('global.empty_input', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $user, 'adminMerchantBranchProvince', '', null, null, new Assert\NotBlank(), null);

                    $userErrors->add($constraint);
                }
            }

            if (empty($formData['u_passwordConfirmation'])) {
                $message = $translator->trans('global.empty_input', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $user, 'passwordConfirmation', '', null, null, new Assert\NotBlank(), null);

                $userErrors->add($constraint);
            }

            if ($formData['u_passwordConfirmation'] !== $formData['u_password']) {
                $message = $translator->trans('user.password_not_match', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $user, 'passwordConfirmation', '', null, null, new Assert\EqualTo('u_password'), null);

                $userErrors->add($constraint);
            }

            if (count($userErrors) === 0) {
                $password = $encoder->encodePassword($user, $formData['u_password']);
                $user->setPassword($password);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $this->appGenericEventDispatcher(new GenericEvent($user, [
                    'em' => $em,
                ]), 'app.user_save', new UserEntityListener());

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.user_created', ['%name%' => $user->getUsername()])
                );

                foreach ($deleted as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                switch ($formData['btn_action']) {
                    case 'save':
                        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $user->getId()]);
                        break;
                    case 'save_exit':
                        $redirect = $this->generateUrl($this->getAppRoute());
                        break;
                }
            } else {
                foreach ($userErrors as $error) {
                    $errors['u_' . $error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);
            }
        }

        return $this->redirect($redirect);
    }

    protected function actReadData(int $id)
    {
        /** @var User $admin */
        $admin = $this->getUser();
        $user = $this->getUserProfile($id);
        $repository = $this->getRepository($this->entity);
        $user['u_userPic'] = $repository->find($id)->getUserPicDocuments();
        $user['u_userPpkTreasurer'] = $repository->find($id)->getUserPpkTreasurers();

        if ($user) {
            if ($user['u_role'] === 'ROLE_SUPER_ADMIN' && $admin->getRole() !== 'ROLE_SUPER_ADMIN') {
                throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
            }

            // Little hack to change boolean type to integer
            $user['u_isActive'] = (int) $user['u_isActive'];
            $user['u_isDeleted'] = (int) $user['u_isDeleted'];

            /** @var DateTime $dob */
            $dob = $user['u_dob'];

            if (null !== $dob) {
                $user['u_dob'] = $dob->format('Y-m-d');
            }
        }

        $user['is_allowed_to_edit'] = $this->isAuthorizedToManage();

        return $user;
    }

    protected function actEditData(int $id)
    {
        if (!$this->isAuthorizedToManage() && !$this->isAdminHelpdeskUser() && !$this->isAdminMerchant()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var User $admin */
        $admin = $this->getUser();
        $user = $this->getUserProfile($id);
        $repository = $this->getRepository($this->entity);
        $repositoryKLPD = $this->getRepository(Kldi::class);
        $repositorySatker = $this->getRepository(Satker::class);
        $user['u_userPic'] = $repository->find($id)->getUserPicDocuments();
        $user['u_userPpkTreasurer'] = $repository->find($id)->getUserPpkTreasurers();

        if ($user) {
            if ($user['u_role'] === 'ROLE_SUPER_ADMIN' && $admin->getRole() !== 'ROLE_SUPER_ADMIN') {
                throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
            }

            // Little hack to change type from boolean to integer
            $user['u_isActive'] = (int) $user['u_isActive'];
            $user['u_isDeleted'] = (int) $user['u_isDeleted'];

            /** @var DateTime $dob */
            $dob = $user['u_dob'];

            if (null !== $dob) {
                $user['u_dob'] = $dob->format('Y-m-d');
            }
        }

        $user['is_superadmin'] = $this->isAuthorizedToManage();
        $user['data_klpd'] = $repositoryKLPD->findAll();
        $user['data_satker'] = $repositorySatker->findAll();

        return $user;
    }

    protected function actUpdateData(Request $request, int $id): string
    {
        
        if (!$this->isAuthorizedToManage() && !$this->isAdminHelpdeskUser() && !$this->isAdminMerchant()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var User $user */
        $user = $this->getRepository($this->entity)->find($id);
        $formData = $request->request->all();
        $roleParam = $request->query->get('role', null);
        $em = $this->getEntityManager();

        // dd($formData);
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id, 'role' => $roleParam]);

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $email = filter_var($formData['u_email'], FILTER_SANITIZE_EMAIL);
        $emailCanonical = GoogleMailHandler::validate($email);

        $repository = $this->getRepository(User::class);
        $cek_data = $repository->findBy(['email' => $email]);
        $cek_username = $repository->findBy(['username' => $formData['u_username']]);

        if ((count($cek_data) > 0 || count($cek_username) > 0) && $user->getEmail() != $email && $user->getUsername() != $formData['u_username']) {
            $flashBag->set('warning', $this->getTranslator()->trans('label.user_exist'));
            return $redirect;
        }


        $repositorySatker = $this->getRepository(Satker::class);


        if ($this->isAdminMerchant() && $user->getRole() !== 'ROLE_USER_SELLER') {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        if ($this->isAdminHelpdeskUser() && (
            $user->getRole() !== 'ROLE_USER' &&
            $user->getRole() !== 'ROLE_USER_BUSINESS' &&
            $user->getRole() !== 'ROLE_USER_GOVERNMENT')) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        if ($user instanceof User) {
            $isActive = (bool) abs($formData['u_isActive']);
            $role = filter_var($formData['u_role'], FILTER_SANITIZE_STRING);
            $isUserTesting = (isset($formData['u_isUserTesting']) && (int)$formData['u_isUserTesting'] === 1);

            if ($isActive && !empty($user->getActivationCode())) {
                $user->setActivationCode(null);
            }

            //--- Only update role and status of User
            $user->setUsername($formData['u_username']);
            $user->setRole($role);
            $user->setIsActive($isActive);
            //--- Only update role and status of User

            $user->setGender(filter_var($formData['u_gender'], FILTER_SANITIZE_STRING));
            $user->setFirstName(filter_var($formData['u_firstName'], FILTER_SANITIZE_STRING));
            $user->setLastName(filter_var($formData['u_lastName'], FILTER_SANITIZE_STRING));
            $user->setPhoneNumber(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_STRING));
            $user->setDescription(filter_var($formData['u_description'], FILTER_SANITIZE_STRING));
            $user->setEmail(filter_var($email, FILTER_SANITIZE_EMAIL));
            $user->setIsUserTesting($isUserTesting);

            if (!empty($formData['u_dob'])) {
                try {
                    $user->setDob(new DateTime($formData['u_dob']));
                } catch (Exception $e) {
                }
            }

            $publicDir = $this->getParameter('public_dir_path');
            $deleted = [];

            if (!empty($formData['u_photoProfileTmp'])) {
                $photoProfile = filter_var($formData['u_photoProfileTmp'], FILTER_SANITIZE_STRING);
                $photoProfile = ltrim($photoProfile, '/');
                
                $formData['u_photoProfile'] = '';
                $formData['photo_profile_src'] = $photoProfile;
                if (!empty($formData['photo_profile_src'])) {
                    $deleted[] = $publicDir . '/' . $formData['photo_profile_src'];
                }

                $user->setPhotoProfile($photoProfile);
            }

            if ($role === 'ROLE_USER_GOVERNMENT') {
                $user->setLkppLpseId(filter_var($formData['u_lkppLpseId'], FILTER_SANITIZE_STRING));
                $user->setNip(filter_var($formData['u_nip'], FILTER_SANITIZE_STRING));
                $user->setPpName(filter_var($formData['u_ppName'], FILTER_SANITIZE_STRING));
                $user->setPpkName(filter_var($formData['u_ppkName'], FILTER_SANITIZE_STRING));
                $user->setLkppKLDI(filter_var($formData['u_lkppKLDI'], FILTER_SANITIZE_STRING));

                if (!empty($formData['u_satker'])) {
                    $dataSatker = $repositorySatker->find($formData['u_satker']);
                    $namaSatker = $dataSatker->getSatkerName();
                    $idSatker = $dataSatker->getId();
                    $user->setLkppWorkUnit(filter_var($namaSatker, FILTER_SANITIZE_STRING));
                    $user->setSatkerId($idSatker);
                }

                $user->setSubRole(filter_var($formData['u_subRole'], FILTER_SANITIZE_STRING));
                if ($formData['u_subRole'] == 'PPK') {
                    $user->setSubRoleTypeAccount(filter_var($formData['u_subRoleTypeAccount'], FILTER_SANITIZE_STRING));
                }

                // $repositoryKldi = $this->getRepository(Kldi::class);
                // $dataKldi = $repositoryKldi->findOneBy(['kldi_name' => $formData['u_lkppKLDI']]);
                // if ($dataKldi != null) {
                //     $noVA = getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$dataKldi->getDigitVa();
                //     $user->setVaBni($noVA);
                //     $user->setDigitSatker($dataKldi->getDigitVa());
                // }

                if ($formData['u_subRole'] == 'PPK' or $formData['u_subRole'] == 'TREASURER') {
                    $dataPpkUser = $this->getRepository(UserPpkTreasurer::class)->findOneBy(['userAccount' => $user->getId()]);
                    if ($dataPpkUser != null) {
                        if (!empty($formData['u_lkppKLDI'])) {
                            $dataPpkUser->setKldi(filter_var($formData['u_lkppKLDI'], FILTER_SANITIZE_STRING));
                        }
                        if (!empty($namaSatker)) {
                            $dataPpkUser->setSatker(filter_var($namaSatker, FILTER_SANITIZE_STRING));
                        }
                        $em->persist($dataPpkUser);
                        $em->flush();
                    }

                    if ($dataPpkUser != null && !empty($dataPpkUser->getKldi()) && !empty($dataPpkUser->getSatker()) && $user->getSendEmailAccess() != true) {
                        $user->setSendEmailAccess(true);
                        try {
                            /** @var BaseMail $mailToSeller */
                            $mailToSeller = $this->get(BaseMail::class);
                            $mailToSeller->setMailSubject('Bmall Pemberitahuan Akses');
                            $mailToSeller->setMailTemplate('@__main__/email/new_user_ppk_treasurer.html.twig');
                            $mailToSeller->setMailRecipient($email);
                            $mailToSeller->setMailData([
                                'name' => $formData['u_username'],
                                'satker' => $dataPpkUser->getSatker(),
                                'klpd' => $dataPpkUser->getKldi(),
                                'type' => strtolower($formData['u_subRole']),
                                'username' => $user->getEmail(),
                                'password' => $user->getSecureRandomCode(),
                            ]);
                            $mailToSeller->send();
                        } catch (\Throwable $exception) {
                            // dd($exception);
                        }
                    }
                }


                if (!empty($formData['id_pic']) && $formData['id_pic'] != 0) {
                    $userPicDocument = $this->getRepository(UserPicDocument::class)->find($formData['id_pic']);
                    $userPicDocument->setName(filter_var($formData['pic_name'], FILTER_SANITIZE_STRING));
                    $userPicDocument->setUnit(filter_var($formData['pic_unit'], FILTER_SANITIZE_STRING));
                    $userPicDocument->setEmail(filter_var($formData['pic_email'], FILTER_SANITIZE_STRING));
                    $userPicDocument->setAddress(filter_var($formData['pic_address'], FILTER_SANITIZE_STRING));
                    $userPicDocument->setNotelp(filter_var($formData['pic_telp'], FILTER_SANITIZE_STRING));
                    $em->persist($userPicDocument);
                    $em->flush();
                }

                if (!empty($formData['id_ppk']) && $formData['id_ppk'] != 0) {
                    $userPpk = $this->getRepository(UserPpkTreasurer::class)->find($formData['id_ppk']);
                    $userPpk->setName(filter_var($formData['ppk_name'], FILTER_SANITIZE_STRING));
                    $userPpk->setNip(filter_var($formData['ppk_nip'], FILTER_SANITIZE_STRING));
                    $userPpk->setEmail(filter_var($formData['ppk_email'], FILTER_SANITIZE_STRING));
                    $userPpk->setTelp(filter_var($formData['ppk_telp'], FILTER_SANITIZE_STRING));
                    $userPpk->setTypeAccount(filter_var($formData['ppk_type_account'], FILTER_SANITIZE_STRING));
                    $userPpk->setType('ppk');
                    $userPpk->setUpdatedAt();
                    $em->persist($userPpk);
                    $em->flush();
                }

                if (!empty($formData['id_treasurer']) && $formData['id_treasurer'] != 0) {
                    $userTreasurer = $this->getRepository(UserPpkTreasurer::class)->find($formData['id_treasurer']);
                    $userTreasurer->setName(filter_var($formData['treasurer_name'], FILTER_SANITIZE_STRING));
                    $userTreasurer->setNip(filter_var($formData['treasurer_nip'], FILTER_SANITIZE_STRING));
                    $userTreasurer->setEmail(filter_var($formData['treasurer_email'], FILTER_SANITIZE_STRING));
                    $userTreasurer->setTelp(filter_var($formData['treasurer_telp'], FILTER_SANITIZE_STRING));
                    $userTreasurer->setTypeAccount(filter_var($formData['treasurer_type_account'], FILTER_SANITIZE_STRING));
                    $userTreasurer->setType('treasurer');
                    $userTreasurer->setUpdatedAt();
                    $em->persist($userTreasurer);
                    $em->flush();
                }

                $userPpkTreasurer = $this->getRepository(UserPpkTreasurer::class)->findOneBy(['userAccount' => $id]);
                if ($userPpkTreasurer != null) {
                    $userPpkTreasurer->setName(filter_var($formData['u_username'], FILTER_SANITIZE_STRING));
                    $userPpkTreasurer->setEmail(filter_var($email, FILTER_SANITIZE_STRING));
                    $userPpkTreasurer->setTelp(filter_var($formData['u_phoneNumber'], FILTER_SANITIZE_STRING));
                    $userPpkTreasurer->setType(strtolower($formData['u_subRole']));
                    if ($formData['u_subRole'] == 'PPK') {
                        $userPpkTreasurer->setTypeAccount(filter_var($formData['u_subRoleTypeAccount'], FILTER_SANITIZE_STRING));
                    }
                    $userPpkTreasurer->setUpdatedAt();
                }

                $user->setSubRole(filter_var($formData['u_subRole'], FILTER_SANITIZE_STRING));
                if ($formData['u_subRole'] == 'PPK') {
                    $user->setSubRoleTypeAccount(filter_var($formData['u_subRoleTypeAccount'], FILTER_SANITIZE_STRING));
                }
            }

            $validator = $this->getValidator();
            $flashBag = $this->get('session.flash_bag');
            $flashBag->set('form_data', $formData);

            $userErrors = $validator->validate($user);

            if ($role === 'ROLE_ADMIN_MERCHANT_CABANG') {
                if (!empty($formData['u_adminMerchantBranchProvince'])) {
                    $user->setAdminMerchantBranchProvince(abs($formData['u_adminMerchantBranchProvince']));
                } else {
                    $message = $this->getTranslator()->trans('global.empty_input', [], 'validators');
                    $constraint = new ConstraintViolation($message, $message, [], $user, 'adminMerchantBranchProvince', '', null, null, new Assert\NotBlank(), null);

                    $userErrors->add($constraint);
                }
            }

            if (!empty($formData['u_password']) && $formData['u_password'] !== $formData['u_passwordConfirmation']) {
                $message = $this->getTranslator()->trans('user.password_not_match', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $user, 'passwordConfirmation', '', null, null, new Assert\EqualTo('password'), null);

                $userErrors->add($constraint);
            }

            if (count($userErrors) === 0) {

                if (!empty($formData['u_password']) && $formData['u_password'] === $formData['u_passwordConfirmation']) {
                    $password = $this->encoder->encodePassword($user, $formData['u_password']);
                    $user->setPassword($password);
                }


                $em->persist($user);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.user_updated', ['%name%' => $user->getUsername()])
                );

                foreach ($deleted as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            } else {
                $errors = [];

                foreach ($userErrors as $error) {
                    $errors['u_' . $error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('errors', $errors);
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            }


            if ($formData['btn_action'] === 'save_exit') {
                if (!empty($roleParam)) {
                    $redirect = $this->generateUrl($this->getAppRoute(), ['role' => $roleParam]);
                } else {
                    $redirect = $this->generateUrl($this->getAppRoute());
                }
            }
        }

        return $redirect;
    }

    protected function actDeleteData(): array
    {
        if (!$this->isAuthorizedToManage() && !$this->isAdminMerchant() && !$this->isAdminHelpdeskUser()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var User $admin */
        $admin = $this->getUser();
        $request = $this->getRequest();
        $userId = abs($request->request->get('user', '0'));
        $user = $this->getRepository($this->entity)->find($userId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'user']),
        ];

        if ($user instanceof User) {
            $username = $user->getUsername();
            $deleted = false;

            if ($admin->getRole() === 'ROLE_SUPER_ADMIN') {
                $deleted = true;

                $user->setIsActive(false);
                $user->setIsDeleted(true);
            } elseif ($admin->getRole() === 'ROLE_ADMIN') {
                if ($user->getRole() !== 'ROLE_SUPER_ADMIN') {
                    $deleted = true;

                    $user->setIsActive(false);
                    $user->setIsDeleted(true);
                }
            } elseif ($this->isAdminMerchant()) {
                if ($user->getRole() === 'ROLE_USER_SELLER') {
                    $deleted = true;

                    $user->setIsActive(false);
                    $user->setIsDeleted(true);
                }
            } elseif ($this->isAdminHelpdeskUser()) {
                if (
                    $user->getRole() === 'ROLE_USER' || $user->getRole() === 'ROLE_USER_BUSINESS' ||
                    $user->getRole() === 'ROLE_USER_GOVERNMENT'
                ) {
                    $deleted = true;

                    $user->setIsActive(false);
                    $user->setIsDeleted(true);
                }
            }

            if ($deleted) {
                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $response['status'] = true;
                $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $username]);
            }
        }

        return $response;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage() && !$this->isAdminHelpdeskUser() && !$this->isAdminMerchant()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        /** @var User $admin */
        $admin = $this->getUser();
        $translator = $this->getTranslator();
        $action = $request->request->get('btn_action', 'invalid');
        $users = [];
        $reserved = [];
        $slugs = [];
        $proceed = false;
        $sql = null;

        $this->roleParam = $request->request->get('role-param', null);

        foreach ($ids as $key => $id) {
            $id = abs($id);
            $ids[$key] = $id;

            $user = $this->getRepository($this->entity)->find($id);

            if ($user instanceof User) {
                $slugs[$key] = $user->getDirSlug();

                if ($user->getRole() === 'ROLE_SUPER_ADMIN' && $admin->getRole() === 'ROLE_ADMIN') {
                    $reserved[] = $key;
                } elseif ($admin->getId() === $id) {
                    $reserved[] = $key;
                } else {
                    $users[] = $user->getUsername();
                }
            }
        }

        foreach ($reserved as $value) {
            if (isset($ids[$value])) {
                unset($ids[$value]);
            }

            if (isset($slugs[$value])) {
                unset($slugs[$value]);
            }
        }

        if (count($reserved) > 0) {
            $ids = array_values($ids);
        }

        switch ($action) {
            case 'delete':
                $sql = 'UPDATE App\Entity\User t SET t.isActive = 0, t.isDeleted = 1, t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $proceed = true;
                break;
            case 'activate':
                $sql = 'UPDATE App\Entity\User t SET t.isActive = 1, t.isDeleted = 0, t.activationCode = NULL, t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $proceed = true;
                break;
            case 'deactivate':
                $sql = 'UPDATE App\Entity\User t SET t.isActive = 0, t.isDeleted = 0, t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $proceed = true;
                break;
        }

        if ($proceed) {
            $now = new DateTime('now');
            $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
            /** @var EntityManager $em */
            $em = $this->getEntityManager();
            $query = $em->createQuery($sql);
            $query->execute();

            $this->addFlash(
                'success',
                $translator->trans('message.success.' . $action, ['%name%' => implode(', ', $users)])
            );
        }
    }

    public function fetchSelect()
    {
        $this->isAjaxRequest();

        $request = $this->getRequest();
        $search = $request->query->get('search', null);
        $role = $request->query->get('role', 'ROLE_USER');
        $items = [
            [
                'id' => '',
                'text' => $this->getTranslator()->trans('label.select_option'),
            ]
        ];

        if (!empty($search)) {
            $parameters = [
                'order_by' => 'u.id',
                'sort_by' => 'DESC',
                'status' => 'active',
                'role' => $role,
                'search' => filter_var($search, FILTER_SANITIZE_STRING),
            ];

            /** @var UserRepository $repository */
            $repository = $this->getRepository($this->entity);
            $items = $repository->getDataForStoreSelection($parameters);
        }

        return $this->view('', ['items' => $items], 'json');
    }

    public function sendActivationMail()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $translator = $this->getTranslator();
        $id = abs($request->request->get('id', '0'));
        $user = $this->getRepository($this->entity)->find($id);
        $response = ['status' => false];
        $message = 'message.error.send_mail';

        if ($user instanceof User) {
            if ((int) $user->getIsActive() === 0 && (int) $user->getIsDeleted() === 0) {
                $message = 'message.success.activate_user';
                $data = [
                    'name' => $user->getFirstName(),
                    'link' => $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ];

                $user->setIsActive(true);

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                $response['status'] = true;

                $body = $this->renderView('@__main__/email/activation.html.twig', $data);
                $content = [
                    'to' => $user->getEmail(),
                    'from' => $this->getParameter('mail_sender'),
                    'subject' => $translator->trans('message.success.activated'),
                    'body' => $body,
                    'content_type' => 'text/html',
                ];

                $this->get(SwiftMailerService::class)->send($content);
            }

            $response['message'] = $translator->trans($message);
        }

        return $this->view('', $response, 'json');
    }

    public function chatRoom($room)
    {
        /** @var ChatMessageRepository $repository */
        $repository = $this->getRepository(ChatMessage::class);
        $request = $this->getRequest();
        $initiator = abs($request->request->get('initiator', '0'));
        $response = [
            'status' => false,
            'content' => null,
        ];

        if ($messages = $repository->getChatMessages($room, 200)) {
            $response['status'] = true;
            $response['content'] = $this->renderView('@__main__/admin/user/chat/content.html.twig', [
                'messages' => $messages,
                'initiator' => $initiator,
            ]);
        }

        return $this->view('', $response, 'json');
    }

    public function importLKPP()
    {
        $request = $this->getRequest();
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');

        if ($request->isMethod('POST')) {
            /** @var ValidatorInterface $validator */
            $validator = $this->getValidator();
            /** @var UploadedFile $file */
            $file = $request->files->get('file_lkpp');
            $violations = $validator->validate($file, [
                new Assert\NotBlank(),
                new Assert\File([
                    'maxSize' => $this->getParameter('max_upload_file'),
                    'mimeTypes' => ['text/plain', 'text/csv'],
                ]),
            ]);

            if (count($violations) === 0 && $file->getClientOriginalExtension() === 'csv') {
                $file->move(__DIR__ . '/../../../var/lkpp', 'users.csv');

                return $this->redirectToRoute('lkpp_portal_import');
            }

            $errors = [];

            foreach ($violations as $error) {
                $errors['file_lkpp'] = $error->getMessage();
            }

            if ($file->getClientOriginalExtension() !== 'csv') {
                $errors['file_lkpp'] = 'The mime type of the file is invalid. Allowed mime types are "text/plain", "text/csv".';
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);
	  

            return $this->redirectToRoute($this->getAppRoute('import_lkpp'));
        }

        return $this->view('@__main__/admin/user/import/lkpp.html.twig', [
            'page_title' => 'title.page.lkpp_import',
            'errors' => $flashBag->get('errors'),
            'token_id' => 'lkpp_import_action',
        ]);
    }

    protected function manipulateDataPackage(): void
    {
        $role = $this->getRequest()->query->get('role', '');

        $helpDeskUserAllowedToCreate = ['buyer', 'business', 'government'];

        if ($this->isAuthorizedToManage()) {
            $this->dataPackage->setAbleToCreate(true);
        } elseif ($this->isAdminHelpdeskUser() && in_array($role, $helpDeskUserAllowedToCreate)) {
            $this->dataPackage->setAbleToCreate(true);
        } elseif ($this->isAdminMerchant() && $role === 'seller') {
            $this->dataPackage->setAbleToCreate(true);
        } else {
            $this->dataPackage->setAbleToCreate(false);
        }

        $this->dataPackage->setAbleToExport(true);
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        /** @var User $admin */
        $admin = $this->getUser();
        $role = $parameters['role'] ?? '';

        if ($role === 'buyer') {
            $parameters['roles'] = ['ROLE_USER', 'ROLE_USER_BUYER'];
        } elseif ($role === 'seller') {
            $parameters['roles'] = ['ROLE_USER_SELLER'];
        } elseif ($role === 'government') {
            $parameters['roles'] = ['ROLE_USER_GOVERNMENT'];
        } elseif ($role === 'business') {
            $parameters['roles'] = ['ROLE_USER_BUSINESS'];
        } elseif ($role === 'admin') {
            if ($admin->getRole() !== 'ROLE_SUPER_ADMIN') {
                $parameters['exclude_role'] = 'ROLE_SUPER_ADMIN';
                $parameters['roles'] = ['ROLE_ADMIN'];
            } else {
                $parameters['roles'] = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
            }
        } else {
            $parameters['roles'] = ['ROLE_INVALID'];
        }

        $parameters['role'] = null;
        $parameters['search'] = null;
        $parameters['deleted'] = 'no';

        /** @var UserRepository $repository */
        $repository = $this->getRepository(User::class);
        $data = $repository->getDataToExport($parameters);
        $writer = null;

        if (count($data['data']) > 0) {
            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'First Name');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Last Name');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Username');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Email');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Role');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Description');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Phone Number');
            $sheet->setCellValueByColumnAndRow(11, 1, 'Gender');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Date of Birth');

            if ($role === 'government') {
                $sheet->setCellValueByColumnAndRow(13, 1, 'NIP');
                $sheet->setCellValueByColumnAndRow(14, 1, 'PP Name');
                $sheet->setCellValueByColumnAndRow(15, 1, 'PPK Name');
                $sheet->setCellValueByColumnAndRow(16, 1, 'NIK');
                $sheet->setCellValueByColumnAndRow(17, 1, 'LPSE ID');
                $sheet->setCellValueByColumnAndRow(18, 1, 'KLDI');
                $sheet->setCellValueByColumnAndRow(19, 1, 'Work Unit');
                $sheet->setCellValueByColumnAndRow(20, 1, 'Created At');
                $sheet->setCellValueByColumnAndRow(21, 1, 'Updated At');
            } else {
                $sheet->setCellValueByColumnAndRow(13, 1, 'Created At');
                $sheet->setCellValueByColumnAndRow(14, 1, 'Updated At');
            }

            foreach ($data['data'] as $item) {
                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['u_id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['u_firstName']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['u_lastName']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $item['u_username']);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item['u_emailCanonical']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['u_role']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item['u_isActive'] ? 'Active' : 'Inactive');
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['u_description']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['u_phoneNumber']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['u_gender']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['u_dob']);

                if ($role === 'government') {
                    $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['u_nip']);
                    $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['u_ppName']);
                    $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item['u_ppkName']);
                    $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item['u_nik']);
                    $sheet->setCellValueByColumnAndRow(17, ($number + 1), $item['u_lkppLpseId']);
                    $sheet->setCellValueByColumnAndRow(18, ($number + 1), $item['u_lkppKLDI']);
                    $sheet->setCellValueByColumnAndRow(19, ($number + 1), $item['u_lkppWorkUnit']);
                    $sheet->setCellValueByColumnAndRow(20, ($number + 1), $item['u_createdAt']->format('Y-m-d H:i:s'));
                    $sheet->setCellValueByColumnAndRow(21, ($number + 1), !empty($item['u_updatedAt']) ? $item['u_updatedAt']->format('Y-m-d H:i:s') : '-');
                } else {
                    $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item['u_createdAt']->format('Y-m-d H:i:s'));
                    $sheet->setCellValueByColumnAndRow(14, ($number + 1), !empty($item['u_updatedAt']) ? $item['u_updatedAt']->format('Y-m-d H:i:s') : '-');
                }

                $number++;
            }

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }
}
