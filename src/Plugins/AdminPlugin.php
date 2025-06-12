<?php

namespace App\Plugins;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Repository\NotificationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Session\Session;

class AdminPlugin extends BasePlugin
{
    public function userAddressList(int $userId, string $type = '')
    {
        /** @var User $user */
        $user = $this->getRepository(User::class)->find($userId);
        $data = [
            'type' => $type,
            'addresses' => [],
        ];

        if ($user instanceof User) {
            $data['addresses'] = $this->getRepository(UserAddress::class)->findBy(['user' => $user]);
        }

        return $this->view('@__main__/plugins/admin/user_address_list.html.twig', $data, 'html');
    }

    public function rekapDataTransaksi()
    {
        $parameters = $this->getRequest()->attributes->all();
        $repository = $this->getRepository(Order::class);
        $year       = !empty($parameters['year']) && $parameters['year'] != null ? $parameters['year'] : date('Y');
        $status     = [
            'new_order' => 'label.new_order',
            'confirmed' => 'label.confirmed',
            'processed' => 'label.processed',
            'shipped' => 'label.shipped',
            'received' => 'label.received',
            'pending_payment' => 'label.pending_payment',
            'paid' => 'label.paid',
        ];

        $data = [];
        foreach ($status as $key => $value) {
            $order = $repository->createQueryBuilder('o')
                                ->where('o.status = :status')
                                ->andWhere('YEAR(o.updatedAt) = :year')
                                ->andWhere('o.isB2gTransaction = :b2g')
                                ->setParameter('b2g', true)
                                ->setParameter('year', abs($year))
                                ->setParameter('status', $key)
                                ->getQuery()
                                ->getResult();
            $data[$key] = count($order);
        }
        $result = [
            'data' => $data,
            'status' => $status,
        ];

        return $this->view('@__main__/plugins/admin/dashboard/rekap_transaksi.html.twig', $result, 'html');
    }

    public function merchantDataByStateChart()
    {
        $parameters = $this->getRequest()->attributes->all();

        $adminMerchantProvince = ($parameters['user'])->getAdminMerchantBranchProvince() ?? null;
        $parameters['admin_merchant_province'] = $adminMerchantProvince;

        $key = 'city_by_province_'.$adminMerchantProvince;
        $cache = new FilesystemAdapter();
        $citiesCache = $cache->getItem($key);

        if(!$citiesCache->isHit()) {
            $rawCities = file_get_contents((dirname(__FILE__).'/../../assets/others/cities.json'));
            $cities = json_decode($rawCities, true);

            $filteredCities = [];

            array_filter($cities['results'], function ($city) use ($adminMerchantProvince, &$filteredCities) {
                if ($city['province_id'] == $adminMerchantProvince) {
                    $filteredCities[] = $city['city_name'];
                }

                return false;
            });

            $citiesCache->set($filteredCities);
            $cache->save($citiesCache);
        }

        $regions = $citiesCache->get();
        /** @var StoreRepository $repository */
        $repository = $this->getRepository(Store::class);
        $chartData = $repository->getDataForMerchantByStateChart($parameters);

        $dataTotal = array_column($chartData, 'total');
        $dataCity = array_column($chartData, 'city');

        foreach ($regions as $key => $region) {
            if (!in_array($region, $dataCity, false)) {
                $dataTotal[] = 0;
                $dataCity[] = $region;
            }
        }

        $token = sha1(md5('export_store_by_state'));
        $data = [
            'labels' => json_encode($dataCity),
            'data' => json_encode($dataTotal),
            'token' => $token,
        ];

        $this->saveToSession($token, [
            'labels' => $data['labels'],
            'data' => $data['data'],
            'year' => $parameters['year'] ?? null,
        ]);

        return $this->view('@__main__/plugins/admin/dashboard/merchant_chart.html.twig', $data, 'html');
    }

    public function merchantTransactionDataChart()
    {
        $parameters = $this->getRequest()->attributes->all();
        // $parameters['in_status'] = ['paid', 'confirmed', 'processed', 'shipped', 'received'];

        $adminMerchantProvince = ($parameters['user'])->getAdminMerchantBranchProvince() ?? null;
        $parameters['admin_merchant_province'] = $adminMerchantProvince;

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $results = $repository->getDataForMerchantTransactionChart($parameters);
        $token = sha1(md5('export_merchant_transaction'));
        $result_data = [];
        foreach ($results as $key => $value) {
            $status     = [
                'new_order' => 0,
                'confirmed' => 0,
                'confirm_order_ppk' => 0,
                'approved_order' => 0,
                'processed' => 0,
                'shipped' => 0,
                'received' => 0,
                'pending_approve' => 0,
                'document' => 0,
                'tax_invoice' => 0,
                'pending_payment' => 0,
                'payment_process' => 0,
                'paid' => 0,
                'cancel_request' => 0,
                'cancel' => 0,
            ];
            $result_data['name'][] = $value[0]['s_name'];
            $result_data['total'][] = count($value);
            $total = 0;
            foreach ($value as $k => $v) {
                $total += $v[0]->getTotal() + $v[0]->getShippingPrice();
                $status[$v[0]->getStatus()] += 1; 
            }
            $result_data['nominal'][] = $total;
            $result_data['status'][] = $status;
        }
        $data = [
            'labels' => json_encode($result_data['name']),
            'total' => json_encode($result_data['total']),
            'nominal' => json_encode($result_data['nominal']),
            'jumlah_status' => json_encode($result_data['status']),
            'token' => $token,
            'parameters' => $parameters,
        ];

        $this->saveToSession($token, [
            'labels' => $data['labels'],
            'total' => $data['total'],
            'nominal' => $data['nominal'],
            'status' => $data['jumlah_status'],
            'year' => $parameters['year'] ?? null,
        ]);

        return $this->view('@__main__/plugins/admin/dashboard/merchant_transaction_chart.html.twig', $data, 'html');
    }

