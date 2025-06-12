<?php

namespace App\Plugins;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

class AdminDevPlugin extends BasePlugin
{
    /**
     * Transaction Fee: 1% * (selling_price - base_price)
     * Verified Merchant Fee: 1% * (total_verified_merchant * 100000)
     *
     * @param array $query
     *
     * @return mixed
     */
    public function feeCalculation(array $query = [])
    {
        $token = $query['t'] ?? null;
        $data = [];
        $statistics = [];

        if ($token === '6da1868b302c8d81b9984f977225fc21') {
            /** @var Connection $db */
            $db = $this->getDoctrine()->getConnection();
            $percentage = 1 / 100;
            $baseQuery = $db
                ->createQueryBuilder()
                ->select('op.*, o.created_at as o_created_at, p.name as p_name, p.status as p_status, p.price as p_price, p.base_price as p_base_price')
                ->from('order_product', 'op')
                ->leftJoin('op', '`order`', 'o', 'op.order_id = o.id')
                ->leftJoin('op', 'product', 'p', 'op.product_id = p.id')
                ->where('o.id <> 0')
                ->orderBy('op.order_id');

            if (isset($query['date']['start']) && !empty($query['date']['start'])) {
                $startDate = date('Y-m-d 00:00:00', strtotime($query['date']['start']));
                $baseQuery->andWhere(sprintf('o.created_at >= %s', $db->quote($startDate)));
            }

            if (isset($query['date']['end']) && !empty($query['date']['end'])) {
                $endDate = date('Y-m-d 23:59:59', strtotime($query['date']['end']));
                $baseQuery->andWhere(sprintf('o.created_at <= %s', $db->quote($endDate)));
            }

            $sqlRegularTransactions = clone $baseQuery;
            $sqlRegularTransactions
                ->andWhere('o.is_b2g_transaction = 0')
                ->andWhere('o.status IN (\'paid\', \'confirmed\', \'processed\', \'shipped\', \'received\')')
            ;

            $sqlB2GTransactions = clone $baseQuery;
            $sqlB2GTransactions
                ->andWhere('o.is_b2g_transaction = 1')
                ->andWhere('o.status = \'paid\'')
            ;

            $sqlVerifiedMerchants = $db
                ->createQueryBuilder()
                ->select('s.id, s.user_id, s.name, s.slug, s.address, s.city, s.city_id, s.district, s.district_id, s.province, s.province_id, s.country, s.country_id, s.is_active, s.is_verified, s.created_at, s.updated_at, u.email as u_email, u.first_name as u_first_name, u.last_name as u_last_name, u.phone_number as u_phone_number')
                ->from('store', 's')
                ->leftJoin('s', 'user', 'u', 's.user_id = u.id')
                ->where('s.id <> 0')
                ->andWhere('s.is_active = 1')
                ->andWhere('s.is_verified = 1')
                //->andWhere('MONTH(s.created_at) = 05')
                //->andWhere('YEAR(s.created_at) = 2020')
                ->orderBy('s.id')
                ->getSql()
            ;

            try {
                $regularTransactionResults = $db->executeQuery($sqlRegularTransactions->getSQL())->fetchAll(FetchMode::ASSOCIATIVE);
                $regularTransactionData = $this->feeCalculationProcess($regularTransactionResults, $percentage);

                $b2gTransactionResults = $db->executeQuery($sqlB2GTransactions->getSQL())->fetchAll(FetchMode::ASSOCIATIVE);
                $b2gTransactionData = $this->feeCalculationProcess($b2gTransactionResults, $percentage);

                $verifiedMerchantResults = $db->executeQuery($sqlVerifiedMerchants)->fetchAll(FetchMode::ASSOCIATIVE);
                $totalVerifiedMerchants = count($verifiedMerchantResults);

                $data['trx_regular'] = $regularTransactionData['data'];
                $data['trx_b2g'] = $b2gTransactionData['data'];
                $data['verified_merchants'] = $verifiedMerchantResults;

                unset($regularTransactionData['data'], $b2gTransactionData['data']);

                $statistics['trx_regular'] = $regularTransactionData;
                $statistics['trx_b2g'] = $b2gTransactionData;
                $statistics['verified_merchants'] = [
                    'total_count' => $totalVerifiedMerchants,
                    'quantity' => $totalVerifiedMerchants,
                    'fee' => $percentage * 100000,
                    'total_fee' => $percentage * ($totalVerifiedMerchants * 100000),
                ];
            } catch (DBALException $e) {
                //
            }
        }

        $trxRegularChartData = $trxB2GChartData = [
            'labels' => [],
            'values' => [],
        ];

        if (isset($data['trx_regular'])) {
            asort($data['trx_regular']);

            $tempData = array_count_values(array_column($data['trx_regular'], 'date_group'));
            $trxRegularChartData = [
                'values' => $tempData,
                'labels' => array_keys($tempData),
            ];
        }

        if (isset($data['trx_b2g'])) {
            asort($data['trx_b2g']);

            $tempData = array_count_values(array_column($data['trx_b2g'], 'date_group'));
            $trxB2GChartData = [
                'values' => $tempData,
                'labels' => array_keys($tempData),
            ];
        }

        $chartLabels = array_merge($trxRegularChartData['labels'], $trxB2GChartData['labels']);
        $chartLabels = array_unique($chartLabels);
        $regularDiff = array_diff($trxB2GChartData['labels'], $trxRegularChartData['labels']);
        $b2gDiff = array_diff($trxRegularChartData['labels'], $trxB2GChartData['labels']);

        if (count($regularDiff) > 0) {
            foreach ($regularDiff as $item) {
                $trxRegularChartData['values'][$item] = 0;
            }
        }

        if (count($b2gDiff) > 0) {
            foreach ($b2gDiff as $item) {
                $trxB2GChartData['values'][$item] = 0;
            }
        }

        asort($chartLabels);
        ksort($trxRegularChartData['values']);
        ksort($trxB2GChartData['values']);

        return $this->view('@__main__/plugins/admin/dev/fee_calculation.html.twig', [
            'data' => $data,
            'statistics' => $statistics,
            'query' => $query,
            'chart' => [
                'labels' => array_values($chartLabels),
                'values' => [
                    'regular' => array_values($trxRegularChartData['values']),
                    'b2g' => array_values($trxB2GChartData['values']),
                ],
            ],
        ], 'html');
    }

