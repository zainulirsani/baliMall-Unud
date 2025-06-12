<?php

namespace App\Controller\User;


use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Kldi;
use App\Entity\Satker;
use App\Entity\Bni;
use App\Entity\BpdCc;
use App\Entity\BniDetail;
use App\Entity\OrderChangeLog;
use App\Entity\Order;
use App\Entity\Store;
use App\Entity\Disbursement;
use App\Entity\DocumentApproval;
use App\Entity\OrderNegotiation;
use App\Entity\OrderProduct;
use App\Entity\UserPpkTreasurer;
use App\Entity\UserPicDocument;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Repository\UserPpkTreasurerRepository;
use App\Service\BreadcrumbService;
use App\Utility\CustomPaginationTemplate;
use Exception;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Utility\GoogleMailHandler;
use App\Service\FileUploader;
use App\Utility\UploadHandler;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Helper\StaticHelper;
use DateTime;
use ReflectionClass;


class UserPpkTreasurerController extends PublicController
{
    public function index()
    {
        
        $request = $this->getRequest();
        $type = $request->query->get('type', null);
        $sanitizeType = filter_var($type, FILTER_SANITIZE_STRING);

          /** @var User $user */
          $user = $this->getUser();

        if($user->getRoles()[0] != "ROLE_USER_GOVERNMENT") {
            return $this->redirectToRoute('login');
        } else {
            if ($user->getSubRole() == 'PPK' && $type == 'ppk') {
                return $this->redirectToRoute('login');
            }
        }

        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(UserPpkTreasurer::class);
        $page = abs($request->query->get('page', '1'));
        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            'order_by' => 'upt.id',
            'sort_by' => 'DESC',
            'type' => $sanitizeType,
        ];
        $parameters['redirect'] = 'user_ppk_index';

