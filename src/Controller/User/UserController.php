<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Bank;
use App\Entity\OrderPayment;
use App\Entity\UserPpkTreasurer;
use App\Repository\UserPpkTreasurerRepository;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\VoucherUsedLog;
use App\Helper\StaticHelper;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherUsedLogRepository;
use App\Service\BreadcrumbService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use App\Utility\CustomPaginationTemplate;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;

class UserController extends PublicController
{
    public function dashboard()
    {
        // BreadcrumbService::add(['label' => $this->getTranslation('menu.dashboard')]);

        // return $this->view('@__main__/public/user/dashboard.html.twig', [
        //     'region' => 'transaction',
        // ]);

        $request = $this->getRequest();

        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(Order::class);
        $storeRepository = $this->getRepository(Store::class);
        $page = abs($request->query->get('page', '1'));
        $keyword = $request->query->get('search_invoice', null);
        // $status_search = $request->query->get('filter_status', null);
        $filter_status_order = $request->query->get('filter_status_order', null);

        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'order_by' => 'o.createdAt',
            'sort_by' => 'DESC',
            'search_invoice' => $keyword,
            // 'filter_status' => $status_search,
            'filter_status_order' => $filter_status_order,
        ];

        // if ($user->getSubRole() == 'PPK') {
        //     $parameters['ppk_user'] = $user->getId();
        //     $parameters['ppk_user_collec'] = $user;
        //     // $parameters['status_multiple'] = ['approve_order_ppk','processed','shipped','pending_approve','received','document','tax_invoice','pending_payment','payment_process','paid'];
        // } else {
        //     $parameters['treasurer_user'] = $user->getId();
        //     $parameters['status_multiple'] = ['tax_invoice','pending_payment','payment_process','paid'];
        // }
        $user = $this->getUser();
        $userStore = $storeRepository->findOneBy(['user'=>$user]);
        if ($userStore != null) {
            $parameters['seller'] = $userStore;
        } else {
            $parameters['buyer'] = $user;
        }

        $parameters2 = $parameters;


        if (!empty($keyword)) {
            $parameters['key_invoice'] = $keyword;
        }

        // if ($user->getSubRole() == 'PPK') {
        //     if (!empty($status_search)) {
        //         $parameters['filter_status_ppk'] = $status_search;
        //     }
        // } else {
        //     if (!empty($status_search)) {
        //         $parameters['filter_status_treasurer'] = $status_search;
        //     }
        // }

        if (!empty($filter_status_order)) {
            $parameters['status'] = $filter_status_order;
        }
        
        $parameters['limit'] = $limit;
        $parameters['offset'] = $offset;
        $parameters['redirect'] = 'user_dashboard';
        
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

        BreadcrumbService::add(['label' => $this->getTranslation('menu.dashboard')]);
        
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
            'processed' => 0,
            'confirm_order_ppk' => 0,
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

        // dd($status_count);

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

    public function pemesanan()
    {
        // BreadcrumbService::add(['label' => $this->getTranslation('menu.dashboard')]);

        // return $this->view('@__main__/public/user/dashboard.html.twig', [
        //     'region' => 'transaction',
        // ]);

        $request = $this->getRequest();

        /** @var User $user */
        $user = $this->getUser();
        /** @var UserPpkTreasurerRepository $repository */
        $repository = $this->getRepository(Order::class);
        $storeRepository = $this->getRepository(Store::class);
        $page = abs($request->query->get('page', '1'));
        $keyword = $request->query->get('search_invoice', null);
        // $status_search = $request->query->get('filter_status', null);
        $filter_status_order = $request->query->get('filter_status_order', null);

        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'order_by' => 'o.createdAt',
            'sort_by' => 'DESC',
            'search_invoice' => $keyword,
            'type_order' => 'master',
            // 'filter_status' => $status_search,
            'filter_status_order' => $filter_status_order,
        ];