    private function feeCalculationProcess(array $results, $percentage): array
    {
        $trxTotal = $trxTotalFee = $trxTotalFeeWithQty = 0;
        $trxCounterIndex = $trxCurrentIndex = 0;
        $data = [];

        foreach ($results as $result) {
            $basePrice = $result['base_price'] > 0 ? $result['base_price'] : $result['p_base_price'];
            $trxFee = $percentage * ($result['price'] - $basePrice);
            $trxFeeWithQty = $trxFee * $result['quantity'];

            $trxTotalFee += $trxFee;
            $trxTotalFeeWithQty += $trxFeeWithQty;

            $result['fee'] = $trxFee;
            $result['fee_x_qty'] = $trxFeeWithQty;

            if ($trxCurrentIndex !== $result['order_id']) {
                $trxCounterIndex = 1;
            } else {
                $trxCounterIndex++;
            }

            if (!array_key_exists($result['order_id'], $data)) {
                $trxCurrentIndex = $result['order_id'];
                $data[$result['order_id']] = [
                    'total' => $trxCounterIndex,
                    'date_group' => date('Y-m', strtotime($result['o_created_at'])),
                    'products' => [$result],
                ];

                $trxTotal++;
            } else {
                $data[$result['order_id']]['total'] = $trxCounterIndex;
                $data[$result['order_id']]['date_group'] = date('Y-m', strtotime($result['o_created_at']));
                $data[$result['order_id']]['products'][] = $result;
            }
        }

        return [
            'total_count' => $trxTotal,
            'fee' => $trxTotalFee,
            'total_fee' => $trxTotalFeeWithQty,
            'data' => $data,
        ];
    }
}