        $errorData = "";

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
            $errorData = $e->getMessage();
        }

        // $dataSQL = $repository->getPaginatedResult($parameters)->getQuery()->getSQL();
        // $datasAll = $repository->getPaginatedResult($parameters)->getQuery()->getScalarResult();
        // dd($documents, $datasAll, $pagination, $parameters, $html, $type, $dataSQL, $errorData);
        BreadcrumbService::add(['label' => $this->getTranslation('label.data_'.$sanitizeType)]);


        return $this->view('@__main__/public/user/user_ppk_treasurer/index.html.twig', [
            'documents' => $documents,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'type' => $sanitizeType,
        ]);
    }

    public function dashboard()
    {
        $request = $this->getRequest();

        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(Order::class);
        $page = abs($request->query->get('page', '1'));
        $keyword = $request->query->get('search_invoice', null);
        $status_search = $request->query->get('filter_status', null);
        $filter_status_order = $request->query->get('filter_status_order', null);

        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'order_by' => 'o.createdAt',
            'sort_by' => 'DESC',
            'search_invoice' => $keyword,
            'filter_status' => $status_search,
            'filter_status_order' => $filter_status_order,
        ];

        if ($user->getSubRole() == 'PPK') {
            $parameters['ppk_user'] = $user->getId();
            $parameters['ppk_user_collec'] = $user;
            // $parameters['status_multiple'] = ['approve_order_ppk','processed','shipped','pending_approve','received','document','tax_invoice','pending_payment','payment_process','paid'];
        } else {
            $parameters['treasurer_user'] = $user->getId();
            $parameters['status_multiple'] = ['tax_invoice','pending_payment','payment_process','paid'];
        }

        $parameters2 = $parameters;


        if (!empty($keyword)) {
            $parameters['key_invoice'] = $keyword;
        }

        if ($user->getSubRole() == 'PPK') {
            if (!empty($status_search)) {
                $parameters['filter_status_ppk'] = $status_search;
            }
        } else {
            if (!empty($status_search)) {
                $parameters['filter_status_treasurer'] = $status_search;
            }
        }

        if (!empty($filter_status_order)) {
            $parameters['status'] = $filter_status_order;
        }

        

        $parameters['limit'] = $limit;
        $parameters['offset'] = $offset;
        $parameters['redirect'] = 'user_ppktreasurer_dashboard';

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $adapter_total = new DoctrineORMAdapter($repository->getPaginatedResult($parameters2));
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $documents = $adapter->getQuery()->getArrayResult();
            $jumlah_data = $adapter_total->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $documents = [];
            $pagination = $html = null;
            $jumlah_data = [];
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.dashboard_'.strtolower($user->getSubRole()))]);
        
        foreach ($documents as $key => $value) {
            $documents[$key][0]['o_products'] = $repository->getOrderProducts($value[0]['id']);
            $documents[$key][0]['o_complaint'] = $repository->getOrderComplaint($value[0]['id']);
            $documents[$key][0]['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($value[0]['id']);
            $documents[$key][0]['o_shippedFiles'] = $repository->getOrderShippedFiles($value[0]['id']);
            $documents[$key][0]['o_master'] = !is_null($value[0]['master_id']) ? $repository->find($value[0]['master_id'])->toArray() : null;

        }

        $status_count = [
            'new_order' => 0,
            'confirmed' => 0,
            'confirm_order_ppk' => 0,
            'processed' => 0,
            'shipped' => 0,
            'received' => 0,
            'pending_payment' => 0,
            'paid' => 0,
        ];
        foreach ($jumlah_data as $key => $value) {
            if (isset($status_count[$value[0]['status']])) {
                $status_count[$value[0]['status']] += 1;
            }
        }

        
        return $this->view('@__main__/public/user/user_ppk_treasurer/dashboard.html.twig', [
            'documents' => $documents,
            'user' => $user,
            'jumlah_data' => $jumlah_data,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'status_count' => $status_count,
            'type' => $user->getSubRole(),
        ]);
    }
    


    public function new()
    {
        $request = $this->getRequest();
        $repository = $this->getRepository(Kldi::class);
        $repositorySatker = $this->getRepository(Satker::class);
        $repositoryUser = $this->getRepository(User::class);
        $type = $request->query->get('type', null);
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_ppk_save';
        $satker_data = $repositorySatker->findBy([
            // 'user' => $this->getUser(),
            'idLpse' => $this->getUser()->getLkppLpseId(),
        ]);

        $getKLDI = $repository->findOneBy([
            'id_lpse' => $this->getUser()->getLkppLpseId(),
        ]);
        // dd($getKLDI);
        BreadcrumbService::add(['label' => $this->getTranslation('label.add_data_pic')]);
        // dd($repositoryUser->findBy(['lkppLpseId' => $this->getUser()->getLkppLpseId(), 'subRole' => strtoupper($type)]));
        return $this->view('@__main__/public/user/user_ppk_treasurer/form.html.twig', [
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'klpd' => $repository->findAll(),
            'kldi' => $getKLDI,
            'satker' => $satker_data,
            'suggest' => $repositoryUser->findBy(['lkppLpseId' => $this->getUser()->getLkppLpseId(), 'subRole' => strtoupper($type)]),
            'token_id' => $tokenId,
            'type' => $type,
        ]);
    }

    public function save(UserPasswordEncoderInterface $encoder): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_ppk_index';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $type = $formData['type'];
            $em = $this->getEntityManager();
            


            $email = filter_var($formData['ppk_treasurer_email'], FILTER_SANITIZE_EMAIL);
            // $emailCanonical = GoogleMailHandler::validate($email);
            $tempEmail = filter_var($formData['ppk_treasurer_email'], FILTER_SANITIZE_EMAIL);

            if(preg_match('/\+\+\+/', $tempEmail)) {
                $emailCanonical = preg_replace('/\+\+\+/', '', $tempEmail);
            } else {
                $emailCanonical = $email;
            }

            $repository = $this->getRepository(User::class);
            $repositoryKldi = $this->getRepository(Kldi::class);
            $repositorySatker = $this->getRepository(Satker::class);

            // dd($emailCanonical, $email);
            // if ($formData['ppk_treasurer_satker_select'] == 'new_satker') {
            //     $dataSatker = new Satker();
            //     $dataSatker->setSatkerName($formData['ppk_treasurer_satker']);
            //     $em = $this->getEntityManager();
            //     $em->persist($dataSatker);
            //     $em->flush();
            //     $dataSatker->setDigitVa(str_pad($dataSatker->getId(), 8, "0", STR_PAD_LEFT));
            //     $em->persist($dataSatker);
            //     $em->flush();

            //     $idSatker = $dataSatker->getId();
            //     $namaSatker = $dataSatker->getSatkerName();
            // } else {
            //     $dataSatker = $repositorySatker->find($formData['ppk_treasurer_satker_select']);
            //     $namaSatker = $dataSatker->getSatkerName();
            //     $idSatker = $dataSatker->getId();
            // }

            $cek_data = $repository->findBy(['email' => $email]);
            $cek_username = $repository->findBy(['username' => $formData['ppk_treasurer_name']]);
            if (count($cek_data) == 0 && count($cek_username) == 0) {
                $securePassword = StaticHelper::secureRandomCode(8);

                $user = new User();
                $user->setUsername($formData['ppk_treasurer_name']);
                $user->setEmail($email);
                $user->setEmailCanonical($emailCanonical);
                $password = $encoder->encodePassword($user, $securePassword);
                $user->setPassword($password);
                $user->setRole('ROLE_USER_GOVERNMENT');
                $user->setSubRole(strtoupper($type));
                $user->setSubRoleTypeAccount(filter_var($formData['ppk_treasurer_type_account'], FILTER_SANITIZE_STRING));
                $user->setIsActive(true);
                $user->setIsDeleted(false);
                $user->setFirstName(filter_var($formData['ppk_treasurer_name'], FILTER_SANITIZE_STRING));
                $user->setPhoneNumber(filter_var($formData['ppk_treasurer_telp'], FILTER_SANITIZE_STRING));
                $user->setLkppLpseId($this->getUser()->getLkppLpseId());
                $user->setPpName(filter_var($formData['ppk_treasurer_name'], FILTER_SANITIZE_STRING));
                $user->setTnc('yes');
                $user->setLkppKLDI($formData['ppk_treasurer_kldi']);
                $user->setLkppWorkUnit($formData['ppk_treasurer_satker']);
                $user->setSecureRandomCode($securePassword);
                $user->setNip(filter_var($formData['ppk_treasurer_nip'], FILTER_SANITIZE_STRING));

                // $noVA = getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$dataSatker->getDigitVa();
                // $user->setVaBni($noVA);
                // $user->setDigitSatker($dataSatker->getDigitVa());
                
                
                $em->persist($user);
                $em->flush();

                $userPpkTreasurer = new UserPpkTreasurer();
                $userPpkTreasurer->setName(filter_var($formData['ppk_treasurer_name'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setNip(filter_var($formData['ppk_treasurer_nip'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setEmail(filter_var($formData['ppk_treasurer_email'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setTypeAccount(filter_var($formData['ppk_treasurer_type_account'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setTelp(filter_var($formData['ppk_treasurer_telp'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setType($type);
                $userPpkTreasurer->setUserAccount($user->getId());
                $userPpkTreasurer->setCreatedAt();
                $userPpkTreasurer->setKldi($formData['ppk_treasurer_kldi']);
                // $userPpkTreasurer->setSatker($formData['ppk_treasurer_satker']);


                $validator = $this->getValidator();
                $userPpkTreasurerErrors = $validator->validate($userPpkTreasurer);

                if (count($userPpkTreasurerErrors) === 0) {
                    /** @var User $user */
                    $user_have = $this->getUser();
                    $userPpkTreasurer->setUser($user_have);

                    $em->persist($userPpkTreasurer);
                    $em->flush();
                    
                    try {
                        /** @var BaseMail $mailToSeller */
                        $mailToSeller = $this->get(BaseMail::class);
                        $mailToSeller->setMailSubject('Bmall Pemberitahuan Akses');
                        $mailToSeller->setMailTemplate('@__main__/email/new_user_ppk_treasurer.html.twig');
                        $mailToSeller->setMailRecipient($emailCanonical);
                        $mailToSeller->setMailData([
                            'name' => $userPpkTreasurer->getName(),
                            'pp' => $this->getUser()->getUsername(),
                            'satker' => $formData['ppk_treasurer_satker'],
                            'klpd' => $formData['ppk_treasurer_kldi'],
                            'type' => $type,
                            'username' => $email,
                            'password' => $securePassword,
                        ]);
                        $mailToSeller->send();
    
                    } catch (\Throwable $exception) {
                        // dd($exception);
                    }

                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_'.$type.'_created')
                    );
                } else {
                    $errors = [];
                    $route = 'user_ppk_new';

                    foreach ($userPpkTreasurerErrors as $error) {
                        $errors[$error->getPropertyPath()] = $error->getMessage();
                    }

                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('label.user_exist')
                );
            }
        }

        return $this->redirectToRoute($route, ['type' => $type]);
    }

    public function edit($id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(UserPpkTreasurer::class);
        $repositorySatker = $this->getRepository(Satker::class);
        $kldiRepository = $this->getRepository(Kldi::class);
        $repositoryUser = $this->getRepository(User::class);
        /** @var UserPpkTreasurer $userTaxDocument */
        $userPpkTreasurer = $repository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        

        $getKLDI = $kldiRepository->findOneBy([
            'id_lpse' => $this->getUser()->getLkppLpseId(),
        ]);

        if (empty($userPpkTreasurer)) {
            throw new NotFoundHttpException(sprintf('Unable to find tax document with id "%s" for user id "%s"', $id, $user->getId()));
        }
        
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_tax_edit';
        $users = $repositoryUser->find($userPpkTreasurer->getUserAccount());

        $formData = [
            'id' => $userPpkTreasurer->getId(),
            'name' => $userPpkTreasurer->getName(),
            'nip' => $userPpkTreasurer->getNip(),
            'email' => $userPpkTreasurer->getEmail(),
            'type_account' => $userPpkTreasurer->getTypeAccount(),
            'telp' => $userPpkTreasurer->getTelp(),
            'kldi' => $userPpkTreasurer->getKldi(),
            'satker' => $users->getSatkerId(),
        ];
        // dd($formData);
        BreadcrumbService::add(['label' => $this->getTranslation('label.data_'.$userPpkTreasurer->getType())]);

        return $this->view('@__main__/public/user/user_ppk_treasurer/form.html.twig', [
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'klpd' => $kldiRepository->findAll(),
            'kldi' => $getKLDI,
            'satker' => $repositorySatker->findAll(),
            'suggest' => $repositoryUser->findBy(['subRole' => 'PPK']),
            'token_id' => $tokenId,
            'user_active' => $users,
            'type' => $userPpkTreasurer->getType(),
        ]);
    }

    public function update(): RedirectResponse
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_ppk_index';
        $em = $this->getEntityManager();

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            /** @var User $user */
            $user = $this->getUser();
            $type = $formData['type'];

            $email = filter_var($formData['ppk_treasurer_email'], FILTER_SANITIZE_EMAIL);
            $emailCanonical = GoogleMailHandler::validate($email);

            $repositoryUser = $this->getRepository(User::class);
            $repositoryKldi = $this->getRepository(Kldi::class);
            $repositorySatker = $this->getRepository(Satker::class);
            

            // if ($formData['ppk_treasurer_satker_select'] == 'new_satker') {
            //     $dataSatker = new Satker();
            //     $dataSatker->setSatkerName($formData['ppk_treasurer_satker']);
            //     $em = $this->getEntityManager();
            //     $em->persist($dataSatker);
            //     $em->flush();
            //     $dataSatker->setDigitVa(str_pad($dataSatker->getId(), 8, "0", STR_PAD_LEFT));
            //     $em->persist($dataSatker);
            //     $em->flush();

            //     $idSatker = $dataSatker->getId();
            //     $namaSatker = $dataSatker->getSatkerName();
            // } else {
            //     $dataSatker = $repositorySatker->find($formData['ppk_treasurer_satker_select']);
            //     $namaSatker = $dataSatker->getSatkerName();
            //     $idSatker = $dataSatker->getId();
            // }

            $cek_data = $repositoryUser->findBy(['email' => $email]);
            $cek_username = $repositoryUser->findBy(['username' => $formData['ppk_treasurer_name']]);
            $repository = $this->getRepository(UserPpkTreasurer::class);
            $userPpkTreasurer = $repository->find($formData['id']);
            if (count($cek_data) == 0 && count($cek_username) == 0 || $userPpkTreasurer->getEmail() == $formData['ppk_treasurer_email']) {
                $userPpkTreasurer->setName(filter_var($formData['ppk_treasurer_name'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setNip(filter_var($formData['ppk_treasurer_nip'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setEmail(filter_var($formData['ppk_treasurer_email'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setTypeAccount(filter_var($formData['ppk_treasurer_type_account'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setTelp(filter_var($formData['ppk_treasurer_telp'], FILTER_SANITIZE_STRING));
                $userPpkTreasurer->setType($type);
                $userPpkTreasurer->setUpdatedAt();
                $userPpkTreasurer->setKldi($formData['ppk_treasurer_kldi']);
                // $userPpkTreasurer->setSatker($formData['ppk_treasurer_satker']);

                $user = $repositoryUser->find($userPpkTreasurer->getUserAccount());
                $user->setUsername($formData['ppk_treasurer_name']);
                $user->setEmail($email);
                $user->setEmailCanonical($emailCanonical);

                $user->setRole('ROLE_USER_GOVERNMENT');
                $user->setSubRole(strtoupper($type));
                $user->setSubRoleTypeAccount(filter_var($formData['ppk_treasurer_type_account'], FILTER_SANITIZE_STRING));
                $user->setIsActive(true);
                $user->setIsDeleted(false);
                $user->setFirstName(filter_var($formData['ppk_treasurer_name'], FILTER_SANITIZE_STRING));
                $user->setPhoneNumber(filter_var($formData['ppk_treasurer_telp'], FILTER_SANITIZE_STRING));
                $user->setTnc('yes');
                $user->setLkppKLDI($formData['ppk_treasurer_kldi']);
                // $user->setLkppWorkUnit($namaSatker);
                // $user->setSatkerId($idSatker);
                $user->setNip(filter_var($formData['ppk_treasurer_nip'], FILTER_SANITIZE_STRING));

                // $noVA = getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$dataSatker->getDigitVa();
                // $user->setVaBni($noVA);
                // $user->setDigitSatker($dataSatker->getDigitVa());
                
                

                $em->persist($user);
                $em->flush();
    
                $validator = $this->getValidator();
                $userPpkTreasurerErrors = $validator->validate($userPpkTreasurer);
    
                if (count($userPpkTreasurerErrors) === 0) {
                    /** @var User $user */
                    $user = $this->getUser();
                    $userPpkTreasurer->setUser($user);
    
                    $em->persist($userPpkTreasurer);
                    $em->flush();
    
                    $this->addFlash(
                        'success',
                        $this->getTranslator()->trans('message.success.user_'.$type.'_updated')
                    );
                } else {
                    $errors = [];
                    $route = 'user_ppk_new';
    
                    foreach ($userPpkTreasurerErrors as $error) {
                        $errors[$error->getPropertyPath()] = $error->getMessage();
                    }
    
                    $flashBag->set('form_data', $formData);
                    $flashBag->set('errors', $errors);
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('label.user_exist')
                );
            }
        }

        return $this->redirectToRoute($route, ['type' => $type]);
    }


    public function delete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $picId = abs($request->request->get('id', '0'));
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'deleted' => false,
        ];

        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(UserPpkTreasurer::class);
        /** @var UserPpkTreasurer $userPpkTreasurer */
        $userPpkTreasurer = $repository->findOneBy([
            'id' => $picId,
            'user' => $user,
        ]);

            $em = $this->getEntityManager();
            $em->remove($userPpkTreasurer);
            $em->flush();

            // Do not delete file as it might be used in order detail
            //unlink($file);

            $response['deleted'] = true;
            

        return $this->view('', $response, 'json');
    }


    private function setOrderStatus(Order $order, string $status, array $data = null): array
    {
        $previousOrderValues = clone $order;

        if ($order->getStatus() !== $status) {
            $order->setStatusChangeTime();
        }

        $order->setStatus($status);
        $order->setUpdatedAt();

        $em = $this->getEntityManager();
        if ($status === 'shipped') {

            $randomCode = StaticHelper::secureRandomCode();

            /** @var FileUploader $uploader */
            $uploader = $this->get(FileUploader::class);
            $uploader->setTargetDirectory(sprintf('orders/%s', $randomCode));

            if (isset($data['state_img']) && !empty($data['state_img'])) {
                $order->setStateImg($uploader->upload($data['state_img'], true));
            }

            

            $order->setTrackingCode($data['trackingCode']);
            $order->setShippedMethod($data['shipped_method']);
            $order->setSelfCourierName($data['self_courier_name']);
            $order->setSelfCourierPosition($data['self_courier_position']);
            // $order->setSelfCourierAddress($data['self_courier_address']);
            $order->setSelfCourierTelp($data['self_courier_telp']);
            $order->setShippedAt();
        }
        $em->persist($order);
        $em->flush();

        if ($status === 'shipped') {
            if (isset($data['shipped_product_img']) && !empty($data['shipped_product_img'])) {
                /** @var OrderShippedFileRepository $repository */
                $repoShippedFile = $this->getRepository(OrderShippedFile::class);
                $em = $this->getEntityManager();
                foreach ($data['shipped_product_img'] as $key => $value) {
                    $orderShippedFile = new OrderShippedFile();
                    $orderShippedFile->setOrder($order);
                    $orderShippedFile->setFilePath($uploader->upload($value, true));
                    $em->persist($orderShippedFile);
                    $em->flush();
                }
            }
        }

        

        

        $templates = $this->statusTemplates();
        /** @var DateTime $updatedAt */
        $updatedAt = $order->getUpdatedAt();
        /** @var User $buyer */
        $buyer = $order->getBuyer();
        /** @var Store $store */
        $store = $order->getSeller();
        /** @var User $seller */
        $seller = $store->getUser();
        $contentStatus = $contentButtons = '';

//        if ($status !== 'pending_payment') {
//            $contentStatus = sprintf($templates[$status]['status'], $updatedAt->format('d/m/Y - H:i'));
//            $contentButtons = $templates[$status]['buttons'];
//        }

        if ($status != 'cancel') {
            if ($status === 'tax_invoice' || $status === 'shipped') {
                $this->logOrder($em, $previousOrderValues, $order, $seller);
            } else if ($status === 'partial_delivery') {
                $this->logOrder($em, $previousOrderValues, $order, $seller);
            } else if ($status == 'received' || $status == 'processed' || $status == 'pending_payment') {
                $repositoryUser = $this->getRepository(User::class);
                $user_ppk       = $repositoryUser->find($order->getPpkId());
                $this->logOrder($em, $previousOrderValues, $order, $user_ppk);
            } else {
                $this->logOrder($em, $previousOrderValues, $order, $buyer);
            }
        }

        if (!$order->getIsB2gTransaction() && $status === 'received') {
            $this->setDisbursementProductFee($em, $order);
        }

        if ($status === 'shipped') {
            $buyer = $order->getBuyer();
            $mailToBuyer = $this->get(BaseMail::class);
            $mailToBuyer->setMailSubject($this->getTranslator()->trans('message.info.shipped'));
            $mailToBuyer->setMailTemplate('@__main__/email/order_shipped.html.twig');
            $mailToBuyer->setMailRecipient($buyer->getEmailCanonical());
            $mailToBuyer->setMailData([
                'name' => $buyer->getFirstName(),
                'invoice' => $order->getInvoice(),
                'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToBuyer->send();
        } elseif ($status === 'received') {
            /** @var Store $seller */
            $seller = $order->getSeller();
            /** @var User $owner */
            $owner = $seller->getUser();
            /** @var BaseMail $mailToSeller */
            $mailToSeller = $this->get(BaseMail::class);
            $mailToSeller->setMailSubject($this->getTranslator()->trans('message.info.received'));
            $mailToSeller->setMailTemplate('@__main__/email/order_received.html.twig');
            $mailToSeller->setMailRecipient($owner->getEmailCanonical());
            $mailToSeller->setMailData([
                'name' => $owner->getFirstName(),
                'invoice' => $order->getInvoice(),
                'recipient_type' => 'seller',
                'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToSeller->send();

        }

        return [
            'status' => true,
            'content_status' => $contentStatus,
            'content_buttons' => $contentButtons,
            'shared_id' => $order->getSharedId(),
            'order' => [
                'title' => 'Order Status',
                'invoice' => $order->getInvoice(),
                'status' => $order->getStatus(),
                'seller_id' => $seller->getId(),
                'buyer_id' => $buyer->getId(),
            ],
        ];
    }

    public function req_faktur_pajak($id)
    {
        $repository = $this->getRepository(Order::class);
        $order      = $repository->find($id);
        $em = $this->getEntityManager();

        $notification = new Notification();
        $notification->setSellerId($order->getSeller()->getUser()->getId());
        $notification->setBuyerId(0);
        $notification->setIsSentToSeller(true);
        $notification->setIsSentToBuyer(false);
        $notification->setIsAdmin(false);
        $notification->setTitle($this->getTranslation('notifications.request_faktur'));
        $notification->setContent($this->getTranslation('notifications.request_faktur_text', ['%invoice%' => $order->getInvoice()]));
        $order->setIsRequestFakturPajak(true);
        $em->persist($order);
        $em->persist($notification);
        $em->flush();

        return $this->view('', ['request' => true, 'response' => 'Request Faktur Pajak Berhasil'], 'json');
    }

    private function statusTemplates(): array
    {
        $translator = $this->getTranslator();
        $received = $translator->trans('label.received');
        $cancelled = $translator->trans('label.cancelled');
        $cancelledMessage = $translator->trans('message.info.cancelled');
        $confirmed = $translator->trans('label.confirmed');
        $confirmedMessage = $translator->trans('message.info.confirmed');
        $processed = $translator->trans('label.processed');
        $processedMessage = $translator->trans('message.info.processed');
        $shipped = $translator->trans('label.shipped');
        $shippedMessage = $translator->trans('message.info.shipped');

        return [
            'received' => [
                'status' => '<p class="green"><span>' . $received . '</span> (%s)</p>',
                'buttons' => '',
            ],
            'cancel' => [
                'status' => '<p><span>' . $cancelled . '</span> (%s)</p>',
                'buttons' => '<span class="gBtn red" style="cursor: default;">' . $cancelledMessage . '</span>',
            ],
            'confirmed' => [
                'status' => '<p class="yellow"><span>' . $confirmed . '</span> (%s)</p>',
                'buttons' => '<a href="javascript:void(0);" class="sBtn red seller-act-order" data-state="processed">' . $confirmedMessage . '</a>',
            ],
            'processed' => [
                'status' => '<p class="blue"><span>' . $processed . '</span> (%s)</p>',
                'buttons' => '<a href="javascript:void(0);" class="sBtn red seller-act-order" data-state="shipped">' . $processedMessage . '</a>',
            ],
            'shipped' => [
                'status' => '<p class="green"><span>' . $shipped . '</span> (%s)</p>',
                'buttons' => '<span class="sBtn red" style="cursor: default;">' . $shippedMessage . '</span>',
            ],
        ];
    }

    public function approve()
    {
        $this->denyAccessUnlessGranted('order.approve_received' , 'permission');
        
        $request = $this->getRequest();
        $id = abs($request->request->get('id', '0'));
        $repository = $this->getRepository(Order::class);

        $this->denyAccessUnlessGranted($repository->getOrderDetail($id) , 'order_permission');

        $user = $this->getUser();

        $repoPPK = $this->getRepository(UserPpkTreasurer::class);
        $order      = $repository->find($id);
        $translator = $this->getTranslator();
        BreadcrumbService::add(['label' => $this->getTranslation('label.approve_order')]);


        if ($request->isMethod('POST')) {
            $em = $this->getEntityManager();
            $statusSaatIni = $order->getStatus();
            $prevOrderValues = clone $order;
            // if ($statusSaatIni == 'approve_order_ppk') {
            //     $order->setIsApprovedOrderPPK(true);
            // } else {
                $order->setIsApprovedPPK(true);
                $order->setStatusApprovePpk('disetujui');
                $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
                $this->setDisbursementProductFee($em, $order);
            // }
            $em->persist($order);
            $em->flush();

            $documentApprovalRepository = $this->getRepository(DocumentApproval::class);
            $documentApproval = new DocumentApproval();
            $documentApproval->setOrderId($order);
            $documentApproval->setTypeDocument('bast');
            $documentApproval->setApprovedBy($user);
            $documentApproval->setApprovedAt(new DateTime());
            $documentApproval->setCreatedAt();

            $documentApprovalRepository->add($documentApproval);

            // $status = 'received';
            
            // // if ($order->getPpkPaymentMethod() == 'uang_persediaan') {
            //     $prevOrderValues = clone $order;
            //     $order->setStatus($status);
            //     $user_update = $order->getBuyer();
            //     $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $user_update);
            //     // if ($statusSaatIni == 'approve_order_ppk') {
            //     //     $status = 'processed';
            //     // } else {
            //         $status = 'pending_payment';
            //     // }
            //     $user_update = $order->getSeller()->getUser();
            // // }

            // $response = $this->setOrderStatus($order, $status);
            // dd($response);
            

            if (!empty($order->getTreasurerName()) && !empty($order->getTreasurerEmail())) {

                $em = $this->getEntityManager();
                if (!empty($order->getDokuInvoiceNumber())) {
                    $order->setDokuInvoiceNumber('');
                }
                $em->persist($order);
                $em->flush();

                $data_bendahara = $repoPPK->findOneBy(['email' => $order->getTreasurerEmail()]);

                /**
                 * Send email to bendahara
                 */
                try {
                    /** @var BaseMail $mailToSeller */
                    $mailToSeller = $this->get(BaseMail::class);
                    $mailToSeller->setMailSubject('Bmall '.$order->getInvoice().'_Payment Process');
                    $mailToSeller->setMailTemplate('@__main__/email/sent_to_pic.html.twig');
                    $mailToSeller->setMailRecipient($order->getTreasurerEmail());
                    $mailToSeller->setMailData([
                        'name' => $order->getTreasurerName(),
                        'pp' => $order->getName(),
                        'ppk_name' => $order->getPpkName(),
                        'satker' => $data_bendahara->getSatker(),
                        'klpd' => $data_bendahara->getKldi(),
                        'email' => $order->getTreasurerEmail(),
                        'nip' => $order->getTreasurerNip(),
                        'invoice' => $order->getInvoice(),
                        'merchant' => $order->getSeller()->getName(),
                        'status' => 'received',
                        'payment_method' => $order->getPpkPaymentMethod(),
                        'type' => 'treasurer',
                        'link_login' => getenv('APP_URL').'/login?email='.$order->getTreasurerEmail(),
                        'link_confirm' => $this->generateUrl('ppk_approve', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_pay' => $this->generateUrl('treasurer_pay_with_channel', ['id' => $order->getSharedId(), 'channel' => 'doku'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_i' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'invoice'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_b' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'bast'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_sp' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'spk'], UrlGeneratorInterface::ABSOLUTE_URL),
                        'link_k' => $this->generateUrl('document_pic', ['id' => $order->getId(),'type'=>'receipt'], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                    $mailToSeller->send();

                } catch (\Throwable $exception) {
                    // $this->logger->error('Send Email Bendahara Throwable', [$exception->getMessage()]);
                }
            }

            $this->addFlash(
                'success',
                $translator->trans('message.success.ppk_order_approved')
            );
        }

        return $this->view('', ['approved' => true], 'json');
    }

    public function detail($id)
    {
        $this->denyAccessUnlessGranted((int) $id , 'order_permission');
        $request = $this->getRequest();

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $userRepository = $this->getRepository(User::class);
        $storeRepository = $this->getRepository(Store::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $parameters = [];

        $this->getDefaultData();
        $user = $this->getUser();

        $order = $repository->getOrderDetail($id, $parameters);
        $reduceOrderByVoucher = [];

        if (!empty($order['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($order['o_sharedId'], $order['s_pkp']);
        }

        $disbursementRepository = $this->getRepository(Disbursement::class);
        $disbursementData = null;

        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_SELLER') {
            try {
                $disbursementData = $disbursementRepository->findOneBy(['orderId' => $id]);
            } catch (\Throwable $throwable) {
                $disbursementData = null;
            }
        }
        $tokenId = 'user_ppktreasurer_save';
        // dd($order);
        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);

        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        if (!empty($order['o_satkerId'])) {
            $satkerData = $this->getRepository(Satker::class)->find($order['o_satkerId']);
            $pendingPaymentBni = $bniRepository->findOneBy([
                'va' => getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$satkerData->getDigitVa(),
                'status' => 'pending'
            ]);
        } else {
            $pendingPaymentBni = null;
        }

        $orderBpd = $repository->find($order['o_id']);
        $haveBpd  = $bpdCcRepository->findOneBy([
            'orders' => $orderBpd,
        ], ['id' => 'DESC']);

        $shippedFiles = [];

        if($order){
            if($order['o_shippedFiles'] && $order['o_shipped_method'] == 'self_courier'){
                foreach ($order['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }
        // get ppk stamp & signature
        $ppkStamp = '';
        $ppkSignature = '';
        if ($order['o_ppkId']){
            $data_ppk = $userRepository->find($order['o_ppkId']);
            $ppkStamp = $data_ppk->getUserStamp();
            $ppkSignature = $data_ppk->getUserSignature();
        }

        // get data bendahara
        $treasurerStamp = '';
        $treasurerSignature = '';
        if ($order['o_treasurerId']){
            $data_treasurer = $userRepository->find($order['o_treasurerId']);
            $treasurerStamp = $data_treasurer->getUserStamp();
            $treasurerSignature = $data_treasurer->getUserSignature();
        }
        // dd('halo');

        // get pp stamp & signature
        $data_pp = $userRepository->find($order['u_id']);

        // get merchant stamp & signature
        $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);

        $order_partials = [];

        if(!is_null($order['o_master_id'])) {
            $order_partials = $repository->getPartialOrders($order['o_master_id']);
        }else if (is_null($order['o_master_id']) && $order['o_type_order'] == 'master') {
            $order_partials = $repository->getPartialOrders($order['o_id']);
        }
        
        foreach ($order_partials as $key => $order_partial) {
            $order_partials[$key]['date_format'] = indonesiaDateFormatAlt($order_partial['o_createdAt']->getTimestamp(), 'l, d F Y'); 
            $order_partials[$key]['o_products'] = $repository->getOrderProducts($order_partial['o_id']);
            $order_partials[$key]['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($order_partial['o_id']);
        }

        // dd([
        //     'order' => $order,
        //     'shippedFiles' => $shippedFiles,
        //     'pp_stamp' => $data_pp->getUserStamp(),
        //     'pp_signature' => $data_pp->getUserSignature(),
        //     'ppk_stamp' => $ppkStamp,
        //     'ppk_signature' => $ppkSignature,
        //     'treasurer_stamp' => $treasurerStamp,
        //     'treasurer_signature' => $treasurerSignature,
        //     'seller_stamp' => $store->getUser()->getUserStamp(),
        //     'seller_signature' => $store->getUser()->getUserSignature(),
        //     'user' => $user,
        //     'order_change_log' => $orderLog,
        //     'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
        //     'token_id' => $tokenId,
        //     'reduce_order_by_id' => $reduceOrderByVoucher,
        //     'disbursement_data' => $disbursementData,
        //     'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
        //     'backQRIS' => $request->query->get('back_qris', '0'),
        //     'haveBpd' => $haveBpd,
        //     'order_partials' => $order_partials,
        //     'order_master' => !is_null($order['o_master_id']) ? $repository->getOrderDetail($order['o_master_id']) : null,
        // ]);
        
        return $this->view('@__main__/public/user/user_ppk_treasurer/detail_order.html.twig', [
            'order' => $order,
            'shippedFiles' => $shippedFiles,
            'pp_stamp' => $data_pp->getUserStamp(),
            'pp_signature' => $data_pp->getUserSignature(),
            'ppk_stamp' => $ppkStamp,
            'ppk_signature' => $ppkSignature,
            'treasurer_stamp' => $treasurerStamp,
            'treasurer_signature' => $treasurerSignature,
            'seller_stamp' => $store->getUser()->getUserStamp(),
            'seller_signature' => $store->getUser()->getUserSignature(),
            'user' => $user,
            'order_change_log' => $orderLog,
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'token_id' => $tokenId,
            'reduce_order_by_id' => $reduceOrderByVoucher,
            'disbursement_data' => $disbursementData,
            'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
            'backQRIS' => $request->query->get('back_qris', '0'),
            'haveBpd' => $haveBpd,
            'order_partials' => $order_partials,
            'order_master' => !is_null($order['o_master_id']) ? $repository->getOrderDetail($order['o_master_id']) : null,
            'tax_value' => $this->getParameter('tax_value'),
        ]);
    }

    public function detailPemesanan($id)
    {
        $request = $this->getRequest();

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $userRepository = $this->getRepository(User::class);
        $storeRepository = $this->getRepository(Store::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $parameters = [];

        $this->getDefaultData();
        $user = $this->getUser();

        $order = $repository->getOrderDetail($id, $parameters);

        $reduceOrderByVoucher = [];

        if (!empty($order['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($order['o_sharedId'], $order['s_pkp']);
        }

        $disbursementRepository = $this->getRepository(Disbursement::class);
        $disbursementData = null;

        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_SELLER') {
            try {
                $disbursementData = $disbursementRepository->findOneBy(['orderId' => $id]);
            } catch (\Throwable $throwable) {
                $disbursementData = null;
            }
        }
        $tokenId = 'user_ppktreasurer_save';
        // dd($order);
        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);

        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        if (!empty($order['o_satkerId'])) {
            $satkerData = $this->getRepository(Satker::class)->find($order['o_satkerId']);
            $pendingPaymentBni = $bniRepository->findOneBy([
                'va' => getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$satkerData->getDigitVa(),
                'status' => 'pending'
            ]);
        } else {
            $pendingPaymentBni = null;
        }

        $orderBpd = $repository->find($order['o_id']);
        $haveBpd  = $bpdCcRepository->findOneBy([
            'orders' => $orderBpd,
        ], ['id' => 'DESC']);

        $shippedFiles = [];

        if($order){
            if($order['o_shippedFiles'] && $order['o_shipped_method'] == 'self_courier'){
                foreach ($order['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }
        // get ppk stamp & signature
        $ppkStamp = '';
        $ppkSignature = '';
        if ($order['o_ppkId']){
            $data_ppk = $userRepository->find($order['o_ppkId']);
            $ppkStamp = $data_ppk->getUserStamp();
            $ppkSignature = $data_ppk->getUserSignature();
        }

        // get data bendahara
        $treasurerStamp = '';
        $treasurerSignature = '';
        if ($order['o_treasurerId']){
            $data_treasurer = $userRepository->find($order['o_treasurerId']);
            $treasurerStamp = $data_treasurer->getUserStamp();
            $treasurerSignature = $data_treasurer->getUserSignature();
        }
        // dd('halo');

        // get pp stamp & signature
        $data_pp = $userRepository->find($order['u_id']);

        // get merchant stamp & signature
        $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);

        $order_partials = [];

        if($order['o_type_order'] == 'master') {
            $order_partials = $repository->getPartialOrders($order['o_id']);

            foreach ($order_partials as $key => $order_partial) {
                $order_partials[$key]['date_format'] = indonesiaDateFormatAlt($order_partial['o_createdAt']->getTimestamp(), 'l, d F Y'); 
                $order_partials[$key]['o_products'] = $repository->getOrderProducts($order_partial['o_id']);
            }
        }
        
        
        return $this->view('@__main__/public/user/user_ppk_treasurer/detail_pemesanan.html.twig', [
            'order' => $order,
            'shippedFiles' => $shippedFiles,
            'pp_stamp' => $data_pp->getUserStamp(),
            'pp_signature' => $data_pp->getUserSignature(),
            'ppk_stamp' => $ppkStamp,
            'ppk_signature' => $ppkSignature,
            'treasurer_stamp' => $treasurerStamp,
            'treasurer_signature' => $treasurerSignature,
            'seller_stamp' => $store->getUser()->getUserStamp(),
            'seller_signature' => $store->getUser()->getUserSignature(),
            'user' => $user,
            'order_change_log' => $orderLog,
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'token_id' => $tokenId,
            'reduce_order_by_id' => $reduceOrderByVoucher,
            'disbursement_data' => $disbursementData,
            'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
            'backQRIS' => $request->query->get('back_qris', '0'),
            'haveBpd' => $haveBpd,
            'order_partials' => $order_partials,
        ]);
    }

    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            
            $query = $parameters;
            $query['page'] = $page;

            return $this->get('router')->generate($parameters['redirect'], $query);
        };
    }

    public function save_detail()
    {
        $request = $this->getRequest();
        $flashBag = $this->get('session.flash_bag');
        $route = 'user_ppktreasurer_detail';
        $textFaktur = '';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $repository = $this->getRepository(Order::class);
            $order = $repository->find($formData['order_id']);


            if (!empty($order)) {
                
                $em = $this->getEntityManager();
                if (isset($formData['tax_types']) && !empty($formData['tax_types'])) {

                    $prevOrderValues = clone $order;
                    $order->setTaxType($formData['tax_types']);
                    // $order->setTaxEBilling($formData['ebilling']);
    
                    if ($formData['tax_types'] == '59') {
                        if ($order->getTotal() + $order->getShippingPrice() <= 2220000) {
                            $textFaktur = ', Mohon upload faktur pajak dengan kode 010';
                        }
                        if ($formData['pph_choose'] == 'lainnya') {
                            $isOtherPph = true;
                            $otherPphName = $formData['other_pph_name'];
                            $pph = $formData['other_pph_persentase'];
                        } else {
                            $isOtherPph = false;
                            $otherPphName = '';
                            $pph = $formData['pph_choose'];
                        }
    
                        if ($formData['ppn_choose'] == 'lainnya') {
                            $isOtherPpn = true;
                            $otherPpnName = $formData['other_ppn_name'];
                            $ppn = $formData['other_ppn_persentase'];
                        } else {
                            $isOtherPpn = false;
                            $otherPpnName = '';
                            $ppn = $formData['ppn_choose'];
                        }
                        $order->setIsOtherPph($isOtherPph);
                        $order->setOtherPphName($otherPphName);
    
                        $order->setIsOtherPpn($isOtherPpn);
                        $order->setOtherPpnName($otherPpnName);
    
                        $order->setTreasurerPph($pph);
                        $order->setTreasurerPpn($ppn);
    
                        $order->setTreasurerPphNominal($formData['pph_nominal']);
                        $order->setTreasurerPpnNominal($formData['ppn_nominal']);
                        $order->setPpkPaymentMethod('pembayaran_langsung');
                    }

                    $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
                }
                
                $notification = new Notification();
                $notification->setSellerId($order->getSeller()->getUser()->getId());
                $notification->setBuyerId(0);
                $notification->setIsSentToSeller(true);
                $notification->setIsSentToBuyer(false);
                $notification->setIsAdmin(false);
                $notification->setTitle($this->getTranslation('notifications.request_faktur'));
                $notification->setContent($this->getTranslation('notifications.request_faktur_text', ['%invoice%' => $order->getInvoice()]));
                $order->setIsRequestFakturPajak(true);
                $em->persist($notification);
                $em->persist($order);
                $em->flush();


                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.update').$textFaktur
                );
            } else {
                $this->addFlash(
                    'warning',
                    $this->getTranslator()->trans('title.page.500')
                );
            }

            // if ($formData['btn_submit'] == 'cc') {
            //     $redirect = $this->redirectToRoute('user_payment_confirmation', ['invoice' => $order->getSharedId() , 'access' => 'treasurer']);
            // } else {
            //     $redirect = $this->redirectToRoute('treasurer_pay_with_channel', ['id' => $order->getSharedId() , 'channel' => 'doku']);
            // }
            $redirect = $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $order->getId()]);
        }

        return $redirect;
    }

    public function other_document()
    {
        $request = $this->getRequest();
        $id      = $request->request->get('order_id');
        if ($request->isMethod('POST')) {
            $repository = $this->getRepository(Order::class);
            $order = $repository->find($id);
            $prevOrderValues = clone $order;

            $uploadedFile = $request->files->get('other_document', null);
            
            $em = $this->getEntityManager();
            $prefixPath = $this->constructUploadPath();
            $uploader = $this->get(FileUploader::class);
            $uploader->setTargetDirectory($prefixPath);

            if ($uploadedFile == null) {
                $uploadedFile = $request->files->get('withholding_tax_file', null);
                if ($uploadedFile == null) {
                    $uploadedFile = $request->files->get('faktur_pajak', null);
                    $uploadPath = $uploader->upload($uploadedFile, false);
                    $order->setTaxInvoiceFile($uploadPath);
                } else {
                    $uploadPath = $uploader->upload($uploadedFile, false);
                    $order->setWithholdingTaxSlipFile($uploadPath);
                }
            } else {
                $uploadPath = $uploader->upload($uploadedFile, false);
                $other_document_name = $request->request->get('other_document_name');
                $order->setOtherDocumentName($other_document_name);
                $order->setOtherDocument($uploadPath);
            }
            $this->logOrder($this->getEntityManager(), $prevOrderValues, $order, $this->getUser());
            
            
            

            $em->persist($order);
            $em->flush();

            $this->addFlash(
                'success',
                $this->getTranslator()->trans('message.success.update')
            );
        }
        return $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $id]);
    }

    public function constructUploadPath($path = ''): string
    {
        $prefix = 'orders/';

        $parts = explode('/', $path);

        if (isset($parts[1]) && count($parts) === 3) {
            $prefix .= $parts[1];
            $prefix .= '/';

            return $prefix;
        }

        $prefix .= 'reupload_file/';

        return $prefix;
    }


    public function export()
    {
        $request = $this->getRequest();
        $keyword = $request->query->get('keyword', null);
        $status_search  = $request->query->get('status', null);
        $filter_status_order = $request->query->get('filter_status_order', null);
        $repository = $this->getRepository(Order::class);
        $storeRepository = $this->getRepository(Store::class);

        $user = $this->getUser();
        $parameters = [
            'order_by' => 'o.id',
            'sort_by' => 'DESC',
        ];

        if (!empty($keyword)) {
            $parameters['key_invoice'] = $keyword;
        }


        if (!empty($filter_status_order)) {
            $parameters['status'] = $filter_status_order;
        }

        if ($user->getSubRole() == 'PPK') {
            $parameters['ppk_user'] = $user->getId();
            // $parameters['status_multiple'] = ['pending_approve','received','document','tax_invoice','payment_process','paid'];
            if (!empty($status_search)) {
                $parameters['filter_status_ppk'] = $status_search;
            }
        } else if ($user->getSubRole() == 'TREASURER') {
            $parameters['treasurer_user'] = $user->getId();
            $parameters['status_multiple'] = ['tax_invoice','pending_payment','payment_process','paid'];
            if (!empty($status_search)) {
                $parameters['filter_status_treasurer'] = $status_search;
            }
        } else {
            $userStore = $storeRepository->findOneBy(['user'=>$user]);
            if ($userStore != null) {
                $parameters['seller'] = $userStore;
            } else {
                $parameters['buyer'] = $user;
            }
        }

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $data = $adapter->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $data = 0;
        }
        // dd($data);
        $url = $this->get('router')->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $package = new UrlPackage($url, new EmptyVersionStrategy());
        $writer = null;

        if (count($data) > 0) {

            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'ID');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Invoice');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Shared Invoice');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Total');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Shipping Amount');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Shipping Courier');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Shipping Service');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Tracking Code');
            $sheet->setCellValueByColumnAndRow(11, 1, 'Buyer Name');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Buyer Email');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Buyer Phone');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Buyer Address');
            $sheet->setCellValueByColumnAndRow(15, 1, 'Buyer City');
            $sheet->setCellValueByColumnAndRow(16, 1, 'Buyer Province');
            $sheet->setCellValueByColumnAndRow(17, 1, 'Buyer Post Code');
            $sheet->setCellValueByColumnAndRow(18, 1, 'Note');
            $sheet->setCellValueByColumnAndRow(19, 1, 'Tax Document Email');
            $sheet->setCellValueByColumnAndRow(20, 1, 'Tax Document Phone');
            $sheet->setCellValueByColumnAndRow(21, 1, 'Tax Document File');
            $sheet->setCellValueByColumnAndRow(22, 1, 'Is B2G Transaction');
            $sheet->setCellValueByColumnAndRow(23, 1, 'Negotiation Status');
            $sheet->setCellValueByColumnAndRow(24, 1, 'Execution Time');
            $sheet->setCellValueByColumnAndRow(25, 1, 'Job Package Name');
            $sheet->setCellValueByColumnAndRow(26, 1, 'Fiscal Year');
            $sheet->setCellValueByColumnAndRow(27, 1, 'Source of Fund');
            $sheet->setCellValueByColumnAndRow(28, 1, 'Budget Ceiling');
            $sheet->setCellValueByColumnAndRow(29, 1, 'BAST File');
            $sheet->setCellValueByColumnAndRow(30, 1, 'Delivery Paper File');
            $sheet->setCellValueByColumnAndRow(31, 1, 'Tax Invoice File');
            $sheet->setCellValueByColumnAndRow(32, 1, 'Invoice File');
            $sheet->setCellValueByColumnAndRow(33, 1, 'Receipt File');
            $sheet->setCellValueByColumnAndRow(34, 1, 'SPK File');
            $sheet->setCellValueByColumnAndRow(35, 1, 'Store Name');
            $sheet->setCellValueByColumnAndRow(36, 1, 'Store Address');
            $sheet->setCellValueByColumnAndRow(37, 1, 'Product Name');
            $sheet->setCellValueByColumnAndRow(38, 1, 'Product Category');
            $sheet->setCellValueByColumnAndRow(39, 1, 'Payment Method');
            $sheet->setCellValueByColumnAndRow(40, 1, 'Shipped Method');
            $sheet->setCellValueByColumnAndRow(41, 1, 'Status Last Changed On');
            $sheet->setCellValueByColumnAndRow(42, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(43, 1, 'Updated At');


            foreach ($data as $item) {
                $status = ucwords(str_replace('_', ' ', $item[0]['status']));
                $taxDocumentFile = !empty($item[0]['taxDocumentFile']) ? $package->getUrl($item[0]['taxDocumentFile']) : null;
                $bastFile = !empty($item[0]['bastFile']) ? $package->getUrl($item[0]['bastFile']) : null;
                $deliveryPaperFile = !empty($item[0]['deliveryPaperFile']) ? $package->getUrl($item[0]['deliveryPaperFile']) : null;
                $taxInvoiceFile = !empty($item[0]['taxInvoiceFile']) ? $package->getUrl($item[0]['taxInvoiceFile']) : null;
                $invoiceFile = !empty($item[0]['invoiceFile']) ? $package->getUrl($item[0]['invoiceFile']) : null;
                $receiptFile = !empty($item[0]['receiptFile']) ? $package->getUrl($item[0]['receiptFile']) : null;
                $workOrderLetterFile = !empty($item[0]['workOrderLetterFile']) ? $package->getUrl($item[0]['workOrderLetterFile']) : null;

                /** @var OrderRepository $orderRepository */
                $orderRepository = $this->getRepository(Order::class);
                $detailOrder = $orderRepository->getOrderProducts($item[0]['id']);

                $storeName = $detailOrder[0]['s_name'];
                $storeAddress = $detailOrder[0]['s_address'];
                $productCategory = $detailOrder[0]['pc_name'];
                $productName = $detailOrder[0]['p_name'];

                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item[0]['id']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item[0]['invoice']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item[0]['sharedInvoice']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $status);
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item[0]['total']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item[0]['shippingPrice']);
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item[0]['shippingCourier']);
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item[0]['shippingService']);
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item[0]['trackingCode']);
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item[0]['name']);
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item[0]['email']);
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), $item[0]['phone']);
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item[0]['address']);
                $sheet->setCellValueByColumnAndRow(15, ($number + 1), $item[0]['city']);
                $sheet->setCellValueByColumnAndRow(16, ($number + 1), $item[0]['province']);
                $sheet->setCellValueByColumnAndRow(17, ($number + 1), $item[0]['postCode']);
                $sheet->setCellValueByColumnAndRow(18, ($number + 1), $item[0]['note']);
                $sheet->setCellValueByColumnAndRow(19, ($number + 1), $item[0]['taxDocumentEmail']);
                $sheet->setCellValueByColumnAndRow(20, ($number + 1), $item[0]['taxDocumentPhone']);
                $sheet->setCellValueByColumnAndRow(21, ($number + 1), $taxDocumentFile);
                $sheet->setCellValueByColumnAndRow(22, ($number + 1), $item[0]['isB2gTransaction'] ? 'Yes' : 'No');
                $sheet->setCellValueByColumnAndRow(23, ($number + 1), $item[0]['negotiationStatus']);
                $sheet->setCellValueByColumnAndRow(24, ($number + 1), $item[0]['executionTime']);
                $sheet->setCellValueByColumnAndRow(25, ($number + 1), $item[0]['jobPackageName']);
                $sheet->setCellValueByColumnAndRow(26, ($number + 1), $item[0]['fiscalYear']);
                $sheet->setCellValueByColumnAndRow(27, ($number + 1), $item[0]['sourceOfFund']);
                $sheet->setCellValueByColumnAndRow(28, ($number + 1), $item[0]['budgetCeiling']);
                $sheet->setCellValueByColumnAndRow(29, ($number + 1), $bastFile);
                $sheet->setCellValueByColumnAndRow(30, ($number + 1), $deliveryPaperFile);
                $sheet->setCellValueByColumnAndRow(31, ($number + 1), $taxInvoiceFile);
                $sheet->setCellValueByColumnAndRow(32, ($number + 1), $invoiceFile);
                $sheet->setCellValueByColumnAndRow(33, ($number + 1), $receiptFile);
                $sheet->setCellValueByColumnAndRow(34, ($number + 1), $workOrderLetterFile);
                $sheet->setCellValueByColumnAndRow(35, ($number + 1), $storeName);
                $sheet->setCellValueByColumnAndRow(36, ($number + 1), $storeAddress);
                $sheet->setCellValueByColumnAndRow(37, ($number + 1), $productName);
                $sheet->setCellValueByColumnAndRow(38, ($number + 1), $productCategory);
                $sheet->setCellValueByColumnAndRow(39, ($number + 1), !empty($item[0]['ppk_payment_method']) ? $this->getParameter('ppk_method_options')[$item[0]['ppk_payment_method']]:'');
                $sheet->setCellValueByColumnAndRow(40, ($number + 1), !empty($item[0]['shipped_method']) ? $this->getParameter('shipped_method_options')[$item[0]['shipped_method']]:'');
                $sheet->setCellValueByColumnAndRow(41, ($number + 1), !empty($item[0]['statusChangeTime']) ? $item[0]['statusChangeTime']->format('Y-m-d H:i:s') : '-');
                $sheet->setCellValueByColumnAndRow(42, ($number + 1), $item[0]['createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(43, ($number + 1), !empty($item[0]['updatedAt']) ? $item[0]['updatedAt']->format('Y-m-d H:i:s') : '-');


                $number++;
            }

            $writer = new Xlsx($spreadsheet);
            // Create a Temporary file in the system
            $fileName = 'data_order_'.strtolower($user->getSubRole()).'.xlsx';
            $temp_file = tempnam(sys_get_temp_dir(), $fileName);
            
            // Create the excel file in the tmp directory of the system
            $writer->save($temp_file);
            
            // Return the excel file as an attachment
            return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
        }

        return 0;
    }

    public function transaction()
    {
        $request = $this->getRequest();

        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(Order::class);
        $page = abs($request->query->get('page', '1'));
        $keyword = $request->query->get('search_invoice', null);
        $status_search = $request->query->get('filter_status', null);
        $filter_status_order = $request->query->get('filter_status_order', null);

        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'order_by' => 'o.createdAt',
            'sort_by' => 'DESC',
            'search_invoice' => $keyword,
            'filter_status' => $status_search,
            'filter_status_order' => $filter_status_order,
        ];

        if ($user->getSubRole() == 'PPK') {
            $parameters['ppk_user'] = $user->getId();
            $parameters['ppk_user_collec'] = $user;
            // $parameters['status_multiple'] = ['approve_order_ppk','processed','shipped','pending_approve','received','document','tax_invoice','pending_payment','payment_process','paid'];
        } else {
            $parameters['treasurer_user'] = $user->getId();
            $parameters['status_multiple'] = ['tax_invoice','pending_payment','payment_process','paid'];
        }

        $parameters2 = $parameters;


        if (!empty($keyword)) {
            $parameters['key_invoice'] = $keyword;
        }

        if ($user->getSubRole() == 'PPK') {
            if (!empty($status_search)) {
                $parameters['filter_status_ppk'] = $status_search;
            }
        } else {
            if (!empty($status_search)) {
                $parameters['filter_status_treasurer'] = $status_search;
            }
        }

        if (!empty($filter_status_order)) {
            $parameters['status'] = $filter_status_order;
        }

        

        $parameters['limit'] = $limit;
        $parameters['offset'] = $offset;
        $parameters['redirect'] = 'user_ppktreasurer_dashboard';

        try {
            $adapter = new DoctrineORMAdapter($repository->getPaginatedResult($parameters));
            $adapter_total = new DoctrineORMAdapter($repository->getPaginatedResult($parameters2));
            $pagination = New Pagerfanta($adapter);
            $pagination
                ->setMaxPerPage($limit)
                ->setCurrentPage($page)
            ;

            $view = new DefaultView(new CustomPaginationTemplate());
            $options = ['proximity' => 3];
            $html = $view->render($pagination, $this->routeGenerator($parameters), $options);
            $documents = $adapter->getQuery()->getArrayResult();
            $jumlah_data = $adapter_total->getQuery()->getArrayResult();
        } catch (Exception $e) {
            $documents = [];
            $pagination = $html = null;
            $jumlah_data = [];
        }

        BreadcrumbService::add(['label' => $this->getTranslation('label.dashboard_'.strtolower($user->getSubRole()))]);
        
        foreach ($documents as $key => $value) {
            $documents[$key][0]['o_products'] = $repository->getOrderProducts($value[0]['id']);
            $documents[$key][0]['o_complaint'] = $repository->getOrderComplaint($value[0]['id']);
            $documents[$key][0]['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($value[0]['id']);
            $documents[$key][0]['o_shippedFiles'] = $repository->getOrderShippedFiles($value[0]['id']);
        }

        $status_count = [
            'new_order' => 0,
            'confirmed' => 0,
            'processed' => 0,
            'shipped' => 0,
            'received' => 0,
            'pending_payment' => 0,
            'paid' => 0,
        ];
        foreach ($jumlah_data as $key => $value) {
            if (isset($status_count[$value[0]['status']])) {
                $status_count[$value[0]['status']] += 1;
            }
        }
        
        return $this->view('@__main__/public/user/user_ppk_treasurer/transaksi.html.twig', [
            'documents' => $documents,
            'user' => $user,
            'jumlah_data' => $jumlah_data,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'status_count' => $status_count,
            'type' => $user->getSubRole(),
        ]);
    }

    public function shipping_partial($id)
    {
        $request = $this->getRequest();

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $userRepository = $this->getRepository(User::class);
        $storeRepository = $this->getRepository(Store::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $parameters = [];

        $this->getDefaultData();
        $user = $this->getUser();

        $order = $repository->getOrderDetail($id, $parameters);
        $reduceOrderByVoucher = [];

        if (!empty($order['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($order['o_sharedId'], $order['s_pkp']);
        }

        $disbursementRepository = $this->getRepository(Disbursement::class);
        $disbursementData = null;

        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_SELLER') {
            try {
                $disbursementData = $disbursementRepository->findOneBy(['orderId' => $id]);
            } catch (\Throwable $throwable) {
                $disbursementData = null;
            }
        }
        $tokenId = 'user_ppktreasurer_save';
        // dd($order);
        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);

        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        if (!empty($order['o_satkerId'])) {
            $satkerData = $this->getRepository(Satker::class)->find($order['o_satkerId']);
            $pendingPaymentBni = $bniRepository->findOneBy([
                'va' => getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$satkerData->getDigitVa(),
                'status' => 'pending'
            ]);
        } else {
            $pendingPaymentBni = null;
        }

        $orderBpd = $repository->find($order['o_id']);
        $haveBpd  = $bpdCcRepository->findOneBy([
            'orders' => $orderBpd,
        ], ['id' => 'DESC']);

        $shippedFiles = [];

        if($order){
            if($order['o_shippedFiles'] && $order['o_shipped_method'] == 'self_courier'){
                foreach ($order['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }
        // get ppk stamp & signature
        $ppkStamp = '';
        $ppkSignature = '';
        if ($order['o_ppkId']){
            $data_ppk = $userRepository->find($order['o_ppkId']);
            $ppkStamp = $data_ppk->getUserStamp();
            $ppkSignature = $data_ppk->getUserSignature();
        }

        // get data bendahara
        $treasurerStamp = '';
        $treasurerSignature = '';
        if ($order['o_treasurerId']){
            $data_treasurer = $userRepository->find($order['o_treasurerId']);
            $treasurerStamp = $data_treasurer->getUserStamp();
            $treasurerSignature = $data_treasurer->getUserSignature();
        }
        // dd('halo');

        // get pp stamp & signature
        $data_pp = $userRepository->find($order['u_id']);

        // get merchant stamp & signature
        $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);

        $order_partials = $repository->getPartialOrders($order['o_id']);

        foreach ($order_partials as $key => $partial) {
            $order_partials[$key]['date_format'] = indonesiaDateFormatAlt($partial['o_createdAt']->getTimestamp(), 'l, d F Y'); 
        }
        
        return $this->view('@__main__/public/user/user_ppk_treasurer/partial.html.twig', [
            'order' => $order,
            'order_partials' => $order_partials,
            'shippedFiles' => $shippedFiles,
            'pp_stamp' => $data_pp->getUserStamp(),
            'pp_signature' => $data_pp->getUserSignature(),
            'ppk_stamp' => $ppkStamp,
            'ppk_signature' => $ppkSignature,
            'treasurer_stamp' => $treasurerStamp,
            'treasurer_signature' => $treasurerSignature,
            'seller_stamp' => $store->getUser()->getUserStamp(),
            'seller_signature' => $store->getUser()->getUserSignature(),
            'user' => $user,
            'order_change_log' => $orderLog,
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'token_id' => $tokenId,
            'reduce_order_by_id' => $reduceOrderByVoucher,
            'disbursement_data' => $disbursementData,
            'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
            'backQRIS' => $request->query->get('back_qris', '0'),
            'haveBpd' => $haveBpd
        ]);
    }

    public function shipping_partial_proccess()  : RedirectResponse
    {
        $request = $this->getRequest();
        $this->denyAccessUnlessGranted('order.order_spp' , 'permission');
        $this->denyAccessUnlessGranted((int) $request->request->get('order_id') , 'order_permission');

        $user = $this->getUser();

        
        $repository = $this->getRepository(Order::class);
        $em = $this->getEntityManager();
        $documentApprovalRepository = $this->getRepository(DocumentApproval::class);

        $totalQtyBarangSend = 0;
        $totalQtyBarang = 0;

        $order = $repository->find($request->request->get('order_id'));
        $store = $order->getSeller();
        $order_partials = $repository->getPartialOrders($request->request->get('order_id'));
        $orderProducts = $order->getOrderProducts();
        $orderProductSends = $request->request->get('order_products');

        if($request->request->get('type_send' , 'parsial') == 'full') {

            $order->setTypeOrder('partial');
            $order->setStatus('processed');
            $order->setMasterId(null);
            $order->setInvoice($order->getInvoice());
            $order->setNote($request->request->get('note' , ''));
            $order->setUpdatedAt();
            $order->setCreatedAt();

            $em->persist($order);
            $em->flush();     
            
            return $this->redirectToRoute('user_ppktreasurer_detail' , ['id' => $order->getId()]);
        }


        foreach ($orderProducts as $key => $orderProduct) {
            if($orderProductSends[$key]['id'] == $orderProduct->getId()){
                $totalQtyBarangSend += isset($orderProductSends[$key]['quantity_to_send']) ? $orderProductSends[$key]['quantity_to_send'] : 0;
                $totalQtyBarang += $orderProduct->getQuantity();
            }
        }

        if($totalQtyBarang == $totalQtyBarangSend) {
            $order->setTypeOrder('partial');
            $order->setStatus('processed');
            $order->setMasterId(null);
            $order->setInvoice($order->getInvoice());
            $order->setNote($request->request->get('note'));
            $order->setUpdatedAt();
            $order->setCreatedAt();

            $em->persist($order);
            $em->flush();     
            
            return $this->redirectToRoute('user_ppktreasurer_detail' , ['id' => $order->getId()]);
        }
        
        $batch = count($order_partials) + 1;

        // membuat partial order
        $newOrder = new Order();
        $reflection = new ReflectionClass($order);

        $total = 0;
        $totalTaxNominal = 0;


        $this->copyEntity($order, $newOrder , ['id']);

        $newOrder->setTypeOrder('partial');
        $newOrder->setStatus('processed');
        $newOrder->setMasterId($order->getId());
        $newOrder->setInvoice($order->getInvoice().'-'.$batch);
        $newOrder->setBatch($batch);
        $newOrder->setNote($request->request->get('note'));
        $newOrder->setUpdatedAt();
        $newOrder->setCreatedAt();

        $em->persist($newOrder);
        $em->flush();        

        foreach ($orderProducts as $key => $orderProduct) {
            if($orderProductSends[$key]['id'] == $orderProduct->getId()){
                $newOrderProduct = new OrderProduct();

                // $totalQtyBarangSend += $orderProductSends[$key]['quantity_to_send'];
                // $totalQtyBarang += $orderProduct->getQuantity();

                $qtyToSend = isset($orderProductSends[$key]['quantity_to_send']) ? $orderProductSends[$key]['quantity_to_send'] : 0;


                $sisaBarang = $orderProduct->getQuantityToSend() - $qtyToSend;

                // order produk partial
                $this->copyEntity($orderProduct, $newOrderProduct , ['id' , 'order']);
                $newOrderProduct->setOrder($newOrder);
                $newOrderProduct->setQuantity($qtyToSend);

                $total += $qtyToSend * $orderProduct->getPrice();

                if($orderProduct->getWithTax() == 1 ){
                    $totalTaxNominal += round($orderProduct->getTaxValue() / 100 * $total);
                }
                $newOrderProduct->setTotalPrice($total);
                $newOrderProduct->setTaxNominal($totalTaxNominal);

                $em->persist($newOrderProduct);
                $em->flush();

                //update sisa barang 
                $orderProduct->setQuantityToSend($sisaBarang);
                $em->persist($orderProduct);
                $em->flush();
            }
        }

        $orderNegotiations = $order->getOrderNegotiations();
        $shipping_price = 0;

        foreach ($orderNegotiations as $orderNegotiation) {
            $newOrderNegotiation = new OrderNegotiation();
            $this->copyEntity($orderNegotiation, $newOrderNegotiation , ['id' , 'order']);
            $negotiatedShippingPrice = round(($totalQtyBarangSend / $totalQtyBarang) * $orderNegotiation->getNegotiatedShippingPrice());
            $shipping_price = $negotiatedShippingPrice + round(($negotiatedShippingPrice * (11/100)));
            $taxNominalShipping = round($negotiatedShippingPrice * 11 / 100);

            $newOrderNegotiation->setOrder($newOrder);
            $newOrderNegotiation->setNegotiatedShippingPrice($negotiatedShippingPrice);
            $newOrderNegotiation->setTaxNominalShipping($taxNominalShipping);
            $em->persist($newOrderNegotiation);
            $em->flush();
            
        }


        $newOrder->setShippingPrice($shipping_price);
        $newOrder->setShippingPriceBackup($shipping_price);
        $newOrder->setSendAt($request->request->get('sendAt'));
        $newOrder->setAddress($request->request->get('address'));
        $newOrder->setAddressNote($request->request->get('address_note'));
        $newOrder->setUnitNote($request->request->get('unit_note'));

        $newOrder->setTotal($total);

        $em->persist($newOrder);
        $em->flush();

        $documentApproval = new DocumentApproval();
        $documentApproval->setOrderId($newOrder);
        $documentApproval->setTypeDocument('surat-pengiriman-parsial');
        $documentApproval->setApprovedBy($user);
        $documentApproval->setApprovedAt(new DateTime());
        $documentApproval->setCreatedAt();

        $documentApprovalRepository->add($documentApproval);

        return $this->redirectToRoute('user_ppktreasurer_detail' , ['id' => $newOrder->getId()]);
    }

    function copyEntity($source, $destination, array $exclude = []): void
{
        $reflection = new ReflectionClass($source);

        foreach ($reflection->getProperties() as $property) {
            // Abaikan properti yang ada di daftar exclude
            if (in_array($property->getName(), $exclude)) {
                continue;
            }

            // Set property menjadi accessible
            $property->setAccessible(true);

            // Salin nilai dari objek sumber ke objek tujuan
            $value = $property->getValue($source);
            $property->setValue($destination, $value);
        }
    }

    function addendum_ppk($id) {
        $request = $this->getRequest();
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $userRepository = $this->getRepository(User::class);
        $storeRepository = $this->getRepository(Store::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bpdCcRepository = $this->getRepository(BpdCc::class);
        $parameters = [];
        $this->getDefaultData();
        $user = $this->getUser();
        $order = $repository->getOrderDetail($id, $parameters);
        $reduceOrderByVoucher = [];
        if (!empty($order['o_sharedId'])) {
            $reduceOrderByVoucher = $this->getVoucherListForEachOrder($order['o_sharedId'], $order['s_pkp']);
        }
        $disbursementRepository = $this->getRepository(Disbursement::class);
        $disbursementData = null;
        if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_SELLER') {
            try {
                $disbursementData = $disbursementRepository->findOneBy(['orderId' => $id]);
            } catch (\Throwable $throwable) {
                $disbursementData = null;
            }
        }
        $tokenId = 'user_ppktreasurer_save';
        // dd($order);
        $orderLogRepository = $this->getRepository(OrderChangeLog::class);
        $orderLog = $orderLogRepository->findByOrderId($id);
        $orderDate = $order['o_createdAt']->format('Y-m-d H:i:s');
        if (!empty($order['o_satkerId'])) {
            $satkerData = $this->getRepository(Satker::class)->find($order['o_satkerId']);
            $pendingPaymentBni = $bniRepository->findOneBy([
                'va' => getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$satkerData->getDigitVa(),
                'status' => 'pending'
            ]);
        } else {
            $pendingPaymentBni = null;
        }
        $orderBpd = $repository->find($order['o_id']);
        $haveBpd  = $bpdCcRepository->findOneBy([
            'orders' => $orderBpd,
        ], ['id' => 'DESC']);
        $shippedFiles = [];
        if($order){
            if($order['o_shippedFiles'] && $order['o_shipped_method'] == 'self_courier'){
                foreach ($order['o_shippedFiles'] as $key => $value) {
                    $shippedFiles[] = $value['os_filepath'];
                }
            }
        }
        // get ppk stamp & signature
        $ppkStamp = '';
        $ppkSignature = '';
        if ($order['o_ppkId']){
            $data_ppk = $userRepository->find($order['o_ppkId']);
            $ppkStamp = $data_ppk->getUserStamp();
            $ppkSignature = $data_ppk->getUserSignature();
        }
        // get data bendahara
        $treasurerStamp = '';
        $treasurerSignature = '';
        if ($order['o_treasurerId']){
            $data_treasurer = $userRepository->find($order['o_treasurerId']);
            $treasurerStamp = $data_treasurer->getUserStamp();
            $treasurerSignature = $data_treasurer->getUserSignature();
        }
        // dd('halo');
        // get pp stamp & signature
        $data_pp = $userRepository->find($order['u_id']);
        // get merchant stamp & signature
        $store = $storeRepository->findOneBy(['user' => $order['s_ow_id']]);
        $order_partials = [];
        if(!is_null($order['o_master_id'])) {
            $order_partials = $repository->getPartialOrders($order['o_master_id']);
            foreach ($order_partials as $key => $order_partial) {
                $order_partials[$key]['date_format'] = indonesiaDateFormatAlt($order_partial['o_createdAt']->getTimestamp(), 'l, d F Y'); 
                $order_partials[$key]['o_products'] = $repository->getOrderProducts($order_partial['o_id']);
            }
        }
        
        // dd([
        //     'order' => $order,
        //     'shippedFiles' => $shippedFiles,
        //     'pp_stamp' => $data_pp->getUserStamp(),
        //     'pp_signature' => $data_pp->getUserSignature(),
        //     'ppk_stamp' => $ppkStamp,
        //     'ppk_signature' => $ppkSignature,
        //     'treasurer_stamp' => $treasurerStamp,
        //     'treasurer_signature' => $treasurerSignature,
        //     'seller_stamp' => $store->getUser()->getUserStamp(),
        //     'seller_signature' => $store->getUser()->getUserSignature(),
        //     'user' => $user,
        //     'order_change_log' => $orderLog,
        //     'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
        //     'token_id' => $tokenId,
        //     'reduce_order_by_id' => $reduceOrderByVoucher,
        //     'disbursement_data' => $disbursementData,
        //     'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
        //     'backQRIS' => $request->query->get('back_qris', '0'),
        //     'haveBpd' => $haveBpd,
        //     'order_partials' => $order_partials,
        // ]);
        return $this->view('@__main__/public/user/user_ppk_treasurer/addendum/form_addendum.html.twig', [
            'order' => $order,
            'shippedFiles' => $shippedFiles,
            'pp_stamp' => $data_pp->getUserStamp(),
            'pp_signature' => $data_pp->getUserSignature(),
            'ppk_stamp' => $ppkStamp,
            'ppk_signature' => $ppkSignature,
            'treasurer_stamp' => $treasurerStamp,
            'treasurer_signature' => $treasurerSignature,
            'seller_stamp' => $store->getUser()->getUserStamp(),
            'seller_signature' => $store->getUser()->getUserSignature(),
            'user' => $user,
            'order_change_log' => $orderLog,
            'order_date_day' => indonesiaDateFormatAlt(strtotime($orderDate), 'l, d F Y'),
            'token_id' => $tokenId,
            'reduce_order_by_id' => $reduceOrderByVoucher,
            'disbursement_data' => $disbursementData,
            'order_partials' => $order_partials,
        ]); 
    }
}
