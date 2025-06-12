<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\VirtualAccount;
use App\Repository\OrderRepository;
use App\Repository\VirtualAccountRepository;
use App\Service\WSClientBPD;
use Datetime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CancelOrderCommand extends Command
{
    /** @var ObjectManager $manager */
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
            ->setName('app:cancel-order')
            ->setDescription('Automatic cancellation for order.')
            ->setHelp('This command allows you to cancel an ongoing order that is not yet have a payment.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = new DateTime();
        $parameters = [
            'today' => $date->modify('-1 day'),
        ];

        /** @var OrderRepository $repository */
        $repository = $this->manager->getRepository(Order::class);
        /** @var Order[] $orders */
        $orders = $repository->checkForUnpaidPendingOrder($parameters);

        if (count($orders) > 0) {
            $counter = 0;
            $cancelled = [];

            /** @var VirtualAccountRepository $vaRepository */
            $vaRepository = $this->manager->getRepository(VirtualAccount::class);
            $wsClient = new WSClientBPD();

            foreach ($orders as $order) {
                /** @var OrderProduct[] $orderProducts */
                $orderProducts = $order->getOrderProducts();

                // Increment product quantity?
                foreach ($orderProducts as $orderProduct) {
                    /** @var Product $product */
                    $product = $orderProduct->getProduct();

                    if ($product instanceof Product) {
                        $stock = $product->getQuantity() + $orderProduct->getQuantity();

                        $product->setQuantity($stock < 1 ? 0 : $stock);

                        $this->manager->persist($product);
                    }
                }

                //$note = $order->getNote();
                //$cancellation = 'PEMBATALAN OTOMATIS';

                //if (!empty($note)) {
                //    $note = sprintf('[%s] --- %s', $cancellation, $note);
                //} else {
                //    $note = $cancellation;
                //}

                $order->setStatus('cancel');
                //$order->setNote($note);

                /** @var VirtualAccount $payment */
                $payment = $vaRepository->findOneBy([
                    'invoice' => $order->getSharedInvoice(),
                    'paidStatus' => '0',
                ]);

                if ($payment instanceof VirtualAccount) {
                    try {
                        $response = $wsClient->billRemovalById($payment->getBillNumber());

                        $payment->setPaidStatus('99'); // Number set from our side to mark VA payment as deleted

                        $this->manager->persist($payment);
                        $this->logger->error('Delete VA payment because the order is expired!', $response);
                    } catch (Exception $e) {
                        $this->logger->error(sprintf('VA exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
                    }
                }

                $cancelled[] = $order->getId();

                $this->manager->persist($order);
                $counter++;
            }

            if ($counter > 0) {
                $this->manager->flush();
                $this->logger->error('Automatic order cancellation!', $cancelled);
            }
        }

        return 1;
    }
}
