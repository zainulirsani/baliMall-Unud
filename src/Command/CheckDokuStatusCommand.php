<?php

namespace App\Command;

use App\Entity\Doku;
use App\Entity\Notification;
use App\Entity\Order;
use App\Helper\StaticHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDokuStatusCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;

    private $logger;

    private $endpoint;
    private $clientId;
    private $secretKey;
    private $headers;
    private $baseUrl;
    private $requestId;
    private $invoiceNumber;
    private $url;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->logger = $logger;
        $this->baseUrl = getenv('JOKUL_API_URL');
        $this->clientId = getenv('JOKUL_CLIENT_ID');
        $this->secretKey = getenv('JOKUL_SECRET_KEY');
        $this->endpoint = '/orders/v1/status/';

        $this->headers = [
            'Client-Id' => $this->clientId,
            'Content-Type' => 'application/json',
        ];
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-doku-status')
            ->setDescription('Automatic checker for DOKU payment status.')
            ->setHelp('This command allows you to automatically check for the status of DOKU payment.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dokuRepository = $this->manager->getRepository(Doku::class);
        $orderRepository = $this->manager->getRepository(Order::class);

        $dokuData = $dokuRepository->getPendingPayment();

        $this->logger->error(sprintf('Total order %s', count($dokuData)));

        if (count($dokuData) > 0) {
            foreach ($dokuData as $item) {
                if ($item->getId() == 178) {
                    # code...
                
                $this->url = $this->baseUrl.$this->endpoint.$item->getInvoiceNumber();
                $this->invoiceNumber = $item->getInvoiceNumber();
                $this->requestId = $item->getRequestId();

                $result = $this->sendRequest();

                if ($result['status']) {

                    $responseData = $result['data'];
                    $status = $responseData['transaction']['status'];
                    $requestId = $responseData['transaction']['original_request_id'];
                    $invoiceNumber = $responseData['order']['invoice_number'];

                    if ($item->getStatus() !== $status && $invoiceNumber === $item->getInvoiceNumber() && $requestId === $item->getRequestId()) {

                        $item->setStatus($status);
                        $this->manager->persist($item);
                        $this->manager->flush();

                        $orders = $orderRepository->findBy(['dokuInvoiceNumber' => $invoiceNumber]);

                        if ($status === 'SUCCESS') {
                            if (count($orders) > 0) {
                                foreach ($orders as $order) {
                                    $order->setStatus('paid');
                                    $this->manager->persist($order);
                                }

                                $this->manager->flush();
                            }
                        }elseif (($status === 'EXPIRED' || $status === 'FAILED')) {

                            if (count($orders) > 0) {
                                foreach ($orders as $order) {
                                    $order->setDokuInvoiceNumber(null);
                                    $this->manager->persist($order);
                                }

                                $this->manager->flush();
                            }
                        }

                    }
                }
            }
            }
        }

        return 1;

    }

    private function sendRequest(): array
    {
        $result = [
            'data' => [],
            'status' => false
        ];

        $this->generateHeaders();

        $this->logger->error(sprintf('DOKU API result: %s', json_encode($this->headers)));


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseJson = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (is_string($responseJson) && $httpcode == 200) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API result: %s', json_encode($responseJson)));

            $response = json_decode($responseJson, true);

            $result['status'] = true;
            $result['data'] = $response;

        } else {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('DOKU API result: %s', json_encode($responseJson)));
        }

        return $result;
    }

    private function generateHeaders():void
    {
        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $timestamp = substr($dateTime, 0, 19) . "Z";
        $requestTarget = $this->endpoint.$this->invoiceNumber;
        $requestId = $this->requestId.'123';

        $componentSignature = "Client-Id:" . $this->clientId . "\n" .
            "Request-Id:" . $requestId. "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $requestTarget;

        $signature = base64_encode(hash_hmac('sha256', $componentSignature, $this->secretKey, true));

        $this->headers['Request-Id'] = $requestId;
        $this->headers['Request-Timestamp'] = $timestamp;
        $this->headers['Signature'] = 'HMACSHA256=' . $signature;
    }
}
