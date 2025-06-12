<?php

namespace App\Plugins;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;

class UserPlugin extends BasePlugin
{
    public function dashboard(string $region, array $parameters = [])
    {
        $templates = [
            'product' => '@__main__/public/product/fragments/dashboard.html.twig',
            'transaction' => '@__main__/public/order/fragments/dashboard.html.twig',
        ];

        $data = $this->getData($region);
        // dd($data);
        $template = $templates[$region] ?? '@__main__/plugins/user/dashboard.html.twig';

        return $this->view($template, $data, 'html');
    }

    private function getData(string $region): array
    {
        if ($region === 'transaction') {
            /** @var User $user */
            $user = $this->getUser();
            $parameters = [
                'limit' => 4,
                'offset' => 0,
                'status' => null,
                'order_by' => 'o.id',
                'sort_by' => 'DESC',
            ];

            if ($this->getUserStore()) {
                $parameters['seller'] = $this->getUserStore();
                $parameters['exclude_status'] = ['pending', 'paid'];
            } else {
                $parameters['buyer'] = $user;
            }

            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            $orderBuilder = $repository->getPaginatedResult($parameters);

            $orders = $orderBuilder->getQuery()->getScalarResult();
            foreach ($orders as &$order) {
                $order['o_products'] = $repository->getOrderProducts($order['o_id']);
                $order['o_negotiatedProducts'] = $repository->getOrderNegotiationProducts($order['o_id']);
            }
            unset($order);

            return [
                'orders' => $orders,
                'parameters' => $parameters,
            ];
        }

        return [];
    }
}
