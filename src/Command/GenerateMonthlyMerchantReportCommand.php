<?php

namespace App\Command;

use App\Entity\MailQueue;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\MailQueueRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Service\AppTwigService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Dompdf\Dompdf;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateMonthlyMerchantReportCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;

    /** @var AppTwigService $twig */
    protected $twig;

    public function __construct(Registry $registry, AppTwigService $twig)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:generate-monthly-merchant-report')
            ->addOption('merchant-id', null, InputOption::VALUE_OPTIONAL, 'Selected merchant id')
            ->setDescription('Generates monthly merchant report.')
            ->setHelp('This command allows you to generate monthly merchant report to be sent via email.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pdfPath = __DIR__.'/../../var/pdf/report';
        $batch = date('m-Y', strtotime('last day of previous month'));
        $pdfName = sprintf('report-%s.pdf', $batch);
        $merchantId = abs($input->getOption('merchant-id'));
        $parameters = ['isActive' => true];

        if ($merchantId > 0) {
            $parameters['id'] = $merchantId;
        }

        /** @var StoreRepository $storeRepository */
        $storeRepository = $this->manager->getRepository(Store::class);
        /** @var ProductRepository $productRepository */
        $productRepository = $this->manager->getRepository(Product::class);
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->manager->getRepository(Order::class);
        /** @var MailQueueRepository $mailQueueRepository */
        $mailQueueRepository = $this->manager->getRepository(MailQueue::class);
        /** @var Store[] $merchants */
        $merchants = $storeRepository->findBy($parameters);
        $processed = 0;

        foreach ($merchants as $merchant) {
            /** @var MailQueue $exist */
            $exist = $mailQueueRepository->findOneBy([
                'batch' => $batch,
                'entityId' => $merchant->getId(),
                'entityName' => Store::class,
            ]);

            if (!empty($exist)) {
                continue;
            }

            $pdfFile = sprintf('%s/%s/%s', $pdfPath, $merchant->getId(), $pdfName);
            $lastMonth = date('M', strtotime('last day of previous month'));
            $totalRating = $totalTransactions = 0;
            $review = $productRepository->getTotalProductReviewForStore($merchant);

            $products = $productRepository->getProductsCreatedByStore($merchant->getId(), [
                'start_date' => date('Y-m-d 00:00:00', strtotime('first day of previous month')),
                'end_date' => date('Y-m-d 23:59:59', strtotime('last day of previous month')),
            ]);

            $trxDataRegular = $orderRepository->getDataForTransactionPerMonthChart('total_transaction', [
                'in_status' => ['paid', 'confirmed', 'processed', 'shipped', 'received'],
                'type' => 'regular',
                'store' => $merchant->getId(),
            ]);

            $trxDataB2G = $orderRepository->getDataForTransactionPerMonthChart('total_transaction', [
                'status' => 'paid',
                'type' => 'b2g',
                'store' => $merchant->getId(),
            ]);

            if ((int) $review['total_rating'] > 0 && (int) $review['total_review'] > 0) {
                $totalRating = floor($review['total_rating'] / $review['total_review']);
            }

            if (isset($trxDataRegular[$lastMonth])) {
                $totalTransactions += $trxDataRegular[$lastMonth];
            }

            if (isset($trxDataB2G[$lastMonth])) {
                $totalTransactions += $trxDataB2G[$lastMonth];
            }

            $data = [
                'merchant_name' => $merchant->getName(),
                'new_products' => $products['total_new_products'],
                'total_rating' => $totalRating,
                'total_transactions' => $totalTransactions,
                'batch' => date('F Y', strtotime('last day of previous month')),
            ];

            $this->generatePdf($this->renderView('@__main__/public/store/print/monthly_report.html.twig', $data), $pdfFile);

            /** @var User $owner */
            $owner = $merchant->getUser();

            if ($owner instanceof User) {
                $mailData = [
                    'name' => trim($owner->getFirstName().' '.$owner->getLastName()),
                ];
                $payload = [
                    'to' => $owner->getEmailCanonical(),
                    'from' => getenv('MAIL_SENDER'),
                    'subject' => 'Laporan Bulanan Merchant BaliMall',
                    'body' => $this->renderView('@__main__/email/merchant_monthly_report.html.twig', $mailData),
                    'content_type' => 'text/html',
                    'attachment' => $pdfFile,
                ];

                $queue = new MailQueue();
                $queue->setConnection('default');
                $queue->setBatch($batch);
                $queue->setEntityId($merchant->getId());
                $queue->setEntityName(Store::class);
                $queue->setPayload($payload);

                $this->manager->persist($queue);
                $processed++;
            }
        }

        if ($processed > 0) {
            $this->manager->flush();
        }

        return 1;
    }

    private function generatePdf(string $content, string $path): void
    {
        $pdf = new Dompdf();
        $pdf->loadHtml($content);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $fs = new Filesystem();
        $fs->appendToFile($path, $pdf->output());
    }

    private function renderView(string $template, array $data = [])
    {
        try {
            return $this->twig->render($template, $data);
        } catch (Exception $e) {
        }

        return null;
    }
}
