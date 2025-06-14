<?php

namespace App\Controller\Order;

use App\Controller\PublicController;
use App\Email\BaseMail;
use App\Entity\Doku;
use App\Entity\Midtrans;
use App\Entity\Notification;
use App\Entity\Operator;
use App\Entity\Satker;
use App\Entity\Order;
use App\Entity\OrderPayment;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Qris;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserAddress;
use App\Entity\UserTaxDocument;
use App\Entity\VirtualAccount;
use App\Entity\Voucher;
use App\Entity\VoucherUsedLog;
use App\Entity\UserPicDocument;
use App\Entity\UserPpkTreasurer;
use App\EventListener\OrderChangeListener;
use App\EventListener\OrderEntityListener;
use App\EventListener\OrderNegotiationEntityListener;
use App\EventListener\SetOrderSharedInvoiceEntityListener;
use App\Helper\StaticHelper;
use App\Repository\OrderPaymentRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\QrisRepository;
use App\Repository\StoreRepository;
use App\Repository\UserAddressRepository;
use App\Repository\UserTaxDocumentRepository;
use App\Repository\VirtualAccountRepository;
use App\Repository\VoucherRepository;
use App\Repository\VoucherUsedLogRepository;
use App\Service\DokuService;
use App\Service\GoSendService;
use App\Service\MidtransService;
use App\Service\QrCodeGenerator;
use App\Service\QRISClient;
use App\Service\RajaOngkirService;
use App\Service\SamitraService;
use App\Service\TokoDaringService;
use App\Service\WSClientBPD;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DateTimeZone;
use App\Service\HttpClientService;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends PublicController
{
    private $successKey = 'order_success';
    private $invoicesKey = 'order_invoices';
    private $sharedIdKey = 'order_shared_id';
    private $qrPayKey = 'order_qr_pay';
    private $vaPayKey = 'order_va_pay';
    private $allowedRoles = ['ROLE_USER', 'ROLE_USER_BUYER', 'ROLE_USER_GOVERNMENT', 'ROLE_USER_BUSINESS'];
    private $allowedIp = ['103.215.26.210', '123.231.238.226'];
    private $b2gSessionKey = 'b2g_cart_session_key';

    public function shipping()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $courier = $request->request->get('courier', null);
        $addressId = abs($request->request->get('address', '0'));
        $cityId = abs($request->request->get('city', '0'));
        $provinceId = abs($request->request->get('province', '0'));
        $store = abs($request->request->get('store', '0'));
        $origin = abs($request->request->get('origin', '0'));
        $weight = abs($request->request->get('weight', '1'));
        $parameters = [
            'courier' => $courier,
            'origin' => $origin,
            'destination' => $cityId > 0 ? $cityId : $provinceId,
            'weight' => $weight * 1000, // (1 kg -> 1000 g)
        ];

        if ($courier === 'samitra') {
            /** @var UserAddressRepository $addressRepository */
            $addressRepository = $this->getRepository(UserAddress::class);
            /** @var StoreRepository $storeRepository */
            $storeRepository = $this->getRepository(Store::class);
            /** @var UserAddress $userAddress */
            $userAddress = $addressRepository->findOneBy([
                'id' => $addressId,
                'user' => $this->getUser(),
            ]);
            /** @var Store $storeAddress */
            $storeAddress = $storeRepository->find($store);

            if ($userAddress instanceof UserAddress && $storeAddress instanceof Store) {
                unset($parameters['courier']);

                $parameters['origin'] = $storeAddress->getCity();
                $parameters['destination'] = $userAddress->getCity();
                $parameters['weight'] = ceil($weight);

                /** @var SamitraService $samitra */
                $samitra = $this->get(SamitraService::class);
                $response = $samitra->getCost($parameters);
            } else {
                $response = [
                    'error' => true,
                    'message' => null,
                    'data' => null,
                ];
            }
        } elseif ($courier === 'gosend') {

            /** @var UserAddressRepository $addressRepository */
            $addressRepository = $this->getRepository(UserAddress::class);
            /** @var StoreRepository $storeRepository */
            $storeRepository = $this->getRepository(Store::class);
            /** @var UserAddress $userAddress */
            $userAddress = $addressRepository->findOneBy([
                'id' => $addressId,
                'user' => $this->getUser(),
            ]);
            /** @var Store $storeAddress */
            $storeAddress = $storeRepository->find($store);

            if ($userAddress instanceof UserAddress && $storeAddress instanceof Store) {
                unset($parameters['courier'], $parameters['weight']);

                if (empty($userAddress->getAddressLat()) || empty($userAddress->getAddressLng())) {

                    $response = [
                        'error' => true,
                        'message' => $this->translator->trans('message.error.buyer_address_pin_is_empty'),
                        'data' => null,
                    ];

                } elseif (empty($storeAddress->getAddressLat()) || empty($storeAddress->getAddressLng())) {

                    $response = [
                        'error' => true,
                        'message' => $this->translator->trans('message.error.seller_address_pin_is_empty'),
                        'data' => null,
                    ];

                }else {
                    $parameters['origin'] = $storeAddress->getAddressLat() . ',' . $storeAddress->getAddressLng();
                    $parameters['destination'] = $userAddress->getAddressLat() . ',' . $userAddress->getAddressLng();

                    $gosend = $this->get(GoSendService::class);
                    $response = $gosend->estimateCost($parameters);
                }

            } else {
                $response = [
                    'error' => true,
                    'message' => null,
                    'data' => null,
                ];
            }
        } else {
            /** @var RajaOngkirService $rajaOngkir */
            $rajaOngkir = $this->get(RajaOngkirService::class);

            if ($rajaOngkir->getAccountType() === 'pro') {
                $parameters['origin_type'] = 'city';
                $parameters['destination_type'] = 'city';
            }

            $response = $rajaOngkir->getCost($parameters);
        }

        return $this->view('', $response, 'json');
    }

    public function process(): RedirectResponse
    {
        $this->deniedManyAccess($this->allowedRoles);

        /** @var User $buyer */
        $buyer = $this->getUser();

        $userCart = $this->getUserCart();
        
        /** @var SessionInterface $session */
        $session = $this->getSession();
        /** @var UserTaxDocumentRepository $taxRepository */
        $taxRepository = $this->getRepository(UserTaxDocument::class);

        if ($userCart->getTotalItem() < 1 || !$session->has(getenv('ORDER_CART_KEY'))) {
            return $this->redirectToRoute('cart_index');
        }

        $request = $this->getRequest();
        $tnc = abs($request->request->get('tnc', '0'));
        // $withTax = abs($request->request->get('with_tax', '0'));
        $withTax = 1;
        $taxDocumentId = abs($request->request->get('tax_document', '0'));
        $postData = $request->request->all();
        $merchants = $session->get(getenv('ORDER_CART_KEY'));
        $vouchers = $session->has(getenv('ORDER_VOUCHER_KEY')) ? $session->get(getenv('ORDER_VOUCHER_KEY')) : [];
        $isB2g = $buyer->getRole() === 'ROLE_USER_GOVERNMENT';

        // dd($merchants, $request);
        if ($tnc !== 1) {
            return $this->orderIsIncomplete('tnc');
        }

        foreach ($merchants as $key => $merchant) {
            $index = $merchant['hash'];
            $id_merchant = (string)$key;
            // $tempQty = 1; // Let's assume min qty is 1
            // $tempTax = 10; // Static base tax value

            // if ($isB2g) {
            //     foreach ($merchant['items'] as $tempItem) {
            //         if ($id_merchant !== $tempItem['attributes']['origin']) {
            //             continue;
            //         }

            //         $tempQty = $tempItem['quantity'];
            //         $tempTax = $tempItem['attributes']['tax_value'];
            //     }
            // }

            if (isset($postData['pick_up'][$index]) && abs($postData['pick_up'][$index]) > 0) {
                $freeShippingValue = abs($postData['pick_up'][$index]);

                $merchants[$key]['shipping']['pick_up'] = 'yes';
                $merchants[$key]['shipping']['address_id'] = 0;
                $merchants[$key]['shipping']['name'] = 'n/a';
                $merchants[$key]['shipping']['service'] = 'n/a';
                $merchants[$key]['shipping']['price'] = 0;
                $merchants[$key]['shipping']['note'] = '';

                if ($freeShippingValue === 1) {
                    $merchants[$key]['shipping']['service'] = 'self_pick_up';
                } elseif ($freeShippingValue === 2) {
                    $merchants[$key]['shipping']['service'] = 'self_pick_up_2';
                } elseif ($freeShippingValue === 3) {
                    $merchants[$key]['shipping']['service'] = 'self_pick_up_3';
                }elseif ($freeShippingValue === 4) {
                    $merchants[$key]['shipping']['service'] = 'self_pick_up_4';
                }

                if (isset($postData['note'][$index]) && !empty($postData['note'][$index])) {
                    $merchants[$key]['shipping']['note'] = $postData['note'][$index];
                }
            } else {
                if (isset($postData['address'][$index]) && !empty($postData['address'][$index])) {
                    $merchants[$key]['shipping']['address_id'] = $postData['address'][$index];
                } else {
                    return $this->orderIsIncomplete('address');
                }

                if (isset($postData['shp_name'][$index]) && !empty($postData['shp_name'][$index])) {
                    $merchants[$key]['shipping']['name'] = $postData['shp_name'][$index];
                } else {
                    return $this->orderIsIncomplete();
                }

                if (isset($postData['shp_service'][$index]) && !empty($postData['shp_service'][$index])) {
                    $service = explode('|', $postData['shp_service'][$index]);

                    $merchants[$key]['shipping']['service'] = $service[0];
                    $merchants[$key]['shipping']['price'] = $service[1];
                    //$merchants[$key]['shipping']['price'] = str_replace(['.', ','], '', $service[1]);
                } else {
                    return $this->orderIsIncomplete();
                }

                if (isset($postData['note'][$index]) && !empty($postData['note'][$index])) {
                    $merchants[$key]['shipping']['note'] = $postData['note'][$index];
                } else {
                    $merchants[$key]['shipping']['note'] = '';
                }
            }

            if (isset($postData['negotiated'][$index]) && abs($postData['negotiated'][$index]) === 1) {
                $negotiatedPrice = is_array($postData['negotiated_price'][$index]) ? $postData['negotiated_price'][$index] : [];
                $negotiatedTime = $postData['negotiated_time'][$index] ?? '';
                $negotiatedShipping = $postData['negotiated_shipping'][$index] ?? '';
                $negotiatedShippingShow = $postData['negotiated_shipping_show'][$index] ?? '';

                if (!empty($negotiatedTime) && count($negotiatedPrice) > 0) {
                    $merchants[$key]['negotiation']['price'] = $negotiatedPrice;
                    $merchants[$key]['negotiation']['time'] = $negotiatedTime;
                    $merchants[$key]['negotiation']['shipping'] = $negotiatedShipping;
                    $merchants[$key]['negotiation']['note'] = $postData['negotiated_note'][$index] ?? '';

                    if ($withTax === 0) {
                        $negotiatedShippingShow = str_replace('.', '', $negotiatedShippingShow);

                        if ((int)$negotiatedShippingShow > 0) {
                            $merchants[$key]['negotiation']['shipping'] = $negotiatedShippingShow;
                        }
                    }

                    // if ($isB2g) {
                    //     $merchants[$key]['negotiation']['quantity'] = $tempQty;
                    //     $merchants[$key]['negotiation']['tax'] = $tempTax;
                    // }
                    $arrayQty = array();
                    $taxValue = $this->getParameter('tax_value');
                    if ($isB2g) {
                        foreach ($merchant['items'] as $tempItem) {
                            if ($id_merchant !== $tempItem['attributes']['origin']) {
                                continue;
                            }
                            array_push($arrayQty, $tempItem['quantity']);

                            $taxValue = $tempItem['attributes']['tax_value'];

                        }
                    }
                    $merchants[$key]['negotiation']['quantity'] = $arrayQty;
                    $merchants[$key]['negotiation']['tax'] = $taxValue;
                } else {
                    return $this->orderIsIncomplete('negotiation');
                }
            }

            if (!$isB2g && $taxDocumentId > 0) {
                $merchants[$key]['request_tax_invoice'] = true;
            }
        }

        /** @var UserAddress[] $addresses */
        $addresses = $buyer->getAddresses();
        $em = $this->getEntityManager();

        $orders = [];
        $sellers = [];
        $invoices = [];
        $attachments = [];
        $sharedId = sprintf('%d-%s', $buyer->getId(), StaticHelper::secureRandomCode());
        $counter = 1;
        foreach ($merchants as $key => $merchant) {
            $tempInvoice = StaticHelper::secureRandomCode();
            $shipping = $merchant['shipping'];
            $shippingName = $shipping['name'];
            $shippingService = $shipping['service'];
            $shippingPrice = $shipping['price'];
            $addressId = $shipping['address_id'];
            /** @var Store $store */
            $store = $this->getRepository(Store::class)->find($key);
            $storePKP = $store->getIsPKP();
            $address = null;
            $total = 0;

            // if ($isB2g && $storePKP) {
            //     $withTax = 1;

            //     //case ketika b2c checkout di 2 toko berbeda (pkp dan non-pkp)
            // } else if ($withTax === 1 && !$storePKP) {
            //     $withTax = 0;
            // } else if ($withTax === 0 && $storePKP) {
            //     $withTax = 1;
            // }

            if (!$isB2g && isset($shipping['pick_up']) && $shipping['pick_up'] === 'yes') {
                $shippingName = 'free_delivery';
                //$shippingService = 'self_pick_up';
                $shippingPrice = 0;
            }

            // With tax aja yang shipping pricenya di * 0.1 PPN, kalau store PKP ongkirnya tetapkan walaupun dia centang Faktur Pajak
            if ($withTax === 1) {
                $shippingPrice += ($shippingPrice * $this->getPpnPercentage($store->getUmkmCategory()));
            }

            foreach ($merchant['items'] as $item) {
                $total += $item['attributes']['price'] * $item['quantity'];
                if ($item['attributes']['with_tax'] == 1 && $item['attributes']['free_tax'] != 1) {
                    $total += $item['attributes']['tax_nominal'];
                }
            }

            foreach ($addresses as $userAddress) {
                if ((int)$addressId === (int)$userAddress->getId()) {
                    $address = $userAddress;
                    break;
                }
            }

            if ($isB2g) {
                $tempSharedId = $sharedId;
                $sharedId = $tempSharedId . '_' . $counter;
                $counter++;
            }

            /** @var UserPicDocumentRepository $repository */
            $repositoryPic = $this->getRepository(UserPicDocument::class);
            $data_pic = $repositoryPic->find($postData['pic-data']);

            /** @var UserPpkTreasurerRepository $repository */
            $repositoryPPK = $this->getRepository(User::class);
            $data_ppk = $repositoryPPK->find($postData['ppk-data']);
            $satker_data = $this->getRepository(Satker::class)->find($postData['satker-data']);
            $repositoryTreasurer = $this->getRepository(UserPpkTreasurer::class);
            $data_treasurer = $repositoryPPK->find($postData['treasurer-data']);
            

            $order = new Order();
            $order->setInvoice($tempInvoice);
            $order->setSeller($store);
            $order->setBuyer($buyer);
            $order->setSharedId($sharedId);
            $order->setTotal($total);
            $order->setTotalBackup($total);
            $order->setStatus($isB2g ? 'new_order' : 'pending');
            $order->setShippingCourier($shippingName);
            $order->setShippingService($shippingService);
            $order->setShippingPrice((float)$shippingPrice);
            $order->setShippingPriceBackup((float)$shippingPrice);
            $order->setName(trim(sprintf('%s %s', $buyer->getFirstName(), $buyer->getLastName())));
            $order->setEmail($buyer->getEmailCanonical());
            $order->setPhone($buyer->getPhoneNumber());
            $order->setTnc('checked');
            $order->setIsB2gTransaction($isB2g);
            $order->setNote(htmlspecialchars($shipping['note']));
            $order->setWorkUnit($buyer);
            $order->setJobPackageName(filter_var($postData['job-package-name'], FILTER_SANITIZE_STRING));
            $order->setRupCode(filter_var($postData['rup'], FILTER_SANITIZE_STRING));
            $order->setFiscalYear(filter_var($postData['fiscal-year'], FILTER_SANITIZE_STRING));
            $source_of_fund = $postData['source-of-fund'] == 'LAINNYA' ? $postData['other-source-of-fund']:$postData['source-of-fund'];
            $order->setSourceOfFund(filter_var($source_of_fund, FILTER_SANITIZE_STRING));
            $order->setBudgetCeiling(filter_var(str_replace(".", "", $postData['budget-ceiling'])), FILTER_SANITIZE_STRING);
            $order->setWorkUnitName($satker_data->getSatkerName());
            $order->setInstitutionName($data_ppk->getLkppKLDI());
            $order->setBudgetAccount(filter_var($postData['budget-account'], FILTER_SANITIZE_STRING));
            $order->setPpkPaymentMethod(filter_var($postData['payment-method'], FILTER_SANITIZE_STRING));
            $order->setExecutionTime($merchant['negotiation']['time']);
            $order->setCreatedAt();
            $order->setUpdatedAt();
            $order->setSatkerId($postData['satker-data']);
            $order->setTypeOrder('master');

            if (!empty($postData['pic-data'])) {
                $order->setUnitName(filter_var($data_pic->getName(), FILTER_SANITIZE_STRING));
                $order->setUnitPic(filter_var($data_pic->getUnit(), FILTER_SANITIZE_STRING));
                $order->setUnitEmail(filter_var($data_pic->getEmail(), FILTER_SANITIZE_STRING));
                $order->setUnitAddress(filter_var($data_pic->getAddress(), FILTER_SANITIZE_STRING));
                $order->setUnitTelp(filter_var($data_pic->getNotelp(), FILTER_SANITIZE_STRING));
            }

            if (!empty($postData['ppk-data'])) {
                $order->setPpkName(filter_var($data_ppk->getFirstName().' '.$data_ppk->getLastName(), FILTER_SANITIZE_STRING));
                $order->setPpkNip(filter_var($data_ppk->getNip(), FILTER_SANITIZE_STRING));
                $order->setPpkType(filter_var($data_ppk->getSubRoleTypeAccount(), FILTER_SANITIZE_STRING));
                $order->setPpkEmail(filter_var($data_ppk->getEmail(), FILTER_SANITIZE_STRING));
                $order->getPpkTelp(filter_var($data_ppk->getPhoneNumber(), FILTER_SANITIZE_STRING));
                $order->setPpkId($postData['ppk-data']);
            }

            if (!empty($postData['treasurer-data'])) {
                $order->setTreasurerName(filter_var($data_treasurer->getFirstName().' '.$data_treasurer->getLastName(), FILTER_SANITIZE_STRING));
                $order->setTreasurerNip(filter_var($data_treasurer->getNip(), FILTER_SANITIZE_STRING));
                $order->setTreasurerType(filter_var($data_treasurer->getSubRoleTypeAccount(), FILTER_SANITIZE_STRING));
                $order->setTreasurerEmail(filter_var($data_treasurer->getEmail(), FILTER_SANITIZE_STRING));
                $order->setTreasurerTelp(filter_var($data_treasurer->getPhoneNumber(), FILTER_SANITIZE_STRING));
                $order->setTreasurerId($postData['treasurer-data']);
            }


            if ($address instanceof UserAddress) {
                $order->setAddress($address->getAddress());
                $order->setPostCode($address->getPostCode());
                $order->setCity($address->getCity());
                $order->setCityId((int)$address->getCityId());
                $order->setDistrict($address->getDistrict());
                $order->setDistrictId((int)$address->getDistrictId());
                $order->setProvince($address->getProvince());
                $order->setProvinceId((int)$address->getProvinceId());
                $order->setCountry($address->getCountry());
                $order->setCountryId((int)$address->getCountryId());

                $order->setAddressLat($address->getAddressLat());
                $order->setAddressLng($address->getAddressLng());
            } else {
                $order->setCityId(0);
                $order->setDistrictId(0);
                $order->setProvinceId(0);
                $order->setCountryId(0);
            }

            if ($taxDocumentId > 0) {
                /** @var UserTaxDocument $taxDocument */
                $taxDocument = $taxRepository->find($taxDocumentId);

                if ($taxDocument instanceof UserTaxDocument) {
                    $order->setTaxDocumentEmail($taxDocument->getEmail());
                    $order->setTaxDocumentPhone($taxDocument->getPhone());
                    $order->setTaxDocumentFile($taxDocument->getImage());
                    $order->setTaxDocumentNpwp($taxDocument->getNumber());
                }
            }

            if ($isB2g) {
                if (isset($merchant['negotiation']) && $buyer->getId() > 0 && count($merchant['negotiation']) > 0) {
                    $negotiation = $merchant['negotiation'];
                    $shippingPrice = $negotiation['shipping'];
                    $tmpTotal = 0;
                    $indexQty = 0;
                    $limit = $storePKP ? $this->getParameter('b2gLimitAmountForPkpStore') : 0;//$this->getParameter('b2gLimitAmountForNonPkpStore')

                    foreach ($negotiation['price'] as $price) {
                        $tmpTotal += $price;
                        $indexQty++;
                    }

                    $tmpTotal += $shippingPrice;

                    $limitAmountWithoutApproval = $this->getParameter('role_pp_amount_without_approval');
                    $limitAmountWithApproval = $this->getParameter('role_pp_max_amount_with_approval');

//                    if ($tmpTotal <= $limitAmountWithoutApproval || $buyer->getLkppRole() === 'PPK') {
//                        $order->setStatus('confirmed');
//                    }elseif ($tmpTotal > $limitAmountWithoutApproval && $tmpTotal <= $limitAmountWithApproval) {
//                        $order->setStatus('pending_approve');
//                    }

                    if ($limit != 0) {
                        if ($tmpTotal > $limit) {
                            return $this->orderIsIncomplete($storePKP ? 'b2gLimitPKP':'b2gLimit');
                        }
                    }
                }
            }


            $em->persist($order);
            $em->flush();

            $this->appGenericEventDispatcher(new GenericEvent($order, [
                'em' => $em,
                'cart' => $userCart,
                'items' => $merchant['items'],
                'tax' => $withTax === 1,
                'request_tax_invoice' => $merchant['request_tax_invoice'] ?? false,
                'free_tax_category_list' => $this->getParameter('free_tax_for_category'),
            ]), 'front.order_process', new OrderEntityListener());

            if ($isB2g && isset($merchant['negotiation'])) {
                $this->appGenericEventDispatcher(new GenericEvent($order, [
                    'em' => $em,
                    'buyer_id' => $buyer->getId(),
                    'items' => $merchant['items'],
                    'negotiation' => $merchant['negotiation'],
                    'tax' => $withTax === 1,
                    'free_tax_category_list' => $this->getParameter('free_tax_for_category'),
                ]), 'front.order_negotiation', new OrderNegotiationEntityListener());
            }

            $orders[] = $order;
            $sellers[] = $store;
            $invoices[] = $order->getInvoice();

            if (count($vouchers) > 0) {
                foreach ($vouchers as $idx => $voucher) {
                    $voucherUsedLog = new VoucherUsedLog();
                    $voucherUsedLog->setVoucherId((int)$voucher['valid']);
                    $voucherUsedLog->setUserId($buyer->getId());
                    $voucherUsedLog->setOrderId($order->getId());
                    $voucherUsedLog->setOrderSharedId($order->getSharedId());
                    $voucherUsedLog->setVoucherAmount((float)$voucher['amount']);
                    $voucherUsedLog->setOrderAmount($order->getTotal() + $order->getShippingPrice());

                    $em->persist($voucherUsedLog);
                }

                $em->flush();
            }
        }

        //--- Create invoice (pdf) attachments
        /** @var OrderRepository $repository */
        $repository = $this->getRepository(Order::class);

        if (!$isB2g) {
            /** @var VoucherUsedLogRepository $usedLogRepository */
            $usedLogRepository = $this->getRepository(VoucherUsedLog::class);
//            $voucherLists = $usedLogRepository->getVouchersForOrderBySharedId($sharedId, true);
            $voucherLists = $usedLogRepository->getTotalOrder($sharedId);
            $orderLists = $usedLogRepository->getTotalOrder($sharedId, 'vul.orderId');

            $voucherIds = [];
            $orderIds = [];
            $isPaidWithVoucher = false;
            if (count($voucherLists) > 0) {
                $totalOrder = 0;
                $totalVoucher = 0;

                foreach ($voucherLists as $list) {
                    if (!in_array($list['voucherId'], $voucherIds)) {
                        $voucherIds[] = $list['voucherId'];
                        $totalVoucher += (float)$list['voucherAmount'];
                    }
                }

                foreach ($orderLists as $list) {
                    if (!in_array($list['orderId'], $orderIds)) {
                        $orderIds[] = $list['orderId'];
                        $totalOrder += (float)$list['orderAmount'];

                        if ($list['op_withTax'] && !empty($list['op_taxNominal'])) {
                            $totalOrder += (float) $list['op_taxNominal'];
                        }
                    }
                }

                if (($totalOrder - $totalVoucher) <= 0) {
                    $isPaidWithVoucher = true;
                }
            }

            foreach ($orders as $order) {
                $prevOrder = clone $order;
                if ($isPaidWithVoucher) {
                   $order->setStatus('paid');

                    $em->persist($order);
                }
                $this->logOrder($em, $prevOrder, $order, $this->getUser(), true);

                /** @var User $buyer */
                $buyer = $order->getBuyer();
                $store = $order->getSeller();
                $pdfFileName = sprintf('%s.pdf', str_replace('/', '-', $order->getInvoice()));
                $invoiceData = [
                    'order' => $repository->getOrderDetail($order->getId()),
                    'pdf_file_name' => $pdfFileName,
                    'pdf_full_path' => sprintf('invoice/%s/%s', $buyer->getId(), $pdfFileName),
                    'date' => indonesiaDateFormatAlt(time()),
                    'seller' => $store->toArray(),
                ];

                $invoiceFile = __DIR__ . '/../../../var/pdf/' . $invoiceData['pdf_full_path'];

                if (is_file($invoiceFile)) {
                    unlink($invoiceFile);
                }

                $this->generatePdf('@__main__/public/user/order/print/invoice.html.twig', $invoiceData);

                $attachments[] = $invoiceFile;
            }

            if ($isPaidWithVoucher) {
                $em->flush();
            }
        } else {
            $prevOrder = clone $order;
            $this->logOrder($em, $prevOrder, $order, $this->getUser(), true);
        }
        //--- Create invoice (pdf) attachments

        //--- Create shared invoice(s)
        $this->appGenericEventDispatcher(new GenericEvent($repository, [
            'em' => $this->getEntityManager(),
            'orders' => $orders,
            'run_type' => $isB2g ? 'b2g_batch' : 'single',
        ]), 'front.set_order_shared_invoice', new SetOrderSharedInvoiceEntityListener());
        //--- Create shared invoice(s)

        /** @var BaseMail $baseMail */
        $baseMail = $this->get(BaseMail::class);
        $translator = $this->getTranslator();

        //--- Send email notification to buyer
        $mailToBuyer = clone $baseMail;
        $mailToBuyer->setMailSubject($translator->trans('message.info.order_placed'));
        $mailToBuyer->setMailTemplate('@__main__/email/order_placed.html.twig');
        $mailToBuyer->setMailRecipient($buyer->getEmailCanonical());
        $mailToBuyer->setMailData([
            'name' => $buyer->getFirstName(),
            'full_name' => trim(sprintf('%s %s', $buyer->getFirstName(), $buyer->getLastName())),
            'orders' => $orders,
            'link_order' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'link_confirmation' => $this->generateUrl('user_payment_confirmation', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'with_tax' => $withTax === 1,
            'is_b2g' => $isB2g,
            'vouchers' => $vouchers,
        ]);
        $mailToBuyer->setMailAttachments($attachments);
        $mailToBuyer->send();
        //--- Send email notification to buyer

        //--- Send email notification to admin
        $mailToAdmin = clone $baseMail;
        $mailToAdmin->setMailSubject($translator->trans('message.info.new_order'));
        $mailToAdmin->setMailTemplate('@__main__/email/new_order.html.twig');
        $mailToAdmin->setToAdmin();
        $mailToAdmin->setMailData([
            'orders' => $orders,
            'invoices' => $invoices,
            'link' => $this->generateUrl('admin_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToAdmin->send();
        //--- Send email notification to admin

//        --- Send email notification to each sellers
        foreach ($sellers as $seller) {
            /** @var User $owner */
            $owner = $seller->getUser();
            $mailToSeller = clone $baseMail;
            $mailToSeller->setMailSubject($translator->trans('message.info.order_received'));
            $mailToSeller->setMailTemplate('@__main__/email/order_notification.html.twig');
            $mailToSeller->setMailRecipient($owner->getEmailCanonical());
            $mailToSeller->setMailData([
                'name' => $owner->getFirstName(),
                'recipient_type' => 'seller',
                'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailToSeller->send();
        }
//        --- Send email notification to each sellers

        $userCart->destroy();
        $session->set($this->successKey, true);
        $session->set($this->invoicesKey, $invoices);
        $session->set($this->sharedIdKey, $orders[0]->getSharedId());
        $session->remove(getenv('ORDER_CART_KEY'));
        $session->remove(getenv('ORDER_CALCULATION_KEY'));
        $session->remove(getenv('ORDER_VOUCHER_KEY'));
        $session->remove(getenv('ORDER_VOUCHER_RESTORED_KEY'));

        if (!$isB2g) {
            $session->set($this->qrPayKey, true);
            $session->set($this->vaPayKey, true);
        }

        return $this->redirectToRoute('order_success');
    }

    public function success()
    {
        /** @var SessionInterface $session */
        $session = $this->getSession();

        if (!$session->has($this->successKey)) {
            return $this->redirectToRoute('homepage');
        }

        $invoices = $session->get($this->invoicesKey);
        $sharedInvoice = $session->get($this->sharedIdKey);

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);
        /** @var Order[] $orders */
        $orders = $orderRepository->findBy([
            'sharedId' => $sharedInvoice,
            'status' => 'pending',
        ]);

        $amount = $this->getTotalToBePaidForPayment($sharedInvoice, $orders);
        $amountLimit = $this->getParameter('qris_amount_limit');
        $orderInvoice = null;
        $orderProducts = [];
        $orderTotal = $orderTax = $orderShipping = 0;

        foreach ($orders as $order) {
            $orderInvoice = $order->getSharedInvoice();
            $orderTotal += $order->getTotal() + $order->getShippingPrice();
            $orderShipping += $order->getShippingPrice();

            foreach ($order->getOrderProducts() as $orderProduct) {
                $orderTax += $orderProduct->getTaxNominal();
                $product = $orderProduct->getProduct();
                $store = $product ? $product->getStore() : null;
                $orderProducts[] = [
                    'name' => $orderProduct->getOriginalName(),
                    'id' => $orderProduct->getOriginalId(),
                    'price' => (float)$orderProduct->getTotalPrice(),
                    'brand' => $store ? $store->getName() : '',
                    'category' => $product ? $this->getCategoryNameFromProduct($product) : '',
                    'variant' => '',
                    'quantity' => $orderProduct->getQuantity(),
                    'coupon' => '',
                ];
            }
        }

        $orderInvoice = null;
        $orderProducts = [];
        $orderTotal = $orderTax = $orderShipping = 0;
        $isOrderPaid = 0;

        foreach ($orders as $order) {
            $orderInvoice = $order->getSharedInvoice();
            $orderTotal += $order->getTotal() + $order->getShippingPrice();
            $orderShipping += $order->getShippingPrice();
            $isOrderPaid += $order->getStatus() === 'paid' ? 1 : 0;

            foreach ($order->getOrderProducts() as $orderProduct) {
                $orderTax += $orderProduct->getTaxNominal();
                $product = $orderProduct->getProduct();
                $store = $product ? $product->getStore() : null;
                $orderProducts[] = [
                    'name' => $orderProduct->getOriginalName(),
                    'id' => $orderProduct->getOriginalId(),
                    'price' => (float)$orderProduct->getTotalPrice(),
                    'brand' => $store ? $store->getName() : '',
                    'category' => $product ? $this->getCategoryNameFromProduct($product) : '',
                    'variant' => '',
                    'quantity' => $orderProduct->getQuantity(),
                    'coupon' => '',
                ];
            }
        }

        $isB2gTransaction = $orderRepository->findOneBy(['sharedId' => $sharedInvoice])->getIsB2gTransaction();
        $isDokuEnable = $isB2gTransaction === true;
        $isMidtransEnable = $isB2gTransaction === false && $this->getParameter('is_midtrans_enable');
        $minimumAmountForDoku = $this->getParameter('dokuMinimumTransactionAmount');

        if ($amount < $minimumAmountForDoku) {
            $isDokuEnable = false;
        }

        $session->remove($this->successKey);
        $session->remove($this->invoicesKey);
        $session->remove($this->sharedIdKey);

        if ($session->has($this->b2gSessionKey)) {
            $session->remove($this->b2gSessionKey);
        }

        $this->sendTransactionReportTokoDaring($sharedInvoice);

        return $this->view('@__main__/public/order/success.html.twig', [
            'invoices' => implode(', ', $invoices),
            'shared_id' => $sharedInvoice,
            'order_invoice' => $orderInvoice,
            'order_products' => $orderProducts,
            'order_total' => (float)$orderTotal, // Total transaction value (incl. tax and shipping)
            'order_tax' => (float)$orderTax,
            'order_shipping' => (float)$orderShipping,
            'qris_pay_availability' => $amount <= $amountLimit ? 'available' : 'not-available',
            'is_order_paid' => $isOrderPaid === count($orders),
            'is_doku_enable' => $isDokuEnable,
            'is_midtrans_enable' => $isMidtransEnable,
        ]);
    }

    // Check if order has not been paid before requesting data to QRIS / VA
    public function payWithChannel(LoggerInterface $logger, $channel)
    {
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $sharedInvoice = $this->getRequest()->query->get('id', '');
        $invoice = $this->getRequest()->query->get('invoice', '');

        if (empty($sharedInvoice)
            || !in_array($channel, ['qris', 'virtual-account', 'doku', 'midtrans'])
            || !$session->has($this->qrPayKey)
            || !$session->has($this->vaPayKey)) {
            return $this->redirectToRoute('user_order_index');
        }

        // dd($sharedInvoice, $invoice, $channel);
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);
        /** @var Order[] $orders */
        $orders = $orderRepository->findBy([
            'sharedId' => $sharedInvoice,
            'invoice' => $invoice,
            'status' => 'pending',
        ]);

        // Calculate the amount of the order(s)
        $nominal = $this->getTotalToBePaidForPayment($sharedInvoice, $orders);
//        dd($nominal);
        $QRISPaymentData = null;
        $VAPaymentData = null;
        $midtransPaymentData = null;

        if ($nominal == 0 || count($orders) < 1) {
            return $this->redirectToRoute('homepage');
        }

        if ($channel === 'qris') {
            // If order amount is below the limit, generate qrcode for payment with QRIS
            $amountLimit = $this->getParameter('qris_amount_limit');
            $em = $this->getEntityManager();
            /** @var QrisRepository $qrisRepository */
            $qrisRepository = $this->getRepository(Qris::class);

            if ($nominal <= $amountLimit) {
                do {
                    // TODO: in the future change max parameter into 10 or more digit
                    $billNumber = (string)StaticHelper::generateInt(999, 999999999);
                    $count = $qrisRepository->count(['invoice' => $billNumber]);
                    $found = $count === 0 ? 'yes' : 'no';
                } while ($found === 'no');

                // After getting bill_number value, update related order(s)
                foreach ($orders as $key => $order) {
                    $order->setQRISBillNumber($billNumber);
                    $em->persist($order);
                }

                $em->flush();

                $qris = new QRISClient();
                $qris->setRequestParameters([
                    'billNumber' => $billNumber,
                    'amount' => (string)$nominal,
                ]);

                try {
                    $response = $qris->execute();

                    $logger->error('QRIS response on order creation!', $response);

                    if (!$response['error']) {
                        // Debug purpose
                        $logger->error('QRIS payload on order creation!', $qris->getRequestParameters());
                        $logger->error('QRIS response on order creation!', $response['data']);

                        if (isset($response['data']['errorCode']) && in_array($response['data']['errorCode'], ['IB-1009', 'IB-0500'])) {
                            $QRISPaymentData = ['error' => true];
                        } else {
                            /** @var Qris $qrisData */
                            $qrisData = $qrisRepository->findOneBy(['invoice' => $billNumber]);

                            if (!$qrisData instanceof Qris) {
                                $qrFactory = $this->get(QrCodeGenerator::class);
                                $QRISPaymentData = $response['data'];
                                $QRISPaymentData['qrImage'] = $qrFactory->dataUri($response['data']['qrValue']);

                                $qris = new Qris();
                                $qris->setInvoice($billNumber);
                                $qris->setBillNumber($QRISPaymentData['billNumber']);
                                $qris->setAmount($QRISPaymentData['amount']);
                                $qris->setTotalAmount($QRISPaymentData['totalAmount']);
                                $qris->setQrValue($QRISPaymentData['qrValue']);
                                $qris->setQrImage($QRISPaymentData['qrImage']);
                                $qris->setQrStatus('Belum Terbayar');
                                $qris->setNmid($QRISPaymentData['nmid']);
                                $qris->setMerchantName($QRISPaymentData['merchantName']);
                                $qris->setProductCode($QRISPaymentData['productCode']);
                                $qris->setCreatedDate(date('Y-m-d H:i:s'));
                                $qris->setExpiredDate(date('Y-m-d H:i:s', strtotime($QRISPaymentData['expiredDate'])));

                                $em->persist($qris);
                                $em->flush();
                            }
                        }
                    } else {
                        $QRISPaymentData = ['error' => true];
                    }
                } catch (Exception $e) {
                    $QRISPaymentData = ['error' => true];

                    $logger->error(sprintf('QRIS exception handler from %s!', __METHOD__), ['message' => $e->getMessage()]);
                }
            } else {
                return $this->redirectToRoute('user_order_index');
            }
        } elseif ($channel === 'virtual-account') {
            /** @var User $buyer */
            $buyer = $this->getUser();
            $em = $this->getEntityManager();
            $ids = [];

            foreach ($orders as $key => $order) {
                $ids[] = $order->getId();
            }

            $va = new VirtualAccount();
            $va->setInvoice($orders[0]->getSharedInvoice());
            $va->setTransactionId(implode('|', $ids));
            $va->setReferenceId(Uuid::uuid4()->toString());
            $va->setName(trim($buyer->getFirstName() . ' ' . $buyer->getLastName()));
            $va->setAmount($nominal);
            $va->setInstitution(getenv('WS_BPD_INSTITUTION'));

            // Save first to get the ID
            $em->persist($va);
            $em->flush();

            // INFO: max length is 10 digit
            $va->setBillNumber(substr('0000000000' . $va->getId(), -10));
            $em->persist($va);
            $em->flush();

            try {
                $wsClient = new WSClientBPD();
                $response = $wsClient->billInsertion([
                    'id' => $va->getBillNumber(),
                    'name' => $va->getName(),
                    'nominal' => $nominal,
                    'note_1' => $va->getInvoice(),
                    'note_2' => $va->getReferenceId(),
                    'note_3' => $va->getTransactionId(),
                ]);
            } catch (Exception $e) {
                $response = [
                    'status' => false,
                    'code' => '99',
                    'message' => $e->getMessage(),
                ];
            }

            if ($response['status'] && $response['code'] === '00') {
                $VAPaymentData = $response['data'];

                $va->setRecordId($VAPaymentData[0]['recordId']);
                $va->setPaidDate($VAPaymentData[0]['tgl_upd']);
                $va->setPaidStatus($VAPaymentData[0]['sts_bayar']);
                $va->setKdUser($VAPaymentData[0]['kd_user']);
                $va->setResponse(json_encode($VAPaymentData));

                $em->persist($va);
                $em->flush();

                $logger->error('VA generate success on order creation!', $response);
            } else {
                $VAPaymentData = ['error' => true];

                $logger->error('VA generate error on order creation!', $response);
            }
        }elseif ($channel === 'doku') {

            $dokuMinimumAmount = $this->getParameter('dokuMinimumTransactionAmount');

            if ($nominal < $dokuMinimumAmount) {
                return $this->redirectToRoute('user_order_index');
            }

            $dokuService = $this->get(DokuService::class);
            $dokuInvoiceNumber = $this->generateDokuInvoiceNumber();
            $requestId = $this->generateRequestId();


            $result = $dokuService->requestPayment($dokuInvoiceNumber, $requestId, $orders, $nominal);

            if ($result['status']) {
                $doku = new Doku();
                $requestPaymentStatus = $result['data']['message'][0] === 'SUCCESS' ? 'PENDING': null;
                $responseData = $result['data']['response'];
                $dokuInvoiceNumber = $responseData['order']['invoice_number'];

                $expiredAt = new DateTime($responseData['payment']['expired_date'], new DateTimeZone('Asia/Jakarta'));
                $expiredAt->setTimezone(new DateTimeZone('UTC'));

                $doku->setRequestId($responseData['headers']['request_id']);
                $doku->setStatus($requestPaymentStatus);
                $doku->setAmount($responseData['order']['amount']);
                $doku->setExpiredDate($expiredAt);
                $doku->setInvoiceNumber($dokuInvoiceNumber);
                $doku->setTokenId($responseData['payment']['token_id']);
                $doku->setUrl($responseData['payment']['url']);
                $doku->setUuid($responseData['uuid']);
                $doku->setSharedInvoice($orders[0]->getSharedInvoice());

                $em = $this->getEntityManager();
                $em->persist($doku);
                $em->flush();

                foreach ($orders as $order) {
                    $order->setDokuInvoiceNumber($dokuInvoiceNumber);
                    $em->persist($order);
                    $em->flush();
                }

                return $this->redirect($responseData['payment']['url']);
            }
        }else if ($channel === 'midtrans' && $this->getParameter('is_midtrans_enable')) {

            $midtransService = $this->get(MidtransService::class);

            $orderId = $this->generateMidtransOrderId();

            $result = $midtransService->requestPayment($nominal, $orders, $orderId);

            if ($result['status']) {
                $snapToken = $result['data']['token'];
                $redirectUrl = '/user/order/shared/' .base64_encode('bm-order:'.$orders[0]->getSharedInvoice());

                $midtrans = new Midtrans();
                $midtrans->setStatus('pending');
                $midtrans->setSharedInvoice($orders[0]->getSharedInvoice());
                $midtrans->setToken($snapToken);
                $midtrans->setOrderId($orderId);

                $em = $this->getEntityManager();
                $em->persist($midtrans);
                $em->flush();

                foreach ($orders as $order) {
                    $order->setMidtransId($midtrans->getId());
                    $em->persist($order);
                }

                $em->flush();

                $midtransPaymentData = [
                    'token' => $snapToken,
                    'redirect_url' => $redirectUrl,
                ];
            }
        }

        $session->remove($this->qrPayKey);
        $session->remove($this->vaPayKey);

        return $this->view('@__main__/public/order/pay.html.twig', [
            'channel' => $channel,
            'nominal' => $nominal,
            'qris_payment_data' => $QRISPaymentData,
            'va_payment_data' => $VAPaymentData,
            'shared_id' => $sharedInvoice,
            'midtrans_payment_data' => $midtransPaymentData
        ]);
    }

    public function qrisCallback(LoggerInterface $logger): JsonResponse
    {
        $request = $this->getRequest();
        $response = ['status' => 'Received'];

        if ($request->isMethod('POST')) {
            if (!in_array($this->getClientIp(), $this->allowedIp, false)) {
                $response['status'] = 'IP tidak dikenal!';

                return new JsonResponse($response);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $recordId = $data['recordId'] ?? '';
            $productCode = $data['productCode'] ?? 'NON BILLER';
            $hashcodeKey = $data['hashcodeKey'] ?? '';
            $responseDescription = $data['responseDescription'] ?? '';
            //$qrString = $data['qrString'] ?? '';
            $referenceNumber = $data['reffNumber'] ?? '';
            $billNumber = $data['billNumber'] ?? '';
            $trxID = $data['trxID'] ?? '';
            $trxDate = $data['trxDate'] ?? '';
            $responseCode = $data['responseCode'] ?? '';
            $tempInvoices = [];
            $buyerName = '';
            $buyerEmail = '';

            $logger->error('[POST] QRIS Callback Notification', $data);

            if ($responseCode === '00') {
                $qrisClient = new QRISClient();
                $hash = $qrisClient->generateHashCodeKey([
                    getenv('QRIS_MERCHANT_PAN'),
                    getenv('QRIS_TERMINAL_USER'),
                    'NON BILLER',
                    $billNumber,
                    getenv('QRIS_KEY'),
                ]);

                if (strtoupper($hash) !== $hashcodeKey) {
                    $response['status'] = 'Hashcode tidak valid';

                    return new JsonResponse($response);
                }

                $em = $this->getEntityManager();
                /** @var QrisRepository $qrisRepository */
                $qrisRepository = $this->getRepository(Qris::class);
                /** @var Qris $qris */
                $qris = $qrisRepository->findOneBy([
                    'invoice' => $billNumber,
                    'qrStatus' => 'Belum Terbayar',
                ]);

                if ($qris instanceof Qris) {
                    $qris->setRecordId((int)$recordId);
                    $qris->setTrxId((int)$trxID);
                    $qris->setTrxDate($trxDate);
                    $qris->setTrxStatus('SUCCEED');
                    $qris->setTrxStatusDetail($responseDescription);
                    $qris->setReferenceNumber($referenceNumber);
                    $qris->setResponseCode($responseCode);
                    $qris->setQrStatus('Sudah Terbayar');
                    $qris->setProductCode($productCode);

                    $em->persist($qris);
                    $em->flush();

                    /** @var OrderPaymentRepository $paymentRepository */
                    $paymentRepository = $this->getRepository(OrderPayment::class);
                    /** @var OrderRepository $orderRepository */
                    $orderRepository = $this->getRepository(Order::class);
                    /** @var Order[] $orders */
                    $orders = $orderRepository->findBy(['qrisBillNumber' => $billNumber]);

                    foreach ($orders as $order) {
                        $buyerName = $order->getName();
                        $buyerEmail = $order->getEmail();

                        /** @var Store $store */
                        $store = $order->getSeller();
                        /** @var User $seller */
                        $seller = $store->getUser();
                        /** @var User $buyer */
                        $buyer = $order->getBuyer();

                        $previousOrderValues = clone $order;

                        $order->setStatus('paid');

                        /** @var OrderPayment $payment */
                        $payment = $paymentRepository->findOneBy(['invoice' => $order->getInvoice()]);

                        if (!$payment instanceof OrderPayment) {
                            $payment = new OrderPayment();
                        }

                        $payment->setOrder($order);
                        $payment->setInvoice($order->getInvoice());
                        $payment->setName($buyerName);
                        $payment->setEmail($buyerEmail);
                        $payment->setType('qris');
                        $payment->setAttachment($hashcodeKey);
                        $payment->setNominal($order->getTotal() + $order->getShippingPrice());
                        $payment->setMessage('Pembayaran menggunakan QRIS');
                        $payment->setBankName('bpd_bali');

                        try {
                            $dateParts = explode(' ', $trxDate);
                            $paymentDate = explode('/', $dateParts[0]);
                            $payment->setDate(new DateTime(sprintf('%s-%s-%s', $paymentDate[2], $paymentDate[1], $paymentDate[0])));
                        } catch (Exception $e) {
                        }

                        $notification = new Notification();
                        $notification->setSellerId($seller->getId());
                        $notification->setBuyerId($buyer->getId());
                        $notification->setIsSentToSeller(false);
                        $notification->setIsSentToBuyer(false);
                        $notification->setTitle($this->getTranslation('notifications.order_status'));
                        $notification->setContent($this->getTranslation('notifications.order_status_text', ['%invoice%' => $order->getInvoice(), '%status%' => 'paid']));

                        $this->logOrder($em, $previousOrderValues, $order, $order->getBuyer());

                        $em->persist($order);
                        $em->persist($payment);
                        $em->persist($notification);
                        $em->flush();

                        $tempInvoices[] = $order->getInvoice();
                    }

                    $this->sendEmailAfterSuccessCallback($tempInvoices, $buyerEmail, $buyerName);
                }
            }

            $response = $data;
        }

        return new JsonResponse($response);
    }

    public function vaCallback(LoggerInterface $logger): JsonResponse
    {
        $request = $this->getRequest();
        $response = [
            'data' => false,
            'code' => 0,
            'message' => null,
            'errors' => null,
        ];

        if ($request->isMethod('POST')) {
            if (!in_array($this->getClientIp(), $this->allowedIp, false)) {
                $response['code'] = 403;
                $response['message'] = 'IP tidak dikenal!';
                $response['errors'] = true;

                return new JsonResponse($response);
            }

            $authToken1 = $request->headers->get('cti-auth‐token', 'invalid1');
            $authToken2 = $request->headers->get('cti-auth_token', 'invalid2');
            $validAuthToken = getenv('WS_BPD_AUTH_TOKEN_CALLBACK');
            $validRequest = false;

            if (!empty($authToken1) && $authToken1 === $validAuthToken) {
                $validRequest = true;
            }

            if (!$validRequest && !empty($authToken2) && $authToken2 === $validAuthToken) {
                $validRequest = true;
            }

            if ($validRequest) {
                $data = json_decode(file_get_contents('php://input'), true);

                $logger->error('[POST] VA Callback Notification', $data);

                $data = current($data);

                if ((int)$data[0]['statusBayar'] === 1) {
                    /** @var VirtualAccountRepository $vaRepository */
                    $vaRepository = $this->getRepository(VirtualAccount::class);
                    /** @var VirtualAccount $va */
                    $va = $vaRepository->findOneBy([
                        'billNumber' => $data[0]['noId'],
                        'paidStatus' => '0',
                    ]);

                    if ($va instanceof VirtualAccount) {
                        $va->setPaidDate($data[0]['tglBayar']);
                        $va->setPaidStatus('1');
                        $va->setResponse(json_encode($data));

                        $em = $this->getEntityManager();
                        $em->persist($va);
                        $em->flush();

                        /** @var OrderRepository $orderRepository */
                        $orderRepository = $this->getRepository(Order::class);
                        /** @var OrderPaymentRepository $paymentRepository */
                        $paymentRepository = $this->getRepository(OrderPayment::class);
                        $orders = $orderRepository->findBy(['sharedInvoice' => $va->getInvoice()]);
                        $translator = $this->getTranslator();
                        $tempInvoices = [];
                        $buyerName = '';
                        $buyerEmail = '';

                        foreach ($orders as $order) {
                            $buyerName = $order->getName();
                            $buyerEmail = $order->getEmail();

                            /** @var Store $store */
                            $store = $order->getSeller();
                            /** @var User $seller */
                            $seller = $store->getUser();
                            /** @var User $buyer */
                            $buyer = $order->getBuyer();

                            $previousOrderValues = clone $order;

                            $order->setStatus('paid');

                            /** @var OrderPayment $payment */
                            $payment = $paymentRepository->findOneBy(['invoice' => $order->getInvoice()]);

                            if (!$payment instanceof OrderPayment) {
                                $payment = new OrderPayment();
                            }

                            $payment->setOrder($order);
                            $payment->setInvoice($order->getInvoice());
                            $payment->setName($order->getName());
                            $payment->setEmail($order->getEmail());
                            $payment->setType('virtual_account');
                            $payment->setAttachment($va->getReferenceId());
                            $payment->setNominal($order->getTotal() + $order->getShippingPrice());
                            $payment->setMessage('Pembayaran menggunakan Virtual Account');
                            $payment->setBankName('bpd_bali');

                            try {
                                $payment->setDate(new DateTime('now'));
                            } catch (Exception $e) {
                            }

                            $notification = new Notification();
                            $notification->setSellerId($seller->getId());
                            $notification->setBuyerId($buyer->getId());
                            $notification->setIsSentToSeller(false);
                            $notification->setIsSentToBuyer(false);
                            $notification->setTitle($translator->trans('notifications.order_status'));
                            $notification->setContent($translator->trans('notifications.order_status_text', ['%invoice%' => $order->getInvoice(), '%status%' => 'paid']));

                            $this->logOrder($em, $previousOrderValues, $order, $order->getBuyer());

                            $em->persist($order);
                            $em->persist($payment);
                            $em->persist($notification);
                            $em->flush();

                            $tempInvoices[] = $order->getInvoice();
                        }

                        $this->sendEmailAfterSuccessCallback($tempInvoices, $buyerEmail, $buyerName);

                        $response['data'] = true;
                    }
                }
            }
        }

        return new JsonResponse($response);
    }

    private function orderIsIncomplete(string $error = 'shipping'): RedirectResponse
    {
        switch ($error) {
            case 'address':
                $message = 'message.error.address_not_selected';
                break;
            case 'shipping':
                $message = 'message.error.courier_not_selected';
                break;
            case 'tnc':
                $message = 'message.error.tnc_unchecked';
                break;
            case 'negotiation':
                $message = 'message.error.empty_negotiation_input';
                break;
            case 'tax':
                $message = 'message.error.check_tax';
                break;
            case 'b2g':
                $message = 'message.error.b2g_not_allowed';
                break;
            case 'b2c':
                $message = 'message.error.b2c_not_allowed';
                break;
            case 'b2gLimit':
                $message = 'message.error.b2g_over_limit';
                break;
            case 'b2gLimitPKP':
                $message = 'message.error.b2g_pkp_over_limit';
                break;
            case 'satker':
                $message = 'message.error.invalid_satker';
                break;
            default:
                $message = 'message.error.check_cart';
        }

        $this->addFlash('error', $this->getTranslator()->trans($message));

        return $this->redirectToRoute('cart_checkout');
    }

    public function preProcess()
    {
        $this->isAjaxRequest('POST');

        /** @var SessionInterface $session */
        $session = $this->getSession();
        $merchants = $session->get(getenv('ORDER_CART_KEY'));
        $vouchers = $session->has(getenv('ORDER_VOUCHER_KEY')) ? $session->get(getenv('ORDER_VOUCHER_KEY')) : [];
        $shipping = $session->has(getenv('ORDER_CALCULATION_KEY')) ? $session->get(getenv('ORDER_CALCULATION_KEY')) : [];
        /** @var ProductRepository $productRepository */
        $productRepository = $this->getRepository(Product::class);
        /** @var VoucherRepository $voucherRepository */
        $voucherRepository = $this->getRepository(Voucher::class);
        $productInactive = [];
        $productNoStock = [];
        $voucherUsed = [];
        $response = [
            'status' => true,
            'message' => null,
        ];

        $limit = $this->getParameter('b2gLimitAmountForNonPkpStore');

        $buyer = $this->getUser();

        $isB2g = $buyer->getRole() === 'ROLE_USER_GOVERNMENT';

        $isLkppUser = $isB2g && !empty($buyer->getLkppLpseId());

        $restricted = $this->getParameter('lkpp_restricted_categories');
        $restrictedData = (getenv('APP_URL') === 'https://tokodaring.balimall.id') ? $restricted['prod'][12] : $restricted['stage'][12];

        $category_id = [];
        $category_name = [];
        $restrictedCategoryList = [];


        foreach ($merchants as $merchant) {
            $total = 0;
            $nonPkp = false;
            $hash = $merchant['hash'];

            foreach ($merchant['items'] as $idx => $item) {
                $attr = $item['attributes'];
                $total += (float)$attr['price'] * $item['quantity'];

                if (isset($attr['category_id']) && isset($attr['category'])){
                    $category_id[] = (int) $attr['category_id'];
                    $category_name[(int) $attr['category_id']] = $attr['category'];
                }

                if ($idx === 0) {
                    $nonPkp = $attr['is_pkp'] < 0;
                }

                /** @var Product $product */
                $product = $productRepository->findOneBy([
                    'id' => (int)$attr['image'],
                    'status' => 'publish',
                ]);

                if (!$product instanceof Product) {
                    $productInactive[] = $attr['name'];
                }else {
                    if ($product->getQuantity() < 1 || ($product->getQuantity() - (int) $item['quantity']) < 0) {
                        $productNoStock[] = $attr['name'];
                    }
                }
            }

//            di hide krn b2g kemungkinan ada nego harga produk, dan sudh ada validasi di function proccess
//            if ($isB2g && $nonPkp && count($shipping) > 0 && isset($shipping[$hash])) {
//                $total += $shipping[$hash];
//
//                if ($total > $limit) {
//                    $response['status'] = false;
//                    $response['message'] = $this->getTranslation('message.error.b2g_over_limit');
//
//                    return $this->view('', $response, 'json');
//                }
//            }

        }

        if ($isLkppUser) {
            $isCategoryAllowed = true;


            if (count($category_id) > 0 && count($restrictedData) > 0) {
                foreach ($category_id as $category) {
                    if (!in_array($category, $restrictedData)) {
                        $isCategoryAllowed = false;
                        $restrictedCategoryList[] = $category_name[$category];
                    }
                }
            }

            // if (!$isCategoryAllowed) {
            //     $response['status'] = false;
            //     $response['message'] = $this->getTranslation('order.lkpp_invalid_category', ['%categories%' => implode(', ', $restrictedCategoryList)], 'validators');
            // }
            // dd($response, $isCategoryAllowed, $category_id, $restrictedData);
        }


        foreach ($vouchers as $code => $voucher) {
            if (!$voucherRepository->checkForValidity($code)) {
                $voucherUsed[] = $code;
            }
        }

        if (count($productInactive) > 0) {
            $response['status'] = false;
            $response['message'] = $this->getTranslation('order.inactive_items', ['%products%' => implode(', ', $productInactive)], 'validators');
        } elseif (count($voucherUsed) > 0) {
            $response['status'] = false;
            $response['message'] = $this->getTranslation('order.invalid_vouchers', ['%vouchers%' => implode(', ', $voucherUsed)], 'validators');
        }elseif (count($productNoStock) > 0) {
            $response['status'] = false;
            $response['message'] = $this->getTranslation('order.no_stock_items', ['%products%' => implode(', ', $productNoStock)], 'validators');
        }

        return $this->view('', $response, 'json');
    }

    public function syncToSiAku(Request $request, $id) {
        if ($request->isMethod('POST')) {
        // Example payload – customize as needed
        /** @var OrderRepository $repository */ 
        $repository = $this->getRepository(Order::class); 
        
        /** @var StoreRepository $storeRepository */
        $storeRepository = $this->getRepository(Store::class);
        $parameters = [];
        $order = $repository->getOrderDetail($id, $parameters); 
        $getStore = $storeRepository->getStoreByOwnerId($order['s_ow_id']);
        

        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }
        $json = json_encode([
            "tahun_anggaran" => $order['o_fiscalYear'],
            "id_unit" => 33,
            "nama_paket" => $order['o_jobPackageName'],
            "nominal" => $order['o_total'],
            "keterangan" => $order['o_note'],
            "nip_pelaksana" => $order['u_nip'],
            "nama_pelaksana" => $order['u_firstName'] . ' ' . $order['u_lastName'],
            "npwp_pihak3" => $order['ow_npwp'],
            "id_bank_pihak3" => 1,
            "norek_pihak3" => $getStore[0]['s_nomorRekening'],
            "an_pihak3" => $getStore[0]['s_rekeningName'],
            "nip_ppk" => $order['o_ppk_nip'],
            "nama_ppk" => $order['o_ppk_name'],
        ]);

        $urlAPI = getenv('SIRUPKU_API_URL') . '/insert_bayar';
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authkey' => getenv('SIRUPKU_API_AUTH_KEY'),
            ],
            'body' => $json,
        ];
        // dd($urlAPI, $headers, $json);
        $client = new HttpClientService();
        $response = $client->run($urlAPI, $options, 'POST');
        dd($response);
        return new JsonResponse($response);
    }

    // Optional: return error if method is not POST
    return new JsonResponse(['error' => 'Invalid request method'], 405);
    }

    public function confirmationOrderPpk()
    {
        $request = $this->getRequest();

        $this->denyAccessUnlessGranted('order.order_confirmation' , 'permission');        
        $this->denyAccessUnlessGranted((int) $request->request->get('order_id') , 'order_permission');  
        
        
        $em = $this->getEntityManager();
        $repository = $this->getRepository(Order::class);

        $order_id = $request->request->get('order_id');
        $order = $repository->find($order_id);

        // $order->setStatus('approved_order');
        // $order->setUpdatedAt(new DateTime('now'));
        
        $order->setUpdatedAt();


        if($request->request->get('shipping_type' , 'parsial') == 'direct') {
            $order->setTypeOrder('partial');
            $order->setMasterId(null);
            $order->setStatus('processed');
            $order->setShippingType('direct');
            $order->setInvoice($order->getInvoice());
            $order->setNote($request->request->get('note' , ''));
        } else {
            $order->setStatus('approved_order');
            $order->setShippingType('partial');
        }

        $em->persist($order);
        $em->flush();

        return new JsonResponse([
            'status' => true,
            'message' => 'Order berhasil di konfirmasi'
        ]);
    } 

    private function sendEmailAfterSuccessCallback(array $tempInvoices, string $buyerEmail, string $buyerName): void
    {
        /** @var BaseMail $baseMail */
        $baseMail = $this->get(BaseMail::class);
        $translator = $this->getTranslator();

        //--- Send email notification to buyer
        $mailToBuyer = clone $baseMail;
        $mailToBuyer->setMailSubject($translator->trans('message.info.new_user_payment'));
        $mailToBuyer->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
        $mailToBuyer->setMailRecipient($buyerEmail);
        $mailToBuyer->setMailData([
            'name' => $buyerName,
            'invoices' => $tempInvoices,
            'recipient_type' => 'buyer',
            'link' => $this->generateUrl('user_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToBuyer->send();
        //--- Send email notification to buyer

        //--- Send email notification to admin
        $mailToAdmin = clone $baseMail;
        $mailToAdmin->setMailSubject($translator->trans('message.info.new_user_payment'));
        $mailToAdmin->setMailTemplate('@__main__/email/user_payment_notification.html.twig');
        $mailToAdmin->setToAdmin();
        $mailToAdmin->setMailData([
            'name' => 'Admin',
            'invoices' => $tempInvoices,
            'recipient_type' => 'admin',
            'link' => $this->generateUrl('admin_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $mailToAdmin->send();
        //--- Send email notification to admin
    }

    private function sendTransactionReportTokoDaring($orderSharedId) {
        $orderRepository = $this->getRepository(Order::class);
        $orders = $orderRepository->findBy(['sharedId' => $orderSharedId]);
        $buyer = $this->getUser();

        if (count($orders) > 0) {
            if (
                !empty($buyer->getLkppLpseId())
                && !empty($buyer->getLkppJwtToken())
                && $buyer->getLkppLoginStatus() === 'logged_in'
                && $buyer->getRole() === 'ROLE_USER_GOVERNMENT'
                && $buyer->getIsUserTesting() != true
            ) {
                $tokoDaringService = $this->get(TokoDaringService::class);
                $productId = $orders[0]->getOrderProducts()[0]->getProduct()->getCategory();

                $productCategoryRepository = $this->getRepository(ProductCategory::class);
                $productCategory = $productCategoryRepository->find($productId);

                $res = $tokoDaringService->sendReportTransactionToTokoDaring(['pc_id' => $productCategory->getParentId(), 'orders' => $orders]);

                $status = 'failed';

                $em = $this->getEntityManager();

                if ($res['error'] === false) {
                    $status = 'sent';

                    if (!empty($res['token'])) {
                        $buyer->setLkppJwtToken($res['token']);
                        $em->persist($buyer);
                        $em->flush();
                    }
                }

                foreach ($orders as $order) {
                    $order->setTokoDaringReportStatus($status);
                    $em->persist($order);
                    $em->flush();
                }

            }
        }
    }
}