    public function productDataByCategoryChart(array $categories = [])
    {
        $parameters = $this->getRequest()->attributes->all();

        $adminMerchantProvince = ($parameters['user'])->getAdminMerchantBranchProvince() ?? null;
        $parameters['admin_merchant_province'] = $adminMerchantProvince;

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        $chartData = $repository->getDataForProductByCategoryChart($parameters);
        $categoryIds = [];
        $nominal = [];

        if (count($chartData) > 10) {
            $others = 0;
            $chartDataTemp = array_column($chartData, 'total');
            array_multisort($chartDataTemp, SORT_DESC, $chartData);

            foreach ($chartData as $key => $item) {
                $categoryIds[] = (int) $item['category_id'];

                /*if ($key < 10) {
                    continue;
                }

                $others += $item['total'];
                unset($chartData[$key]);*/
            }

            if ($others > 0) {
                $chartDataTemp = array_column($chartData, 'category');
                array_multisort($chartDataTemp, SORT_ASC, $chartData);

                $chartData[10] = [
                    'category_id' => 0,
                    'category' => 'Kategori Lainnya',
                    'total' => $others,
                ];
            }
        }

        if (count($categories) > 0) {
            $categoryIds = $categories;
        }

        if (count($categoryIds) > 0) {
            $nominalParameters = [
                'in_status' => ['paid', 'confirmed', 'processed', 'shipped', 'received'],
                'categories' => $categoryIds,
            ];

            if (isset($parameters['year']) && !empty($parameters['year'])) {
                $nominalParameters['year'] = $parameters['year'];
            }

            /** @var OrderRepository $repository */
            $repository = $this->getRepository(Order::class);
            $nominalResults = $repository->getDataForTransactionByCategoryPerMonthChart($nominalParameters);

            foreach ($nominalResults as $nominalResult) {
                if (!array_key_exists($nominalResult['category_id'], $nominal)) {
                    $nominal[$nominalResult['category_id']] = $nominalResult['amount'];
                } else {
                    $nominal[$nominalResult['category_id']] += $nominalResult['amount'];
                }
            }

            foreach ($chartData as $key => &$item) {
                if (array_key_exists($item['category_id'], $nominal)) {
                    $item['amount'] = (int) $nominal[$item['category_id']];
                } else {
                    $item['amount'] = 0;
                }

                if (empty($item['category'])) {
                    unset($chartData[$key]);
                }
            }

            unset($item);
        }

        $tokenData = sha1(md5('export_product_by_category'));
        $tokenNominal = sha1(md5('export_product_nominal_by_category'));
        $data = [
            'labels' => json_encode(array_column($chartData, 'category')),
            'data' => json_encode(array_column($chartData, 'total')),
            'nominal' => json_encode(array_column($chartData, 'amount')),
            'token_data' => $tokenData,
            'token_nominal' => $tokenNominal,
            'parameters' => $parameters,
        ];

        $this->saveToSession($tokenData, [
            'labels' => $data['labels'],
            'data' => $data['data'],
            'year' => $parameters['year'] ?? null,
        ]);

        $this->saveToSession($tokenNominal, [
            'labels' => $data['labels'],
            'data' => $data['nominal'],
            'year' => $parameters['year'] ?? null,
        ]);

        return $this->view('@__main__/plugins/admin/dashboard/product_chart.html.twig', $data, 'html');
    }

    public function transactionDataPerMonthChart()
    {
        $parameters = $this->getRequest()->attributes->all();

        $adminMerchantProvince = ($parameters['user'])->getAdminMerchantBranchProvince() ?? null;

        $parameterRegular = [
            'in_status' => ['paid', 'confirmed', 'processed', 'shipped', 'received'],
            'type' => 'regular',
            'admin_merchant_province' => $adminMerchantProvince,
        ];

        $parameterB2G = [
            'status' => 'paid',
            'type' => 'b2g',
            'admin_merchant_province' => $adminMerchantProvince,
        ];

        if (isset($parameters['year']) && !empty($parameters['year'])) {
            $parameterRegular['year'] = $parameters['year'];
            $parameterB2G['year'] = $parameters['year'];
        }

        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);
        $chartDataRegular = $repository->getDataForTransactionPerMonthChart('total_transaction', $parameterRegular);
        $chartDataB2G = $repository->getDataForTransactionPerMonthChart('total_transaction', $parameterB2G);
        $dataNominalRegular = $repository->getDataForTransactionPerMonthChart('nominal_transaction', $parameterRegular);
        $dataNominalB2G = $repository->getDataForTransactionPerMonthChart('nominal_transaction', $parameterB2G);

