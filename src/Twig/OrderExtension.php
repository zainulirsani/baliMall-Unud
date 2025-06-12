<?php

namespace App\Twig;

use App\Entity\Chat;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\MasterTax;
use App\Entity\VoucherUsedLog;
use App\Repository\ChatRepository;
use App\Repository\MasterTaxRepository;
use App\Repository\OrderRepository;
use App\Repository\VoucherUsedLogRepository;
use App\Traits\ContainerTrait;
use Hashids\Hashids;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class OrderExtension implements RuntimeExtensionInterface
{
    use ContainerTrait;

    /** @var ContainerInterface $container */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getOrderProducts(int $orderId)
    {
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(OrderProduct::class);

        return $repository->getOrderProducts($orderId);
    }

    public function getOrderChatRoom(int $initiator, int $participant, int $orderId, int $productId)
    {
        /** @var ChatRepository $repository */
        $repository = $this->getRepository(Chat::class);
        $encoder = new Hashids(Chat::class, 16);
        $roomId = $encoder->encode([$initiator, $participant, $orderId, $productId]);
        /** @var Chat $chat */
        $chat = $repository->findOneBy([
            'room' => $roomId,
            'type' => 'complain',
        ]);

        return !empty($chat) ? $roomId : null;
    }

    public function getOrderIdFromInvoice(string $invoice)
    {
        $invoice = getInvoiceNumberFromNotificationContent($invoice);
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(OrderProduct::class);
        $order = $repository->findOneBy(['invoice' => $invoice]);

        return $order ? $order->getId() : 0;
    }

    public function getOrderVouchers(string $sharedId, string $group = 'yes')
    {
        /** @var VoucherUsedLogRepository $repository */
        $repository = $this->getRepository(VoucherUsedLog::class);

        return $repository->getVouchersForOrderBySharedIdGroupByVoucherId($sharedId, $group === 'yes');
    }

    public function getOrderRelated(string $sharedId, int $orderId)
    {
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);

        return $repository->getOrderRelatedBySharedId($sharedId, $orderId);
    }

    public function getOrderStepStatus(string $status, string $role): int
    {
        $step = 9;

        if ($role === 'ROLE_USER_GOVERNMENT') {
            switch ($status) {
                case 'confirmed':
                    $step = 0;
                    break;
                case 'confirm_order_ppk':
                case 'approved_order':
                    $step = 1;
                    break;
                case 'processed':
                    $step = 2;
                    break;
                case 'shipped':
                case 'partial_delivery':
                case 'received':
                    $step = 3;
                    break;
                case 'document':
                    $step = 4;
                    break;
                case 'tax_invoice':
                case 'pending_payment':
                    $step = 5;
                    break;
                case 'payment_process':
                    $step = 6;
                    break;
                case 'paid':
                    $step = 7;
                    break;
            }
        } else {
            switch ($status) {
                case 'payment_process':
                    $step = 0;
                    break;
                case 'paid':
                    $step = 1;
                    break;
                case 'processed':
                    $step = 2;
                    break;
                case 'shipped':
                case 'received':
                    $step = 3;
                    break;
            }
        }

        return $step;
    }

    public function getPPNAtTotalPrice(float $totalPrice, float $ppn = 11): float
    {
        $price = $this->getPriceAtTotalPrice($totalPrice, $ppn);

        return $totalPrice - $price;
    }

    public function generatePPN(int $totalPrice, $umkm_type = '', $ppn = ''): float
    {
        $umkm_type = $umkm_type != '' && $umkm_type != null ? $umkm_type : 'usaha_mikro';
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository(MasterTax::class);
        $data = $repository->findOneBy(['umkm_category' => $umkm_type]);

        try {
            $ppnCategory = $data->getPpn();
        } catch (\Throwable $throwable) {
            $ppnCategory = 11;
        }

        $ppn_percentage = $ppn != '' && $ppn != null ? $ppn : $ppnCategory;

        return round($totalPrice * ((float)$ppn_percentage / 100), 1);
    }

    public function getPpnPercentage($umkm_type = 'usaha_mikro'): float
    {
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository(MasterTax::class);
        $data = $repository->findOneBy(['umkm_category' => $umkm_type]);

        if (!$data instanceof  MasterTax) {
            $data = $repository->findOneBy(['umkm_category' => 'usaha_mikro']);
        }

        return ($data->getPpn() / 100);
    }

    public function generatePPH(int $totalPrice, string $umkm_type = 'usaha_mikro'): float
    {
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository(MasterTax::class);
        $data = $repository->findOneBy(['umkm_category' => $umkm_type]);

        return round($totalPrice * ($data->getPph() / 100), 1);
    }

    public function getPriceAtTotalPrice(float $totalPrice, float $ppn = 11): float
    {
        $ppn = ((float)$ppn) / 100;

        return round($totalPrice / ($ppn + 1), 1);
    }

    public function generateTax(float $totalPrice, int $taxValue): float
    {
        return round($totalPrice * ($taxValue / 100), 1);
    }
}
