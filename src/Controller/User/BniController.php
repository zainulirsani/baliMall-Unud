<?php

namespace App\Controller\User;

use App\Controller\PublicController;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\OrderPayment;
use App\Entity\Satker;
use App\Entity\Bni;
use App\Entity\BniDetail;
use App\Repository\BniRepository;

use DateTime;
use DateTimeZone;
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
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Service\BniService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\JsonResponse;

class BniController extends PublicController
{
    // protected $logger;

    // public function __construct(TranslatorInterface $translator, ValidatorInterface $validator, LoggerInterface $logger)
    // {
    //     parent::__construct($translator, $validator);

    //     $this->logger = $logger;
    // }

    public function dashboard()
    {
        $request = $this->getRequest();

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRoles() != "ROLES_USER_GOVERMENT" && $user->getSubRole() != "TREASURER") {
            return $this->redirectToRoute('login');
        }

        $orderRepository = $this->getRepository(Order::class);
        $satkerRepository = $this->getRepository(Satker::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bniDetailRepository = $this->getRepository(BniDetail::class);
        $page = abs($request->query->get('page', '1'));
        $keyword = $request->query->get('search_rid', null);
        $user = $this->getUser();

        $limit = $this->getParameter('result_per_page');
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $parameters = [
            'order_by' => 'bn.createdAt',
            'sort_by' => 'DESC',
            'search_rid' => $keyword,
            'limit' => $limit,
            'offset' => $offset,
            'user' => $user,
            'redirect' => 'user_bnipayment_dashboard'
        ];


        try {
            $adapter = new DoctrineORMAdapter($bniRepository->getPaginatedResult($parameters));
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
        }

        foreach ($documents as $key => $value) {
            $detail = $bniDetailRepository->findBy(['bni_trx_id' => $value['id']]);
            $details = [];
            $value['details'] = [];
            foreach ($detail as $k => $v) {
                $details[] = $orderRepository->find($v->getOrderId());
            }
            // dd($details);
            $documents[$key]['details'] = $details;
        }
        
        $invoicePendingPayment = $orderRepository->findBy([
            'status' => 'pending_payment',
            'treasurerId' => $user->getId()
        ]);
        
        $satker = [];
        foreach ($invoicePendingPayment as $key => $value) {
            if (!empty($value->getSatkerId())) {
                $satkerData = $this->getRepository(Satker::class)->find($value->getSatkerId());
                $satker[] = [
                    'order' => $value,
                    'satker' => $satkerData,
                ];
            }
        }
        // dd($satker);
        $pendingPaymentBni = $bniRepository->findOneBy([
            'user' => $user,
            'status' => 'pending'
        ]);
        
        BreadcrumbService::add(['label' => $this->getTranslation('label.dashboard_va_bni')]);
        return $this->view('@__main__/public/user/bni/dashboard.html.twig',[
            'documents' => $documents,
            'pagination' => $pagination,
            'parameters' => $parameters,
            'html' => $html,
            'orders' => $satker,
            'haveTrx' => $pendingPaymentBni != null ? $pendingPaymentBni : false,
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

    public function vaNotifications()
    {
        $orderRepository = $this->getRepository(Order::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bniDetailRepository = $this->getRepository(BniDetail::class);
        $bniService = $this->get(BniService::class);
        $em = $this->getEntityManager();

        $data = file_get_contents('php://input');
        $result = $bniService->handleCallback($data);

        if ($result['status']) {
            $response['status'] = '000';
            $response['message'] = 'Success!';
            $bni = $bniRepository->findOneBy(['requestId' => $result['data']['trx_id']]);
            if ($bni != null) {
                $paymentDate = new DateTime($result['data']['datetime_payment'], new DateTimeZone('Asia/Makassar'));
                $bni->setPaymentDate($paymentDate);
                $bni->setStatus('done');
                $bni->setResponse(json_encode($result['data']));
                $bni->setUpdatedAt();

                $em->persist($bni);
                $em->flush();

                $bniDetails = $bniDetailRepository->findBy(['bni_trx_id' => $bni->getId()]);
                if ($bniDetails != null) {
                    foreach ($bniDetails as $key => $detail) {
                        $order = $orderRepository->find($detail->getOrderId());
                        $previousOrderValues = clone $order;
                        $order->setStatus('paid');
                        $order->setUpdatedAt();

                        $em->persist($order);
                        $em->flush();

                        $this->setDisbursementProductFee($em, $order);

                        $orderPayment = new OrderPayment();
                        $orderPayment->setOrder($order);
                        $orderPayment->setInvoice($order->getInvoice());
                        $orderPayment->setName($order->getName());
                        $orderPayment->setEmail($order->getEmail());
                        $orderPayment->setType('bni');
                        $orderPayment->setNominal($detail->getAmount());
                        $orderPayment->setMessage('Pembayaran menggunakan VA BNI No Request '.$bni->getRequestId());
                        $orderPayment->setBankName('BNI');
                        $orderPayment->setAttachment('#');

                        $treasurer = $this->getRepository(User::class)->find($order->getTreasurerId());
                        $this->logOrder($em, $previousOrderValues, $order, $treasurer);
                        try {
                            $orderPayment->setDate(new DateTime('now'));
                        } catch (\Exception $e) {
                        }

                        $em->persist($orderPayment);
                        $em->flush();
                    }
                }
            }
        } else {
            $response['status'] = '999';
            $response['message'] = 'Error!';
        }
        return new JsonResponse($response, 200);
    }

    public function bniPaymentMultiple()
    {
        $request = $this->getRequest();
        $orderRepository = $this->getRepository(Order::class);
        $userRepository = $this->getRepository(User::class);
        $bniRepository = $this->getRepository(Bni::class);
        $bniService = $this->get(BniService::class);
        $em = $this->getEntityManager();
        $orders = [];
        $user = $this->getUser();

        foreach ($request->request->get('select_invoices') as $key => $value) {
            $order = $orderRepository->findOneBy([
                'invoice' => $value,
            ]);
            array_push($orders, $order);
        }
        $userPpk = $userRepository->find($orders[0]->getPpkId());
        $satkerData = $this->getRepository(Satker::class)->find($orders[0]->getSatkerId());
        $noVA = getenv('VA_BNI_PREFIX').getenv('VA_BNI_CLIENT_ID').$satkerData->getDigitVa();
        $pendingPaymentBni = $bniRepository->findOneBy([
            'va' => $noVA,
            'status' => 'pending'
        ]);

        if ($pendingPaymentBni == null) {
            
            // Calculate the amount of the order(s)
            $nominal = (int) round($this->getTotalToBePaidForPayment(null, $orders));
            $requestId = $this->generateBniRequestId();
            $digitSatker = $satkerData->getDigitVa();
            $result = $bniService->createVa($nominal, $requestId, $digitSatker, $user);

            if ($result['status']) {
                $data = $result['data'];
                $bni = new Bni();
                $bni->setVa($data['virtual_account']);
                $bni->setAmount($nominal);
                $bni->setStatus('pending');
                $bni->setResponse(json_encode($data));
                $bni->setRequestId($requestId);
                $bni->setType('multiple');
                $bni->setUser($user);

                $expiredAt = new DateTime(date('c', time() + ((24 * 3600) * 3)), new DateTimeZone('Asia/Makassar'));

                $bni->setExpiredTime($expiredAt);
                $bni->setCreatedAt();

                $em->persist($bni);
                $em->flush();

                foreach ($orders as $key => $order) {
                    $products = $order->getOrderProducts();
                    $pphTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPphNominal()) ? $order->getTreasurerPphNominal(): 0): 0;
                    $ppnTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPpnNominal()) ? $order->getTreasurerPpnNominal(): 0): 0;
                    $totalAmount = $order->getTotal() + $order->getShippingPrice() - $pphTreasurer - $ppnTreasurer;

                    $bniDetail = new BniDetail();
                    $bniDetail->setOrderId($order->getId());
                    $bniDetail->setBniTrxId($bni->getId());
                    $bniDetail->setAmount($totalAmount);
                    $bniDetail->setCreatedAt();

                    $em->persist($bniDetail);
                    $em->flush();
                }
                $this->addFlash(
                    'success',
                    'Request pembayaran menggunakan No VA '.$noVA.' berhasil.'
                );
            }
        }else {
            $this->addFlash(
                'warning',
                'Terdapat Request yang belum selesai pada No VA '.$noVA.', Mohon untuk selesaikan pembayaran untuk dapat melakukan request kembali.'
            );
        }
        return $this->redirectToRoute('user_bnipayment_dashboard');

    }

}
