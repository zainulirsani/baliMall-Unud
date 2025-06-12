<?php

namespace App\Traits;

use App\Entity\Doku;
use App\Entity\Bni;
use App\Entity\Midtrans;
use App\Entity\Order;
use App\Entity\OrderNegotiation;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\Disbursement;
use App\Entity\VoucherUsedLog;
use App\Entity\MasterTax;
use App\EventListener\OrderChangeListener;
use App\EventListener\OrderDisbursementListener;
use App\Helper\StaticHelper;
use App\Repository\OrderRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\StoreRepository;
use App\Repository\VoucherUsedLogRepository;
use App\Repository\MasterTaxRepository;
use App\Service\HttpClientService;
use App\Service\RajaOngkirService;
use Dompdf\Dompdf;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait AppTrait
{
    public function getCountryList(string $lang = 'en', bool $rebuild = false)
    {
        /** @var FilesystemAdapter $cache */
        $cache = $this->getCache();
        $lang = strtolower($lang);
        $cacheName = 'country_list_' . $lang;

        if ($rebuild) {
            try {
                $cache->deleteItem($cacheName);
            } catch (InvalidArgumentException $e) {
            }
        }

        $data = include __DIR__ . '/../Resources/data/administrative/country.en.php';

        try {
            $countries = $cache->getItem($cacheName);

            if (!$countries->isHit()) {
                $file = __DIR__ . '/../Resources/data/administrative/country.' . $lang . '.php';

                if (is_file($file)) {
                    $data = include $file;
                }

                $countries->set($data);
                $cache->save($countries);
            }

            return $countries->get();
        } catch (InvalidArgumentException $e) {
        }

        return $data;
    }

    public function getProvinceList(string $country = 'all', string $lang = 'en', bool $rebuild = false)
    {
        /** @var FilesystemAdapter $cache */
        $cache = $this->getCache();
        $country = strtoupper($country);
        $lang = strtolower($lang);
        $cacheName = 'province_list_' . $lang;

        if ($rebuild) {
            try {
                $cache->deleteItem($cacheName);
            } catch (InvalidArgumentException $e) {
            }
        }

        $data = include __DIR__ . '/../Resources/data/administrative/province.en.php';

        try {
            $provinces = $cache->getItem($cacheName);

            if (!$provinces->isHit()) {
                $file = __DIR__ . '/../Resources/data/administrative/province.' . $lang . '.php';

                if (is_file($file)) {
                    $data = include $file;
                }

                $provinces->set($data);
                $cache->save($provinces);
            }

            $provinces = $provinces->get();
        } catch (InvalidArgumentException $e) {
            $provinces = $data;
        }

        if ($country !== 'ALL' && isset($provinces[$country])) {
            $provinces = $provinces[$country];
        }

        return $provinces;
    }

    public function clearAllCache(): void
    {
        /** @var FilesystemAdapter $cache */
        $cache = $this->getCache();
        $cache->clear();
    }

    public function generatePdf(string $template, array $data): void
    {
        $html = $this->renderView($template, $data);

        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        //$pdf->stream($data['pdf_file_name']);

        $fs = new Filesystem();
        $fs->appendToFile(__DIR__ . '/../../var/pdf/' . $data['pdf_full_path'], $pdf->output());
    }

    public function populateParametersFromRequest(Request $request, string $method = 'POST'): array
    {
        $key = $method === 'GET' ? 'query' : 'request';

        $dateStart = $request->{$key}->get('date_start', null);
        $dateEnd = $request->{$key}->get('date_end', null);
        $startAt = $request->{$key}->get('start_at', null);
        $endAt = $request->{$key}->get('end_at', null);
        $statusLastChanged = $request->{$key}->get('status_last_changed', null);
        $ppkPaymentMethod = $request->{$key}->get('ppk_payment_method', null);
        $isUsedStartAt = $request->{$key}->get('is_used_start_at', null);
        $isUsedEndAt = $request->{$key}->get('is_used_end_at', null);
        $isUpdatedAt = $request->{$key}->get('updated_at', null);
        $cancel_request = $request->{$key}->get('cancel_request', null);
        $jump_to_page = $request->{$key}->get('jump_to_page', null);
        $tax_type = $request->{$key}->get('tax_type', null);
        $business_criteria = $request->{$key}->get('business_criteria', null);
        $id_lpse = $request->{$key}->get('id_lpse', null);
        $id_satker = $request->{$key}->get('id_satker', null);
        $sub_role = $request->{$key}->get('sub_role', null);

        return [
            'page' => abs($request->{$key}->get('page', '1')),
            'draw' => abs($request->{$key}->get('draw', '0')),
            'limit' => abs($request->{$key}->get('limit', '10')),
            'offset' => abs($request->{$key}->get('offset', '0')),
            'length' => abs($request->{$key}->get('length', '10')),
            'start' => abs($request->{$key}->get('start', '0')),
            'role' => $request->{$key}->get('role', null),
            'status' => $request->{$key}->get('status', null),
            'status_order' => $request->{$key}->get('status_order', null),
            'status_djp' => $request->{$key}->get('status_djp', null),
            'search' => $request->{$key}->get('search', null),
            'keywords' => $request->{$key}->get('keywords', null),
            'store' => $request->{$key}->get('store', null),
            'price_min' => $request->{$key}->get('price_min', null),
            'price_max' => $request->{$key}->get('price_max', null),
            'category' => $request->{$key}->get('category', null),
            'verified' => $request->{$key}->get('verified', null),
            'is_used' => $request->{$key}->get('is_used', null),
            'parent_category' => $request->{$key}->get('parent_category', null),
            'type' => $request->{$key}->get('type', null),
            'base_type' => $request->{$key}->get('base_type', null),
            'date_start' => !empty($dateStart) ? sprintf('%s 00:00:00', $dateStart) : null,
            'date_end' => !empty($dateEnd) ? sprintf('%s 23:59:59', $dateEnd) : null,
            'start_at' => !empty($startAt) ? sprintf('%s 00:00:00', $startAt) : null,
            'end_at' => !empty($endAt) ? sprintf('%s 23:59:59', $endAt) : null,
            'year' => $request->{$key}->get('year', null),
            'status_last_changed' => $statusLastChanged,
            'ppk_payment_method' => $ppkPaymentMethod,
            'jump_to_page' => $jump_to_page,
            'tax_type' => $tax_type,
            'business_criteria' => $business_criteria,
            'is_used_start_at' => !empty($isUsedStartAt) ? sprintf('%s 00:00:00', $isUsedStartAt) : null,
            'is_used_end_at' => !empty($isUsedEndAt) ? sprintf('%s 23:59:59', $isUsedEndAt) : null,
            'is_updated_at' => $isUpdatedAt === "true" ?? false,
            'cancel_request' => $cancel_request === "true" ?? false,
            'id_lpse' => $id_lpse,
            'id_satker' => $id_satker,
            'sub_role' => $sub_role
        ];
    }

    public function populateParametersForDataTable(Request $request, array $options = []): array
    {
        $parameters = $this->populateParametersFromRequest($request, $options['method'] ?? 'POST');

        $parameters['limit'] = $parameters['length'];
        $parameters['offset'] = $parameters['start'];
        $parameters['search'] = $parameters['keywords'];
        $parameters['order_by'] = $options['order_by'] ?? 't.id';
        $parameters['sort_by'] = $options['sort_by'] ?? 'DESC';

        return $parameters;
    }

    public function getProductCategoriesData()
    {
        $data = [];

        try {
            $cache = $this->getCache();
            /** @var CacheItem $items */
            $items = $cache->getItem('app_product_category');
            $data = $items->get();

            if (null === $data) {
                /** @var ProductCategoryRepository $repository */
                $repository = $this->getRepository(ProductCategory::class);
                $data = $repository->getDataForSelectOptions([
                    'order_by' => 'pc.id',
                    'sort_by' => 'DESC',
                ]);

                if (count($data) > 0) {
                    $items->set($data);
                    $cache->save($items);
                }
            }
        } catch (InvalidArgumentException $e) {
        }

        return $data;
    }

    public function manipulateCitiesData(): array
    {
        /** @var RajaOngkirService $rajaOngkir */
        $rajaOngkir = $this->get(RajaOngkirService::class);
        $cities = $rajaOngkir->getCity();
        $output = [];

        foreach ($cities as $city) {
            if (!isset($output[$city['province_id']])) {
                $output[$city['province_id']][] = $city;
            } else {
                $output[$city['province_id']][] = $city;
            }
        }

        ksort($output);

        return $output;
    }

    public function getProductCategoriesFeatured(int $limit = 6, string $featured = 'yes', string $fetchParent = 'no'): array
    {
        $cached = false;
        $parameters = [
            'order_by' => 'pc.sort',
            'sort_by' => 'ASC',
            'limit' => $limit,
        ];

        if ($featured === 'yes') {
            $parameters['featured'] = $featured;
        }

        if ($fetchParent === 'yes') {
            $parameters['fetch_parent'] = $fetchParent;
        }

        if ($limit === 0 && $featured === 'no' && $fetchParent === 'yes') {
            $cached = true;
        }

        if ($cached) {
            try {
                $cache = $this->getCache();
                /** @var CacheItem $items */
                $items = $cache->getItem('app_product_category_search_filter');
                $data = $items->get();

                if (null === $data) {
                    $data = $this->fetchProductCategoriesFeatured($parameters);

                    if (count($data) > 0) {
                        $items->set($data);
                        $cache->save($items);
                    }
                }

                return $data;
            } catch (InvalidArgumentException $e) {
            }
        }

        return $this->fetchProductCategoriesFeatured($parameters);
    }

    public function getPpnPercentage($umkm_type = 'usaha_mikro'): float
    {
        /** @var MasterTaxRepository $repository */
        $repository = $this->getRepository(MasterTax::class);
        $data = $repository->findOneBy(['umkm_category' => $umkm_type]);

        if (!$data instanceof MasterTax) {
            $data = $repository->findOneBy(['umkm_category' => 'usaha_mikro']);
        }

        return floatval($data->getPpn() / 100);
    }

    public function getUserStoresData()
    {
        $data = [];

        try {
            $cache = $this->getCache();
            /** @var CacheItem $items */
            $items = $cache->getItem('app_user_store');
            $data = $items->get();

            if (null === $data) {
                /** @var StoreRepository $repository */
                $repository = $this->getRepository(Store::class);
                $data = $repository->getDataForSelectOptions([
                    'order_by' => 's.id',
                    'sort_by' => 'DESC',
                ]);

                if (count($data) > 0) {
                    $items->set($data);
                    $cache->save($items);
                }
            }
        } catch (InvalidArgumentException $e) {
        }

        return $data;
    }

    public function getProductCategoriesParentData()
    {
        $data = [];

        try {
            $cache = $this->getCache();
            /** @var CacheItem $items */
            $items = $cache->getItem('app_product_category_parent');
            $data = $items->get();

            if (null === $data) {
                /** @var ProductCategoryRepository $repository */
                $repository = $this->getRepository(ProductCategory::class);
                $data = $repository->getDataForSelectOptions([
                    'parent_id' => 0,
                    'order_by' => 'pc.id',
                    'sort_by' => 'DESC',
                ]);

                if (count($data) > 0) {
                    $items->set($data);
                    $cache->save($items);
                }
            }
        } catch (InvalidArgumentException $e) {
        }

        return $data;
    }

    public function getProductCategoriesWithParentsData()
    {
        $data = [];

        try {
            $cache = $this->getCache();
            /** @var CacheItem $items */
            $items = $cache->getItem('app_product_category_with_parent');
            $data = $items->get();

            if (null === $data) {
                /** @var ProductCategoryRepository $repository */
                $repository = $this->getRepository(ProductCategory::class);
                $data = $repository->getCategoryDataWithParents();

                if (count($data) > 0) {
                    $items->set($data);
                    $cache->save($items);
                }
            }
        } catch (InvalidArgumentException $e) {
        }

        return $data;
    }

    public function fetchProductCategoriesFeatured(array $parameters): array
    {
        $categories = [];

        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository(ProductCategory::class);
        $productCategories = $repository->getFeaturedData($parameters);

        foreach ($productCategories as $productCategory) {
            $id = (int)$productCategory['id'];

            if (array_key_exists($id, $categories)) {
                processChildrenCategoryLevel1($categories, $productCategory, $id);
            } else {
                $categories[$id] = [
                    'id' => $id,
                    'text' => $productCategory['text'],
                    'icon' => $productCategory['icon'],
                    'class' => $productCategory['class'],
                    'status' => $productCategory['status'],
                    'children' => [],
                ];

                processChildrenCategoryLevel1($categories, $productCategory, $id);
            }
        }

        foreach ($productCategories as $productCategory) {
            processChildrenCategoryLevel2($categories, $productCategory, (int)$productCategory['id']);
        }

        return $categories;
    }

    public function getProductDiff(Product $product, array $dataInput, int $userId = 0): array
    {
        $originalData = $product->originalData();
        $originalImages = $product->originalFiles();
        $dataImages = [];
        $productDiff = [];
        $dataDiff = [];

        // Edit form input to match with entity data
//        $dataInput['p_category'] = !empty($dataInput['p_category']) ? implode(',', $dataInput['p_category']) : null;
        $dataInput['p_category'] = !empty($dataInput['p_category']) ? $dataInput['p_category'] : null;
        $dataInput['p_quantity'] = (int)str_replace(".", "", $dataInput['p_quantity']);
        $dataInput['p_price'] = (float)str_replace(".", "", $dataInput['p_price']);
        $dataInput['p_basePrice'] = (float)str_replace(".", "", $dataInput['p_basePrice']);
        $dataInput['p_weight'] = (int)$dataInput['p_weight'];
        $dataInput['p_id'] = (int)$dataInput['p_id'];
        $dataInput['s_id'] = isset($dataInput['s_id']) ? (int)$dataInput['s_id'] : null;

        if (isset($dataInput['p_imagesTmp']['filePath'])) {
            foreach ($dataInput['p_imagesTmp']['filePath'] as $image) {
                if ($image !== 'dist/img/bg.jpg') {
                    $dataImages[] = $image;
                }
            }
        }

        if (empty($dataInput['p_category'])) {
            unset(
                $dataInput['p_category'],
                $originalData['p_category'],
            );
        }

        unset(
            $dataInput['p_imagesTmp'],
            $dataInput['overridden'],
            $originalData['p_note'],
        );

        $dataChanges = array_diff($originalData, $dataInput);
        $imageChanges = array_diff($dataImages, $originalImages);


        if (count($dataChanges) > 0) {
            $dataDiff = $originalData;
            $dataDiff['p_images'] = [];

            $productDiff = $dataChanges;
            $productDiff['p_images'] = [];
            $productDiff['p_lastChangedBy'] = $userId;
            $productDiff['p_lastChangedAt'] = date('Y-m-d H:i:s');
        }

        if (count($imageChanges) > 0) {
            $dataDiff['p_images'] = $imageChanges;
            $productDiff['p_images'] = $originalImages;
        } else {
            unset($dataDiff['p_images'], $productDiff['p_images']);
        }

        return [
            'data' => $dataDiff,
            'diff' => $productDiff,
        ];
    }

    public function getStoreDiff(Store $store, array $dataInput, array $parameters = []): array
    {
        $originalData = $store->originalData();
        $storeDiff = [];
        $dataDiff = [];
        $userId = isset($parameters['user_id']) ? abs($parameters['user_id']) : 0;
        $origin = $parameters['origin'] ?? 'FE';

        // Edit form input to match with entity data
        if ($origin === 'FE') {
            $dataInput['s_name'] = $dataInput['name'];
            $dataInput['s_brand'] = $dataInput['brand'];
            $dataInput['s_description'] = $dataInput['description'];
            $dataInput['s_address'] = $dataInput['address'];
            $dataInput['s_postCode'] = $dataInput['post_code'];
            $dataInput['s_city'] = $dataInput['city'];
            $dataInput['s_cityId'] = (int)$dataInput['city_id'];
            $dataInput['s_district'] = $dataInput['district'];
            $dataInput['s_province'] = $dataInput['province'];
            $dataInput['s_provinceId'] = (int)$dataInput['province_id'];
            $dataInput['s_deliveryCouriers'] = $dataInput['delivery_couriers'] ?? [];
            $dataInput['s_isActive'] = $store->getIsActive();
            $dataInput['s_isVerified'] = $store->getIsVerified();
            $dataInput['s_typeOfBusiness'] = $dataInput['jenis_usaha'];
            $dataInput['s_modalUsaha'] = (float)$dataInput['modal_usaha'];
            $dataInput['s_totalManpower'] = $dataInput['total_manpower'];
            $dataInput['s_bankName'] = $dataInput['bank_name'];
            $dataInput['s_nomorRekening'] = $dataInput['nomor_rekening'];
            $dataInput['s_registeredNumber'] = $dataInput['registered_number'];
            $dataInput['s_rekeningName'] = $dataInput['rekening_name'];
            $dataInput['s_businessCriteria'] = $dataInput['kriteria_usaha'];
            $dataInput['s_position'] = $dataInput['position'];
            $dataInput['s_productCategories'] = $dataInput['product_categories'] ?? [];

            $dataInput['u_nik'] = $dataInput['nik'];
            $dataInput['u_dob'] = $dataInput['dob'];
            $dataInput['u_gender'] = $dataInput['gender'];
            $dataInput['u_fullName'] = $dataInput['full_name'];
            $dataInput['u_npwp'] = $dataInput['no_npwp'];
            $dataInput['u_email'] = $dataInput['user_email'];
            $dataInput['u_phoneNumber'] = $dataInput['user_phone_number'];

            if (isset($dataInput['pkp'])) {
                $dataInput['s_isPKP'] = (bool)$dataInput['pkp'] ? 'pkp' : 'non-pkp';
            }

//            if (!empty($dataInput['logo_img_temp'])) {
//                $dataInput['u_photoProfile'] = (string) $dataInput['logo_img_temp'];
//            }else {
//                unset($originalData['u_photoProfile']);
//            }
//
//            if (!empty($dataInput['dashboard_img_temp'])) {
//                $dataInput['u_bannerProfile'] = (string) $dataInput['dashboard_img_temp'];
//            }else {
//                unset($originalData['u_bannerProfile']);
//            }

            if (!empty($dataInput['npwp_img_temp'])) {
                $dataInput['u_npwpFile'] = (string)$dataInput['npwp_img_temp'];
            } else {
                unset($originalData['u_npwpFile']);
            }

            if (!empty($dataInput['ktp_img_temp'])) {
                $dataInput['u_ktpFile'] = (string)$dataInput['ktp_img_temp'];
            } else {
                unset($originalData['u_ktpFile']);
            }

            if (!empty($dataInput['rekening_img_temp'])) {
                $dataInput['s_rekeningFile'] = $dataInput['rekening_img_temp'];
            } else {
                unset($originalData['s_rekeningFile']);
            }

            if (count(json_decode($dataInput['sppkp_img'][0])) > 0) {
                $dataInput['s_sppkpFile'] = implode(',', json_decode($dataInput['sppkp_img'][0]));
                $originalData['s_sppkpFile'] = is_array($originalData['s_sppkpFile']) ? implode(',', $originalData['s_sppkpFile']) : '';
            } else {
                unset($originalData['s_sppkpFile']);
            }

            if (count(json_decode($dataInput['surat-ijin-usaha-img'][0])) > 0) {
                $dataInput['u_suratIjinFile'] = implode(',', json_decode($dataInput['surat-ijin-usaha-img'][0]));
                $originalData['u_suratIjinFile'] = is_array($originalData['u_suratIjinFile']) ? implode(',', $originalData['u_suratIjinFile']) : '';
            } else {
                unset($originalData['u_suratIjinFile']);
            }

            if (count(json_decode($dataInput['dokumen-tambahan-img'][0])) > 0) {
                $dataInput['u_dokumenFile'] = implode(',', json_decode($dataInput['dokumen-tambahan-img'][0]));
                $originalData['u_dokumenFile'] = is_array($originalData['u_dokumenFile']) ? implode(',', $originalData['u_dokumenFile']) : '';
            } else {
                unset($originalData['u_dokumenFile']);
            }

            unset(
                $dataInput['name'],
                $dataInput['brand'],
                $dataInput['description'],
                $dataInput['address'],
                $dataInput['province_id'],
                $dataInput['province'],
                $dataInput['city_id'],
                $dataInput['city'],
                $dataInput['district'],
                $dataInput['post_code'],
                $dataInput['delivery_couriers'],
                $dataInput['jenis_usaha'],
                $dataInput['modal_usaha'],
                $dataInput['total_manpower'],
                $dataInput['bank_name'],
                $dataInput['nomor_rekening'],
                $dataInput['pkp'],
                $dataInput['rekening_img_temp'],
                $dataInput['surat-ijin-usaha-img'],
                $dataInput['dokumen-tambahan-img'],
                $dataInput['sppkp_img'],
                $dataInput['surat-ijin-usaha-img-temp'],
                $dataInput['dokumen-tambahan-img-temp'],
                $dataInput['sppkp_img_temp'],
                $dataInput['logo_img_temp'],
                $dataInput['dashboard_img_temp'],
                $dataInput['npwp_img_temp'],
                $dataInput['ktp_img_temp'],
                $dataInput['rekening_img_temp'],
                $dataInput['rekening_name'],
                $dataInput['nik'],
                $dataInput['dob'],
                $dataInput['gender'],
                $dataInput['no_npwp'],
                $dataInput['kriteria_usaha'],
                $dataInput['status'],
                $dataInput['tnc-kerjasama'],
                $dataInput['tnc-kesepakatan'],
                $dataInput['position'],
                $dataInput['full_name'],
                $dataInput['registered_number'],
                $dataInput['deletedSiuFiles'],
                $dataInput['deletedDocFiles'],
                $dataInput['deletedSppkpFiles'],
                $dataInput['user_email'],
                $dataInput['user_phone_number'],
                $dataInput['product_categories'],
            );
        } else {
            $dataInput['s_isActive'] = isset($dataInput['s_isActive']) ? (bool)$dataInput['s_isActive'] : false;
            $dataInput['s_isVerified'] = isset($dataInput['s_isVerified']) ? (bool)$dataInput['s_isVerified'] : false;
            $dataInput['s_deliveryCouriers'] = $dataInput['s_deliveryCouriers'] ?? [];

            $dataInput['s_modalUsaha'] = (float)$dataInput['s_modalUsaha'];
            $dataInput['u_fullName'] = $dataInput['u_name'];

            if (isset($dataInput['s_isPKP'])) {
                $dataInput['s_isPKP'] = (bool)$dataInput['s_isPKP'] ? 'pkp' : 'non-pkp';
            }

            if (!empty($dataInput['u_suratIjin'])) {
                $dataInput['u_suratIjinFile'] = is_array($dataInput['u_suratIjin']) ? implode(',', $dataInput['u_suratIjin']) : null;
                $originalData['u_suratIjinFile'] = is_array($originalData['u_suratIjinFile']) ? implode(',', $originalData['u_suratIjinFile']) : '';
            } else {
                unset($originalData['u_suratIjinFile']);
            }

            if (!empty($dataInput['u_dokumenFile'])) {
                $dataInput['u_dokumenFile'] = is_array($dataInput['u_dokumenFile']) ? implode(',', $dataInput['u_dokumenFile']) : null;
                $originalData['u_dokumenFile'] = is_array($originalData['u_dokumenFile']) ? implode(',', $originalData['u_dokumenFile']) : '';
            } else {
                unset($originalData['u_dokumenFile']);
            }

            if (!empty($dataInput['s_sppkpFile'])) {
                $dataInput['s_sppkpFile'] = is_array($dataInput['s_sppkpFile']) ? implode(',', $dataInput['s_sppkpFile']) : null;
                $originalData['s_sppkpFile'] = is_array($originalData['s_sppkpFile']) ? implode(',', $originalData['s_sppkpFile']) : '';
            } else {
                unset($originalData['s_sppkpFile']);
            }

            if (empty($dataInput['s_rekeningFile'])) {
                unset($originalData['s_rekeningFile']);
            }

            if (empty($dataInput['u_npwpFile'])) {
                unset($originalData['u_npwpFile']);
            }

            if (empty($dataInput['u_ktpFile'])) {
                unset($originalData['u_ktpFile']);
            }

            unset(
                $dataInput['u_id'],
                $dataInput['u_suratIjin'],
                $dataInput['s_id'],
                $dataInput['btn_action'],
                $dataInput['s'],
                $dataInput['u_photoProfile'],
                $dataInput['u_bannerProfile'],
                $originalData['u_photoProfile'],
                $originalData['u_bannerProfile'],
            );
        }

        $originalData['s_deliveryCouriers'] = is_array($originalData['s_deliveryCouriers']) ? implode(',', $originalData['s_deliveryCouriers']) : null;
        $originalData['s_productCategories'] = is_array($originalData['s_productCategories']) ? implode(',', $originalData['s_productCategories']) : null;

        $dataInput['s_districtId'] = isset($dataInput['district_id']) ? (int)$dataInput['district_id'] : 0;
        $dataInput['s_country'] = isset($dataInput['country']) ? (int)$dataInput['country'] : 'ID';
        $dataInput['s_countryId'] = isset($dataInput['country_id']) ? (int)$dataInput['country_id'] : 0;
        $dataInput['s_deliveryCouriers'] = is_array($dataInput['s_deliveryCouriers']) ? implode(',', $dataInput['s_deliveryCouriers']) : null;
        $dataInput['s_productCategories'] = is_array($dataInput['s_productCategories']) ? implode(',', $dataInput['s_productCategories']) : null;

        $dataChanges = array_diff($originalData, $dataInput);

        if (count($dataChanges) > 0) {
            $dataDiff = $originalData;
            $storeDiff = $dataChanges;
            $storeDiff['p_lastChangedBy'] = $userId;
            $storeDiff['p_lastChangedAt'] = date('Y-m-d H:i:s');
        }

        return [
            'data' => $dataDiff,
            'diff' => $storeDiff,
        ];
    }

    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServerVar('HTTP_CLIENT_IP') !== null) {
            $ip = $this->getServerVar('HTTP_CLIENT_IP');
        } elseif ($checkProxy && $this->getServerVar('HTTP_X_FORWARDED_FOR') !== null) {
            $ip = $this->getServerVar('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServerVar('REMOTE_ADDR');
        }

        return $ip;
    }

    public function getServerVar($key = null, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    public function getTotalToBePaidForPayment(string $sharedInvoice = null, array $orders = []): float
    {
        /** @var VoucherUsedLogRepository $voucherRepository */
        $voucherRepository = $this->getRepository(VoucherUsedLog::class);
        $totalAmount = $totalVoucher = $totalToBePaid = 0;
        $totalVoucher = 0;
        foreach ($orders as $key => $order) {
            $products = $order->getOrderProducts();
            $pphTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPphNominal()) ? $order->getTreasurerPphNominal(): 0): 0;
            $ppnTreasurer  = $order->getTaxType() == '59' ? (!empty($order->getTreasurerPpnNominal()) ? $order->getTreasurerPpnNominal(): 0): 0;
            if ($sharedInvoice != null) {
                $vouchers = $voucherRepository->getVouchersForOrderBySharedId($sharedInvoice, false, (int)$order->getId());
            }
            $totalAmount += $order->getTotal() + $order->getShippingPrice();
            if ($order->getTotal() + $order->getShippingPrice() > 2220000) {
                $totalAmount = $totalAmount - $pphTreasurer - $ppnTreasurer;
            }

            if (!$order->getIsB2gTransaction()) {
                foreach ($products as $product) {
                    $totalAmount += (float)$product->getTaxNominal();
                }
            }

            if ($sharedInvoice != null) {
                if ($key === 0 && count($vouchers) > 0) {
                    foreach ($vouchers as $voucher) {
                        $totalVoucher = (float)$voucher['vul_totalVoucher'];
                    }
                }
            }
        }

        // if ($order->getTaxType() == '58') {
        //     $getRealShipping = $order->getShippingPrice() - (($order->getShippingPrice() * 11) / (100 + 11));
        //     $getPPNValue = ($order->getShippingPrice() * 11) / (100 + 11);
        //     $getPPhValue = ($order->getTotal() + $getRealShipping) * (0.5 / 100);
        //     $subTotal = round($totalAmount) - $getPPNValue - $getPPhValue;
        // }
        
        if ($totalAmount > $totalVoucher) {
            $totalToBePaid = $totalAmount - $totalVoucher;
        }


        // dd(round($totalToBePaid), $totalAmount, $totalVoucher);
        return round($totalToBePaid);
    }

    public function getCategoryNameFromProduct(Product $product): string
    {
        $categories = explode(',', $product->getCategory());
        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository(ProductCategory::class);
        /** @var ProductCategory $productCategory */
        $productCategory = $repository->getCategoryFromProduct($categories);

        return !empty($productCategory) ? $productCategory->getName() : '';
    }

    public function getVoucherListForEachOrder(string $sharedId, string $is_pkp): array
    {
        $reduceOrderByVoucher = [];
        $orderIds = [];
        $voucherIds = [];
        $totalVoucher = 0;
        $voucherUsedLogRepository = $this->getRepository(VoucherUsedLog::class);
        $orderRepository = $this->getRepository(Order::class);

        $voucherList = $voucherUsedLogRepository->getVouchersListBySharedId($sharedId);
        $orderList = $voucherUsedLogRepository->getTotalOrder($sharedId, 'vul.orderId');

        if (count($voucherList) > 1) {
            foreach ($voucherList as $item) {
                if (!in_array($item['vul_orderId'], $orderIds, false)) {
                    $orderIds[] = $item['vul_orderId'];
                }

                if (!in_array($item['vul_voucherId'], $voucherIds, false)) {
                    $voucherIds[] = $item['vul_voucherId'];
                    $totalVoucher += (float)$item['vul_voucherAmount'];
                }
            }

            $tmpVoucher = $totalVoucher;

            foreach ($orderList as $index => $item) {
                $orderAmount = (float)$item['orderAmount'];
                $seller = $orderRepository->find($item['o_id'])->getSeller();

                if ($is_pkp === '1') {
                    $tmp = $tmpVoucher - ($orderAmount + ((float)$item['o_total'] * $this->getPpnPercentage($seller->getUmkmCategory())));
                } else {
                    $tmp = $tmpVoucher - $orderAmount;
                }

                if ($tmp <= 0) {
                    $reduceOrderByVoucher[$orderIds[$index]] = $tmpVoucher;
                    $tmpVoucher = 0;
                } else if ($tmp > 0) {
                    $tmpVoucher = $tmp;
                    $reduceOrderByVoucher[$orderIds[$index]] = abs($totalVoucher - $tmp);
                } else {
                    $reduceOrderByVoucher[$orderIds[$index]] = 0;
                }
            }
        }

        return $reduceOrderByVoucher;

    }

    public function generateRequestId(): string
    {
        $dokuRepository = $this->getRepository(Doku::class);

        $attempt = 0;
        $defaultLength = 50;
        $requestId = StaticHelper::secureRandomCode($defaultLength);

        $requestIdExist = $dokuRepository->count(['requestId' => $requestId]);

        if ($requestIdExist > 0) {
            do {
                $requestId = StaticHelper::secureRandomCode($defaultLength);
                $count = $dokuRepository->count(['request_id' => $requestId]);
                $found = $count === 0 ? 'yes' : 'no';
            } while ($found === 'no');
        }

        return $requestId;
    }

    public function generateBniRequestId(): string
    {
        $bniRepository = $this->getRepository(Bni::class);

        $attempt = 0;
        $defaultLength = 10;
        $requestId = StaticHelper::secureRandomCode($defaultLength);

        $requestIdExist = $bniRepository->count(['requestId' => $requestId]);

        if ($requestIdExist > 0) {
            do {
                $requestId = StaticHelper::secureRandomCode($defaultLength);
                $count = $bniRepository->count(['request_id' => $requestId]);
                $found = $count === 0 ? 'yes' : 'no';
            } while ($found === 'no');
        }

        return $requestId;
    }

    public function generateDokuInvoiceNumber(): string
    {
        $dokuRepository = $this->getRepository(Doku::class);

        $attempt = 0;
        $defaultLength = 6;
        $hash = StaticHelper::secureRandomCode($defaultLength);
        $invoice = sprintf(getenv('DOKU_INVOICE'), date('m'), date('Y'), $hash);

        $invoiceExist = $dokuRepository->count(['invoice_number' => $invoice]);

        if ($invoiceExist > 0) {
            do {
                $hash = StaticHelper::secureRandomCode($defaultLength);
                $invoice = sprintf(getenv('DOKU_INVOICE'), date('m'), date('Y'), $hash);
                $count = $dokuRepository->count(['invoice_number' => $invoice]);
                $found = $count === 0 ? 'yes' : 'no';
            } while ($found === 'no');
        }

        return $invoice;
    }

    public function generateArtpayInvoiceNumber(): string
    {
        $dokuRepository = $this->getRepository(Doku::class);

        $attempt = 0;
        $defaultLength = 6;
        $hash = StaticHelper::secureRandomCode($defaultLength);
        $invoice = sprintf(getenv('ARTPAY_INVOICE'), date('m'), date('Y'), $hash);

        $invoiceExist = $dokuRepository->count(['invoice_number' => $invoice]);

        if ($invoiceExist > 0) {
            do {
                $hash = StaticHelper::secureRandomCode($defaultLength);
                $invoice = sprintf(getenv('ARTPAY_INVOICE'), date('m'), date('Y'), $hash);
                $count = $dokuRepository->count(['invoice_number' => $invoice]);
                $found = $count === 0 ? 'yes' : 'no';
            } while ($found === 'no');
        }

        return $invoice;
    }

    protected function logOrder($em, $previousOrderValues, $order, $user, $is_created = false): void
    {
        $this->appGenericEventDispatcher(new GenericEvent(null, [
            'em' => $em,
            'previousOrderValues' => $previousOrderValues,
            'currentOrderValues' => $order,
            'user' => $user,
            'is_created' => $is_created,
        ]), 'admin.order_change', new OrderChangeListener());
    }

    protected function setDisbursementProductFee($em, $order): void
    {
        $this->appGenericEventDispatcher(new GenericEvent(null, [
            'em' => $em,
            'order' => $order,
            'disbursement' => $this->getRepository(Disbursement::class),
        ]), 'user.set_disbursement_fee', new OrderDisbursementListener());
    }

    protected function generateMidtransOrderId(): string
    {
        $midtransRepository = $this->getRepository(Midtrans::class);

        $attempt = 0;
        $defaultLength = 10;
        $orderId = sprintf('BM-%s%s-%s', date('m'), date('y'), StaticHelper::secureRandomCode($defaultLength));

        $orderIdExist = $midtransRepository->count(['orderId' => $orderId]);

        if ($orderIdExist > 0) {
            do {
                $orderId = StaticHelper::secureRandomCode($defaultLength);
                $count = $midtransRepository->count(['orderId' => $orderId]);
                $found = $count === 0 ? 'yes' : 'no';
            } while ($found === 'no');
        }

        return $orderId;
    }

    protected function getParentChildProductCategories(): array
    {
        $data = [];

        $productCategoryRepository = $this->getRepository(ProductCategory::class);
        $parents = $productCategoryRepository->getCategoryParents();

        foreach ($parents as $parent) {
            $data[] = [
                'parent' => $parent,
                'child' => $productCategoryRepository->getChildrenCategoryData((int)$parent['id']),
            ];
        }

        return $data;
    }

    protected function response($data = [], $msg = 'success', $statusCode = 200): JsonResponse
    {
        $response = [
            'data' => $data,
            'message' => $msg,
            'status' => $statusCode
        ];

        return new JsonResponse($response, $statusCode);
    }

    public function constructResponse(Product $product, $array): array
    {
        return array_merge($product->getData(), $array);
    }

    public function getProductImages(Product $product): array
    {
        $images = [];

        if (!empty($product->getFiles())) {
            foreach ($product->getFiles() as $file) {
                $images[] = $this->getBaseUrl() . '/' . $file->getFilePath();
            }
        }

        return $images;
    }
}