        $chartNominalRegular = $chartNominalB2G = [
            'Jan' => '0',
            'Feb' => '0',
            'Mar' => '0',
            'Apr' => '0',
            'May' => '0',
            'Jun' => '0',
            'Jul' => '0',
            'Aug' => '0',
            'Sep' => '0',
            'Oct' => '0',
            'Nov' => '0',
            'Dec' => '0',
        ];

        $chartNominalPieRegular = $chartNominalPieB2G = [];
        $pieColours = [
            'Jan' => '#4dc9f6',
            'Feb' => '#f67019',
            'Mar' => '#f53794',
            'Apr' => '#537bc4',
            'May' => '#acc236',
            'Jun' => '#166a8f',
            'Jul' => '#00a950',
            'Aug' => '#58595b',
            'Sep' => '#8549ba',
            'Oct' => '#aec236',
            'Nov' => '#e670a9',
            'Dec' => '#136a8f',
        ];

        foreach ($dataNominalRegular as $dataReg) {
            foreach ($dataReg as $key => $nominalReg) {
                $index = str_replace('-Count', '', $key);

                if ($dataReg[$index.'-Count'] > 0 && $chartNominalRegular[$index] < 1) {
                    $chartNominalRegular[$index] = (int) $dataReg[$index];
                    break;
                }
            }
        }

        foreach ($dataNominalB2G as $dataB2G) {
            foreach ($dataB2G as $key => $nominalB2G) {
                $index = str_replace('-Count', '', $key);

                if ($dataB2G[$index.'-Count'] > 0 && $chartNominalB2G[$index] < 1) {
                    $chartNominalB2G[$index] = (int) $dataB2G[$index];
                    break;
                }
            }
        }

        foreach ($chartNominalRegular as $item) {
            $chartNominalPieRegular[] = $item;
        }

        foreach ($chartNominalB2G as $item) {
            $chartNominalPieB2G[] = $item;
        }

        $tokenData = sha1(md5('export_transaction_per_month'));
        $tokenNominal = sha1(md5('export_transaction_nominal_per_month'));
        $data = [
            'data_labels' => json_encode(array_keys($chartDataRegular)),
            'data_regular' => json_encode(array_values($chartDataRegular)),
            'data_b2g' => json_encode(array_values($chartDataB2G)),
            'nominal_chart_type' => 'bar', // ['bar', 'pie']
            'nominal_labels' => json_encode(array_keys($chartNominalRegular)),
            'nominal_regular' => json_encode(array_values($chartNominalRegular)),
            'nominal_regular_pie' => json_encode($chartNominalPieRegular),
            'nominal_b2g' => json_encode(array_values($chartNominalB2G)),
            'nominal_b2g_pie' => json_encode($chartNominalPieB2G),
            'token_data' => $tokenData,
            'token_nominal' => $tokenNominal,
            'parameters' => $parameters,
            'pie_colors' => json_encode(array_values($pieColours)),
        ];

        $this->saveToSession($tokenData, [
            'labels' => $data['data_labels'],
            'regular' => $data['data_regular'],
            'b2g' => $data['data_b2g'],
            'year' => $parameters['year'] ?? null,
        ]);

        $this->saveToSession($tokenNominal, [
            'labels' => $data['nominal_labels'],
            'regular' => $data['nominal_regular'],
            'b2g' => $data['nominal_b2g'],
            'year' => $parameters['year'] ?? null,
        ]);

        return $this->view('@__main__/plugins/admin/dashboard/transaction_chart.html.twig', $data, 'html');
    }

    public function adminNotification()
    {
        /** @var NotificationRepository $repository */
        $repository = $this->getRepository(Notification::class);
        $data = $repository->getAdminNotification([
            'read' => 'no',
            'limit' => 10,
            'sort_by' => 'n.id',
            'order_by' => 'DESC',
        ]);

        return $this->view('@__main__/plugins/admin/header_notification.html.twig', $data, 'html');
    }

    public function adminNotificationList()
    {
        /** @var NotificationRepository $repository */
        $repository = $this->getRepository(Notification::class);
        $data = $repository->getAdminNotification([
            'limit' => 100,
            'sort_by' => 'n.id',
            'order_by' => 'DESC',
        ]);

        $now = new DateTime('now');
        $today = $now->format('Y-m-d H:i:s');
        $sql = 'UPDATE App\Entity\Notification t SET t.readAt = \'%s\', t.updatedAt = \'%s\' WHERE t.isAdmin = 1 AND t.readAt IS NULL';

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $query = $em->createQuery(sprintf($sql, $today, $today));
        $query->execute();

        return $this->view('@__main__/plugins/admin/notification/list.html.twig', $data, 'html');
    }

    private function saveToSession($token, $data): void
    {
        /** @var Session $session */
        $session = $this->getSession();
        $session->set($token, $data);
    }
}