        if ($user->getSubRole() == 'PPK') {
            $parameters['ppk_user'] = $user->getId();
            $parameters['ppk_user_collec'] = $user;

            if (!empty($status_search)) {
                $parameters['filter_status_ppk'] = $status_search;
            }
            // $parameters['status_multiple'] = ['approve_order_ppk','processed','shipped','pending_approve','received','document','tax_invoice','pending_payment','payment_process','paid'];
        } else {
            $user = $this->getUser();
            $userStore = $storeRepository->findOneBy(['user'=>$user]);
            if ($userStore != null) {
                $parameters['seller'] = $userStore;
            } else {
                $parameters['buyer'] = $user;
            }
        }

        // else {
        //     $parameters['treasurer_user'] = $user->getId();
        //     $parameters['status_multiple'] = ['tax_invoice','pending_payment','payment_process','paid'];
        // }

        $parameters2 = $parameters;


        if (!empty($keyword)) {
            $parameters['key_invoice'] = $keyword;
        }

        // if ($user->getSubRole() == 'PPK') {
        //     if (!empty($status_search)) {
        //         $parameters['filter_status_ppk'] = $status_search;
        //     }
        // } else {
        //     if (!empty($status_search)) {
        //         $parameters['filter_status_treasurer'] = $status_search;
        //     }
        // }

        if (!empty($filter_status_order)) {
            $parameters['status'] = $filter_status_order;
        }
        
        $parameters['limit'] = $limit;
        $parameters['offset'] = $offset;
        $parameters['redirect'] = 'user_pemesanan';
        
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

        BreadcrumbService::add(['label' => $this->getTranslation('menu.dashboard')]);
        
