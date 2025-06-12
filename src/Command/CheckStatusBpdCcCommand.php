<?php

namespace App\Command;

use App\Helper\StaticHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use App\Service\BpdSnapService;
use App\Entity\Order;
use App\Entity\BpdCc;
use App\Entity\AccessTokenBpd;
use App\Repository\BpdCcRepository;
use App\Repository\AccessTokenBpdRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;
use Carbon\Carbon;
use DateTimeZone;

class CheckStatusBpdCcCommand extends Command
{
    private $manager;
    private $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-status-bpd-cc')
            ->setDescription('Check status BPD CC')
            ->setHelp('This command allows you to check status bpd cc.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository     = $this->manager->getRepository(Order::class);
        $bpdCcRepository = $this->manager->getRepository(BpdCc::class);
        $accessTokenRepository = $this->manager->getRepository(AccessTokenBpd::class);
        $bpdSnap = new BpdSnapService($this->logger);

        $dataAccess = $accessTokenRepository->findOneBy([], ['id' => 'DESC']);
        $dataExpired = new DateTime($dataAccess->getExpiredDate()->format('Y-m-d H:i:s'), new DateTimeZone('Asia/Makassar'));
        $dataNow = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
        $dataNow = new DateTime($dataNow, new DateTimeZone('Asia/Makassar'));
        $hasToken = false;
        $getToken = null;
        if ($dataExpired > $dataNow) {
            $hasToken = true;
            $getToken = $dataAccess;
        }
        if (!$hasToken) {
            $accessToken = $bpdSnap->accessToken();
            $accessToken = json_decode($accessToken);
            if (isset($accessToken->responseMessage) && $accessToken->responseMessage == "Success") {
                $expiredDate = Carbon::now('Asia/Makassar')->addSeconds($accessToken->expiresIn)->format('Y-m-d H:i:s');
                $covertExpired = new DateTime($expiredDate, new DateTimeZone('Asia/Makassar'));
                $getToken = new AccessTokenBpd();
                $getToken->setToken($accessToken->accessToken);
                $getToken->setExpiredDate($covertExpired);
                $getToken->setCreatedAt();
    
                $this->manager->persist($getToken);
                $this->manager->flush();
            } 
        }
        
        if ($getToken != null) {
            $bpdCredits = $bpdCcRepository->findBy([
                'status' => 'PENDING'
            ],['id' => 'DESC']);

            foreach ($bpdCredits as $key => $value) {
                $request_data = [
                    'partnerReferenceNo' => str_pad($value->getOrders()->getId(), 12, "0", STR_PAD_LEFT),
                    'cpan' => $value->getCpan(),
                    'ott' => $value->getOtt(),
                    'externalId' => gmdate("His").$value->getId(),
                    'approvalCode' => $value->getApprovalCode()
                ];
                $checkStatus = $bpdSnap->checkStatusCpts($getToken->getToken(), $request_data);
                $response = json_decode($checkStatus);
                if (isset($response->responseMessage) && $response->responseMessage == "Success") {
                    if ($response->additionalInfo->trxStatus != "PENDING") {
                        $value->setStatus($response->additionalInfo->trxStatus);
                        $value->setTrxId($response->additionalInfo->trxId);
                        $value->setResponse($checkStatus);
                        $value->setUpdatedAt();
                        $this->manager->persist($value);
                        $this->manager->flush();

                        if ($response->additionalInfo->trxStatus == "SUCCESS") {
                            $order = $value->getOrders();
                            $order->setStatus('paid');

                            $payment = new OrderPayment();
                            $payment->setOrder($order);
                            $payment->setInvoice($order->getInvoice());
                            $payment->setName($order->getName());
                            $payment->setEmail($order->getEmail());
                            $payment->setType('KKI');
                            $payment->setDate(new DateTime('now'));
                            $payment->setAttachment($response->additionalInfo->trxId);
                            $payment->setNominal((int)$value->getAmount());
                            $payment->setMessage('Pembayaran menggunakan KKI');
                            $payment->setBankName('bpd_bali');

                            $em->persist($payment);
                            $em->flush();

                            $this->manager->persist($order);
                            $this->manager->flush();
                        }

                    }
                }
            }
        }

        return 0;
    }
}