        foreach ($documents as $key => $value) {
            $documents[$key][0]['o_products'] = $repository->getOrderProducts($value[0]['id']);
            $documents[$key][0]['o_complaint'] = $repository->getOrderComplaint($value[0]['id']);
            $documents[$key][0]['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($value[0]['id']);
            $documents[$key][0]['o_shippedFiles'] = $repository->getOrderShippedFiles($value[0]['id']);
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

        // dd($status_count);

        return $this->view('@__main__/public/user/user_ppk_treasurer/pemesanan.html.twig', [
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


    private function routeGenerator(array $parameters = []): callable
    {
        return function ($page) use ($parameters) {
            
            $query = $parameters;
            $query['page'] = $page;

            return $this->get('router')->generate($parameters['redirect'], $query);
        };
    }

    public function profile(UserPasswordEncoderInterface $encoder)
    {
        $request = $this->getRequest();
        $profile = $this->getUserProfile();
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'update_profile';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $name = StaticHelper::splitFullName($formData['full_name']);
            $publicDir = $this->getParameter('public_dir_path');
            $translator = $this->getTranslator();
            $deleted = [];

            /** @var UserRepository $repository */
            $repository = $this->getRepository(User::class);
            /** @var User $user */
            $user = $repository->find((int) $profile['u_id']);
            // $user->setUsername($user->getEmailCanonical());
            $user->setFirstName($name['first_name']);
            $user->setLastName($name['last_name']);
            $user->setDescription($formData['description']);
            $user->setGender($formData['gender']);
            $user->setPhoneNumber($formData['phone']);
            $user->setTnc('checked');

            if (!empty($formData['dob'])) {
                try {
                    $user->setDob(new DateTime($formData['dob']));
                } catch (Exception $e) {
                }
            }

            if (!empty($formData['photo_profile_temp'])) {
                $photoProfile = filter_var($formData['photo_profile_temp'], FILTER_SANITIZE_STRING);
                $photoProfile = ltrim($photoProfile, '/');

                $formData['u_photoProfile'] = '';
                $formData['photo_profile_src'] = $photoProfile;

                if (!empty($formData['photo_profile'])
                    && $formData['photo_profile'] !== $photoProfile
                    && strpos($formData['photo_profile'], 'dist/img/') === false) {
                    $deleted[] = $publicDir.'/'.$formData['photo_profile'];
                }

                $user->setPhotoProfile($photoProfile);
            } else {
                if (!empty($formData['photo_profile']) && strpos($formData['photo_profile'], 'dist/img/') === false) {
                    $deleted[] = $publicDir.'/'.$formData['photo_profile'];
                }

                $user->setPhotoProfile(null);
            }

            if ($user->getRole() != 'ROLE_USER_SELLER') {
                if (!empty($formData['user_signature_temp'])) {
                    $userSignature = filter_var($formData['user_signature_temp'], FILTER_SANITIZE_STRING);
                    $userSignature = ltrim($userSignature, '/');
    
                    $formData['u_user_signature'] = '';
                    $formData['user_signature_src'] = $userSignature;
    
                    if (!empty($formData['user_signature'])
                        && $formData['user_signature'] !== $userSignature
                        && strpos($formData['user_signature'], 'dist/img/') === false) {
                        $deleted[] = $publicDir.'/'.$formData['user_signature'];
                    }
    
                    $user->setUserSignature($userSignature);
                } else {
                    if (!empty($formData['user_signature']) && strpos($formData['user_signature'], 'dist/img/') === false) {
                        $deleted[] = $publicDir.'/'.$formData['user_signature'];
                    }
    
                    $user->setUserSignature(null);
                }
    
                if (!empty($formData['user_stamp_temp'])) {
                    $userstamp = filter_var($formData['user_stamp_temp'], FILTER_SANITIZE_STRING);
                    $userstamp = ltrim($userstamp, '/');
    
                    $formData['u_user_stamp'] = '';
                    $formData['user_stamp_src'] = $userstamp;
    
                    if (!empty($formData['user_stamp'])
                        && $formData['user_stamp'] !== $userstamp
                        && strpos($formData['user_stamp'], 'dist/img/') === false) {
                        $deleted[] = $publicDir.'/'.$formData['user_stamp'];
                    }
    
                    $user->setUserStamp($userstamp);
                } else {
                    if (!empty($formData['user_stamp']) && strpos($formData['user_stamp'], 'dist/img/') === false) {
                        $deleted[] = $publicDir.'/'.$formData['user_stamp'];
                    }
    
                    $user->setUserStamp(null);
                }
            }

            

            if (!empty($formData['banner_profile_temp'])) {
                $bannerProfile = filter_var($formData['banner_profile_temp'], FILTER_SANITIZE_STRING);
                $bannerProfile = ltrim($bannerProfile, '/');

                $formData['u_bannerProfile'] = '';
                $formData['banner_profile_src'] = $bannerProfile;

                if (!empty($formData['banner_profile'])
                    && $formData['banner_profile'] !== $bannerProfile
                    && strpos($formData['banner_profile'], 'dist/img/') === false) {
                    $deleted[] = $publicDir.'/'.$formData['banner_profile'];
                }

                $user->setBannerProfile($bannerProfile);
            } else {
                if (!empty($formData['banner_profile'])) {
                    $deleted[] = $publicDir.'/'.$formData['banner_profile'];
                }

                $user->setBannerProfile(null);
            }

            $flashBag->set('form_data', $formData);

            $validator = $this->getValidator();

            if ($user->getRole() === 'ROLE_USER_GOVERNMENT') {
                $user->setNip(filter_var($formData['nip'], FILTER_SANITIZE_STRING));
                $user->setPpName(filter_var($formData['pp_name'], FILTER_SANITIZE_STRING));
                $user->setPpkName(filter_var($formData['ppk_name'], FILTER_SANITIZE_STRING));

                $userErrors = $validator->validate($user);
            } elseif ($user->getRole() === 'ROLE_USER_BUSINESS') {
                $user->setNik(filter_var($formData['nik'], FILTER_SANITIZE_STRING));
                $user->setCompanyPhone(filter_var($formData['company_phone'], FILTER_SANITIZE_STRING));
                $user->setCompanyRole(filter_var($formData['company_role'], FILTER_SANITIZE_STRING));

                $userErrors = $validator->validate($user, null, ['Default', 'b2b']);
            } else {
                $userErrors = $validator->validate($user);
            }

            if (!empty($formData['password']) && $formData['password'] !== $formData['confirm_password']) {
                $message = $translator->trans('user.password_not_match', [], 'validators');
                $constraint = new ConstraintViolation($message, $message, [], $user, 'confirm_password', '', null, null, new Assert\EqualTo('password'), null);

                $userErrors->add($constraint);
            }

            if (count($userErrors) === 0) {
                if (!empty($formData['password']) && $formData['password'] === $formData['confirm_password']) {
                    $password = $encoder->encodePassword($user, $formData['password']);
                    $user->setPassword($password);
                }

                $em = $this->getEntityManager();
                $em->persist($user);
                $em->flush();

                foreach ($deleted as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                $this->addFlash(
                    'success',
                    $translator->trans('message.success.profile_updated')
                );
            } else {
                $errors = [];

                foreach ($userErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag->set('errors', $errors);
            }

            return $this->redirectToRoute('user_profile');
        }

        if (!empty($profile['u_dob'])) {
            $profile['u_dob'] = $profile['u_dob']->format('Y-m-d');
        }

        $profile['u_companyName'] = null;

        if ($profile['u_role'] === 'ROLE_USER_BUSINESS' && isset($profile['main_address']['title'])) {
            $profile['u_companyName'] = $profile['main_address']['title'];
        }

        BreadcrumbService::add(['label' => $this->getTranslation('menu.profile_edit')]);

        return $this->view('@__main__/public/user/profile.html.twig', [
            'form_data' => array_merge($profile, $flashBag->get('form_data')),
            'errors' => $flashBag->get('errors'),
            'token_id' => $tokenId,
            'date_picker' => true,
        ]);
    }

    public function paymentConfirmation()
    {
        $request = $this->getRequest();
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $tokenId = 'user_payment_confirmation';
        $route   = $this->redirectToRoute('user_payment_confirmation');
        /** @var User $user */
        $user = $this->getUser();
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);
        $bank = $this->getRepository(Bank::class)->findBy(['is_active' => true]);
        $message = 'message.success.payment_verification';

//        if ($user && $user->getLkppRole() !== 'BENDAHARA') {
//            return $this->redirectToRoute('user_dashboard');
//        }

        if ($request->isMethod('POST')) {
            $translator = $this->getTranslator();
            $formData = $request->request->all();

            /** @var Order[] $orders */
            $orders = $orderRepository->findBy(['sharedId' => $formData['invoice']]);

            if (count($orders) < 1) {
                $this->addFlash(
                    'success',
                    $translator->trans('message.error.order_not_found', ['%invoice%' => $formData['invoice']])
                );

                return $route;
            }

            /** @var OrderPaymentRepository $paymentRepository */
            $paymentRepository = $this->getRepository(OrderPayment::class);
            $existing = 0;

            foreach ($orders as $order) {
                /** @var OrderPayment $orderPayment */
                $orderPayment = $paymentRepository->findOneBy(['order' => $order]);

                if ($orderPayment instanceof OrderPayment) {
                    $existing++;
                }
            }

            if ($existing > 0) {
                $this->addFlash(
                    'success',
                    $translator->trans('message.error.order_payment_exist', ['%invoice%' => $formData['invoice']])
                );

                return $this->redirectToRoute($route);
            }

            if (!empty($formData['date'])) {
                $formData['date'] = strtotime($formData['date']);
            }

            $orderPayment = new OrderPayment();
            //$orderPayment->setOrder($order);
            $orderPayment->setInvoice(filter_var($formData['invoice'], FILTER_SANITIZE_STRING));
            $orderPayment->setName(trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName())));
            $orderPayment->setEmail($user->getEmailCanonical());
            $orderPayment->setDate(filter_var($formData['date'], FILTER_SANITIZE_STRING));
            $orderPayment->setAttachment(ltrim($formData['attachment'], '/'));
            $orderPayment->setNominal((float) str_replace('.', '', $formData['nominal']));
            $orderPayment->setMessage(filter_var($formData['message'], FILTER_SANITIZE_STRING));
            $orderPayment->setBankName(filter_var($formData['bank_name'], FILTER_SANITIZE_STRING));
            $orderPayment->setType(filter_var($formData['bank_method'], FILTER_SANITIZE_STRING));
            //$orderPayment->setBankAccountName(filter_var($formData['bank_account_name'], FILTER_SANITIZE_STRING));
            //$orderPayment->setBankAccountNumber(filter_var($formData['bank_account_number'], FILTER_SANITIZE_STRING));

            $validator = $this->getValidator();
            $orderPaymentErrors = $validator->validate($orderPayment);

            if (count($orderPaymentErrors) === 0) {
                try {
                    $formData['date'] = date('Y-m-d', $formData['date']);

                    $orderPayment->setDate(new DateTime($formData['date']));
                } catch (Exception $e) {
                }

                $em = $this->getEntityManager();
                $tempInvoices = [];
                /** @var BaseMail $baseMail */
                $baseMail = $this->get(BaseMail::class);

                foreach ($orders as $order) {
                    $payment = clone $orderPayment;
                    $payment->setOrder($order);
                    $payment->setInvoice($order->getInvoice());
                    $payment->setNominal($order->getTotal() + $order->getShippingPrice());

                    $previousOrderValues = clone $order;

                    $order->setStatus('payment_process');

                    /** @var Store $store */
                    $store = $order->getSeller();
                    /** @var User $seller */
                    $seller = $store->getUser();
                    /** @var User $buyer */
                    $buyer = $order->getBuyer();

                    $notification = new Notification();
                    $notification->setSellerId($seller->getId());
                    $notification->setBuyerId($buyer->getId());
                    $notification->setIsSentToSeller(false);
                    $notification->setIsSentToBuyer(false);
                    $notification->setTitle($this->getTranslation('notifications.order_status'));
                    $notification->setContent($this->getTranslation('notifications.order_status_text', ['%invoice%' => $order->getInvoice(), '%status%' => 'payment_process']));

                    $em->persist($payment);
                    $em->persist($order);
                    $em->persist($notification);
                    $em->flush();

                    $this->logOrder($em, $previousOrderValues, $order, $user);

                    $tempInvoices[] = $order->getInvoice();
                    $grand_total = $order->getTotal() + $order->getShippingPrice();
                    if ($order->getIsB2gTransaction() && $order->getStatus() === 'payment_process' && $order->getPpkPaymentMethod() == 'pembayaran_langsung') {
                        $this->setDisbursementProductFee($em, $order);
                        $message = 'message.success.please_upload_withholding_tax';
                    }
                    $route   = $this->redirectToRoute('user_ppktreasurer_detail', ['id' => $order->getId()]);

//                    --- Send email notification to seller
                    /** @var Store $store */
                    $store = $order->getSeller();
                    /** @var User $owner */
                    $owner = $store->getUser();
                    // $mailToSeller = clone $baseMail;
                    // $mailToSeller->setMailSubject($translator->trans('message.info.new_user_payment'));
                    // $mailToSeller->setMailTemplate('@__main__/email/user_payment_confirmation.html.twig');
                    // $mailToSeller->setMailRecipient($owner->getEmailCanonical());
                    // $mailToSeller->setMailData([
                    //     'name' => $owner->getFirstName(),
                    //     'invoice' => $order->getInvoice(),
                    //     'link' => $this->generateUrl('user_order_detail', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    // ]);
                    // $mailToSeller->send();
//                    --- Send email notification to seller
                }

                //--- Send email notification to buyer
                $mailToBuyer = clone $baseMail;
                $mailToBuyer->setMailSubject($translator->trans('message.info.new_user_payment'));
                $mailToBuyer->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
                $mailToBuyer->setMailRecipient($user->getEmailCanonical());
                $mailToBuyer->setMailData([
                    'name' => $user->getFirstName(),
                    'invoices' => $tempInvoices,
                    'recipient_type' => 'buyer',
                    'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);
                $mailToBuyer->send();
                //--- Send email notification to buyer

                //--- Send email notification to admin
                $mailToAdmin = clone $baseMail;
                $mailToAdmin->setMailSubject($translator->trans('message.info.new_user_payment'));
                $mailToAdmin->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
                $mailToAdmin->setToAdmin();
                $mailToAdmin->setMailData([
                    'name' => 'Admin',
                    'invoices' => $tempInvoices,
                    'recipient_type' => 'admin',
                    'link' => $this->generateUrl('admin_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);
                $mailToAdmin->send();
                //--- Send email notification to admin


                $this->addFlash(
                    'success',
                    $translator->trans($message)
                );

            } else {
                $errors = [];

                // Add error messages into its own array
                foreach ($orderPaymentErrors as $error) {
                    $errors[$error->getPropertyPath()] = $error->getMessage();
                }

                if (!empty($formData['date'])) {
                    $formData['date'] = date('Y-m-d', $formData['date']);
                }

                $flashBag->set('errors', $errors);
                $flashBag->set('form_data', $formData);
            }

            return $route;
        }

        $maxLength = 100;
        /** @var VoucherUsedLogRepository $voucherRepository */
        $voucherRepository = $this->getRepository(VoucherUsedLog::class);
        $invoiceQuery = $request->query->get('invoice', null);
        $accessQuery = $request->query->get('access', null);
        $pageTitle = 'title.page.payment_confirmation';
        $nominal = [];
        $orderLists = [];
        $voucherLists = [];
        
        $invoices = $orderRepository->getInvoiceListForBuyer($user, $invoiceQuery);
        foreach ($invoices as $invoice) {
            $treasurer_pph = !empty($invoice['treasurerPphNominal']) ? $invoice['treasurerPphNominal'] : 0;
            $treasurer_ppn = !empty($invoice['treasurer_ppn_nominal']) ? $invoice['treasurer_ppn_nominal'] : 0;

            if (!array_key_exists($invoice['sharedId'], $nominal)) {
                $nominal[$invoice['sharedId']] = ($invoice['total'] - $treasurer_pph - $treasurer_ppn);
            } else {
                $nominal[$invoice['sharedId']] += ($invoice['total'] - $treasurer_pph - $treasurer_ppn);
            }

            //Ini Menyebabkan order negosiasi ditambah lagiu dengan PPN pdhl nilai total sudah include PPN
                if ($products = $orderRepository->getOrderProducts($invoice['id'])) {
                    foreach ($products as $product) {
                        if ( $product['op_withTax'] === true && $product['o_isB2gTransaction'] === '0' ) {
                            $nominal[$invoice['sharedId']] += (float) $product['op_taxNominal'];
                        }
                    }
                }

            if ($vouchers = $voucherRepository->getVouchersForPaymentConfirmationBySharedId($invoice['sharedId'], false)) {

                foreach ($vouchers as $voucher) {
                    if (!in_array($voucher['vul_orderId'], $orderLists, false)) {
                        $orderLists[] = $voucher['vul_orderId'];
                    }

                    if (!in_array($voucher['v_code'], $voucherLists, false)) {
                        $voucherLists[] = $voucher['v_code'];
                        $nominal[$invoice['sharedId']] -= (float) $voucher['vul_voucherAmount'];
                    }
                }

            }
        }


        foreach ($nominal as $key => $item) {
            $nominal[$key] = StaticHelper::formatForCurrency($item);

            if ((float) $item <= 0) {
                unset($nominal[$key]);
            }
        }
        

        BreadcrumbService::add(['label' => $this->getTranslation($pageTitle)]);

        return $this->view('@__main__/public/user/payment_confirmation.html.twig', [
            'page_title' => $pageTitle,
            'token_id' => $tokenId,
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'max_length' => $maxLength,
            'invoices' => $invoices,
            'banks' => $bank,
            'invoice_query' => $invoiceQuery,
            'access_query' => $accessQuery,
            'nominal' => $nominal,
            'date_picker' => true,
        ]);
    }

    public function notification()
    {
        $this->deniedAccess('ROLE_USER_SELLER');

        $pageTitle = 'title.page.notification';
        $notifications = $this->getUserNotification(['read' => 'all']);
        $unread = 0;

        foreach ($notifications['data'] as $notification) {
            if (empty($notification['n_readAt'])) {
                $unread++;
                break;
            }
        }

        if ($unread > 0) {
            $sql = 'UPDATE App\Entity\Notification t SET t.readAt = \'%s\' WHERE t.readAt IS NULL';
            $now = new DateTime('now');

            /** @var EntityManager $em */
            $em = $this->getEntityManager();
            $query = $em->createQuery(sprintf($sql, $now->format('Y-m-d H:i:s')));
            $query->execute();
        }

        BreadcrumbService::add(['label' => $this->getTranslation($pageTitle)]);

        return $this->view('@__main__/public/user/notification.html.twig', [
            'page_title' => $pageTitle,
            'notifications' => $notifications,
        ]);
    }
}
