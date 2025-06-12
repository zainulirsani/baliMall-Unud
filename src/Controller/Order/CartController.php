<?php

namespace App\Controller\Order;

use App\Controller\PublicController;
use App\Entity\Operator;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\Store;
use App\Entity\User;
use App\Entity\UserPpkTreasurer;
use App\Entity\Satker;
use App\Entity\Voucher;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\VoucherRepository;
use App\Service\BreadcrumbService;
use Hashids\Hashids;
use App\Helper\StaticHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartController extends PublicController
{
    private $b2gSessionKey = 'b2g_cart_session_key';

    public function cart()
    {
        /** @var User $user */
        $user = $this->getUser();
        $addresses = !empty($user) ? $user->getAddresses() : [];

        BreadcrumbService::add(['label' => $this->getTranslation('label.cart')]);

        return $this->view('@__main__/public/order/cart.html.twig', [
            'addresses' => $addresses,
        ]);
    }

    public function addItem()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $response = $this->processItem($request);

        return $this->view('', $response, 'json');
    }

    public function updateItem()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $response = $this->processItem($request, 'update');

        return $this->view('', $response, 'json');
    }

    public function removeItem()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $response = $this->processItem($request, 'remove');

        return $this->view('', $response, 'json');
    }

    public function clearCart()
    {
        $this->isAjaxRequest('POST');

        $this->getUserCart()->clear();

        /** @var SessionInterface $session */
        $session = $this->getSession();
        $session->remove(getenv('ORDER_CART_KEY'));
        $session->remove($this->b2gSessionKey);

        $response = ['status' => true];

        return $this->view('', $response, 'json');
    }

    public function checkout()
    {
        $userCart = $this->getUserCart();
        
        if ($userCart->getTotalItem() < 1) {
            return $this->redirectToRoute('cart_index');
        }

        /** @var SessionInterface $session */
        $session = $this->getSession();
        $sessionKey = getenv('ORDER_CART_KEY');
        $voucherSessionKey = getenv('ORDER_VOUCHER_KEY');
        $umkmCategory = '';
        if (!$session->has($sessionKey)) {
            $items = $userCart->getItems();
            $merchants = [];
            foreach ($items as $item) {
                $attr = $item[0]['attributes'];
                $quantity = $item[0]['quantity'];
                if (!array_key_exists($attr['origin'], $merchants)) {

                    $productRepository = $this->getRepository(Product::class);
                    $data_product      = $productRepository->find($attr['image']);
                    if ($data_product instanceof Product) {
                        $stock = $data_product->getQuantity() - $quantity;
                        if ($stock < 0) {

                            $this->addFlash('error', $this->getTranslation('label.no_stock'));
                            $this->getUserCart()->clear();

                            /** @var SessionInterface $session */
                            $session = $this->getSession();
                            $session->remove(getenv('ORDER_CART_KEY'));
                            $session->remove($this->b2gSessionKey);
                            return $this->redirectToRoute('homepage');
                        }
                    }

                    if (isset($attr['vendor_umkm_category']) && !empty($attr['vendor_umkm_category'])) {
                        $umkmCategory = $attr['vendor_umkm_category'];
                    }
                    $merchants[$attr['origin']]['vendor'] = $attr['vendor'];
                    $merchants[$attr['origin']]['origin_id'] = $attr['origin_id'];
                    $merchants[$attr['origin']]['hash'] = $item[0]['hash'];
                    $merchants[$attr['origin']]['items'] = $item;
                } else {
                    $merchants[$attr['origin']]['items'][] = $item[0];
                }
            }

            $session->set($sessionKey, $merchants);
        } else {
            foreach ($userCart->getItems() as $item) {
                $attr = $item[0]['attributes'];
                if (isset($attr['vendor_umkm_category']) && !empty($attr['vendor_umkm_category'])) {
                    $umkmCategory = $attr['vendor_umkm_category'];
                }
            }
            $merchants = $session->get($sessionKey);
        }

        /** @var User $user */
        $user = $this->getUser();
        $addresses = !empty($user) ? $user->getAddresses() : [];
        $taxDocuments = !empty($user) ? $user->getTaxDocuments() : [];
        $picData = !empty($user) ? $user->getUserPicDocuments() : [];
        $ppk_treasurer_data = $this->getRepository(User::class)->getAllTreasurerUsers($user->getLkppLpseId());
        $ppk_data = $this->getRepository(User::class)->getAllPpkUsers($user->getLkppLpseId());
        if ($user->getSubRole() == 'PPK') {
            $userPPK = $this->getRepository(UserPpkTreasurer::class)->findOneBy([
                'userAccount' => $user->getId()
            ]);
            
            $satker_data = $this->getRepository(Satker::class)->findBy([
                // 'user' => $userPPK->getUser(),
                'idLpse' => $user->getLkppLpseId()
            ]);
        } else {
            $satker_data = $this->getRepository(Satker::class)->findBy([
                // 'user' => $user,
                'idLpse' => $user->getLkppLpseId()
            ]);
        }
        $noPhone = !empty($user) ? empty($user->getPhoneNumber()) : false;
        $vouchers = $session->has($voucherSessionKey) ? $session->get($voucherSessionKey) : [];
        
        // dd($user, $ppk_data);
        $tahun_ini = intval(date('Y'));
        $select_year = [];
        for ($i=($tahun_ini + 30); $i > $tahun_ini ; $i--) { 
            $select_year[] = $i;
        }
        for ($i=$tahun_ini; $i > ($tahun_ini - 30); $i--) { 
            $select_year[] = $i;
        }




        $session->remove(getenv('ORDER_CALCULATION_KEY'));

        try {
            $user = $this->getUser();
            $userId = $user->getId();
            $operatorRepository = $this->getRepository(Operator::class);
            $satkerChoices = $operatorRepository->findBy(['owner' => $userId, 'role' => 'ROLE_SATKER']);
        }catch (\Throwable $throwable) {
            $satkerChoices = [];
        }

        BreadcrumbService::add(['label' => $this->getTranslation('button.checkout')]);

        // dd($satker_data, $user->getLkppLpseId());

        return $this->view('@__main__/public/order/checkout_v2.html.twig', [
            'merchants' => $merchants,
            'addresses' => $addresses,
            'tax_documents' => $taxDocuments,
            'pic_data' => $picData,
            'ppk_treasurer_data' => $ppk_treasurer_data,
            'ppk_data' => $ppk_data,
            'satker_data' => $satker_data,
            'no_phone' => $noPhone,
            'vouchers' => $vouchers,
            'satker_choices' => $satkerChoices,
            'umkm_category' => $umkmCategory,
            'select_year' => $select_year,
            'tahun_ini' => $tahun_ini,
        ]);
    }

    public function calculateBackup()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $hash = $request->request->get('hash', null);
        $cost = abs($request->request->get('cost', '0'));
        $withTax = $request->request->get('with_tax', null);
        $grandTotal = abs($request->request->get('grand_total', '0'));
        $grandTotalWithTax = abs($request->request->get('grand_total_with_tax', '0'));
        $skipVoucher = $request->request->get('skip_voucher', 'yes');
        $voucherAmount = 0;
        $processedVoucherAmount = 0;
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $sessionKey = getenv('ORDER_CALCULATION_KEY');
        $voucherSessionKey = getenv('ORDER_VOUCHER_KEY');
        $voucherRestoredSessionKey = getenv('ORDER_VOUCHER_RESTORED_KEY');
        $orderAmountKey = getenv('ORDER_AMOUNT_KEY');
        $response = [
            'status' => false,
            'with_tax' => $withTax,
            'grand_total' => $grandTotal,
            'grand_total_with_tax' => $grandTotalWithTax,
            'voucher_amount' => $voucherAmount,
            'status_grand_total' => '',
            'status_grand_total_with_tax' => '',
        ];

        if (!empty($hash)) {
            $response['status'] = true;

            if ($session->has($orderAmountKey)) {
                $orderAmount = $session->get($orderAmountKey);
                $grandTotal = $orderAmount['total'];
                $grandTotalWithTax = $orderAmount['total_with_tax'];
            }

            if (!$session->has($sessionKey)) {
                $costs = [$hash => $cost];

                $response['grand_total'] = $grandTotal + $cost;
                $response['grand_total_with_tax'] = $grandTotalWithTax + $cost;

                $session->set($sessionKey, $costs);
            } else {
                $costs = $session->get($sessionKey);

                if (array_key_exists($hash, $costs)) {
                    if ($cost > 0) {
                        $tempCost = $cost;

                        if ($cost > $costs[$hash]) {
                            $cost -= $costs[$hash];

                            $response['grand_total'] = $grandTotal + $cost;
                            $response['grand_total_with_tax'] = $grandTotalWithTax + $cost;
                        } else {
                            $cost = $costs[$hash] - $cost;

                            $response['grand_total'] = $grandTotal - $cost;
                            $response['grand_total_with_tax'] = $grandTotalWithTax - $cost;
                        }

                        $costs[$hash] = $tempCost;
                    } else {
                        $costs[$hash] = 0;

                        $response['grand_total'] = $grandTotal - $costs[$hash];
                        $response['grand_total_with_tax'] = $grandTotalWithTax - $costs[$hash];

                        //$costs[$hash] = 0;
                    }

                    $session->set($sessionKey, $costs);
                } else {
                    if ($cost > 0) {
                        $costs[$hash] = $cost;

                        $response['status'] = true;
                        $response['grand_total'] = $grandTotal + $cost;
                        $response['grand_total_with_tax'] = $grandTotalWithTax + $cost;

                        $session->set($sessionKey, $costs);
                    }
                }
            }

            if ($session->has($voucherSessionKey)) {
                $vouchers = $session->get($voucherSessionKey);

                foreach ($vouchers as $idx => $voucher) {
                    $voucherAmount += $voucher['amount'];

                    if (!$voucher['processed']) {
                        $processedVoucherAmount += $voucher['amount'];

                        $vouchers[$idx]['processed'] = true;
                    }
                }

                if ($processedVoucherAmount > 0) {
                    $session->set($voucherSessionKey, $vouchers);
                }

                if ($voucherAmount > 0) {
                    if ($skipVoucher === 'no') {
                        if ($processedVoucherAmount > 0) {
                            $voucherGrandTotal = $response['grand_total'] - $processedVoucherAmount;
                            $voucherGrandTotalWithTax = $response['grand_total_with_tax'] - $processedVoucherAmount;
                        } else {
                            $voucherGrandTotal = $response['grand_total'] - $voucherAmount;
                            $voucherGrandTotalWithTax = $response['grand_total_with_tax'] - $voucherAmount;

                            if ($session->has($voucherRestoredSessionKey)) {
                                $restored = $session->get($voucherRestoredSessionKey);
                                $voucherGrandTotal += $restored['amount'];
                                $voucherGrandTotalWithTax += $restored['amount'];

                                $session->remove($voucherRestoredSessionKey);
                            }
                        }
                    } else {
                        $voucherGrandTotal = $response['grand_total'];
                        $voucherGrandTotalWithTax = $response['grand_total_with_tax'];

                        if ($session->has($voucherRestoredSessionKey)) {
                            $restored = $session->get($voucherRestoredSessionKey);
                            $voucherGrandTotal += $restored['amount'];
                            $voucherGrandTotalWithTax += $restored['amount'];

                            $session->remove($voucherRestoredSessionKey);
                        } else {
                            $voucherGrandTotal = $response['grand_total'] - $voucherAmount;
                            $voucherGrandTotalWithTax = $response['grand_total_with_tax'] - $voucherAmount;
                        }
                    }
                } else {
                    if ($session->has($voucherRestoredSessionKey)) {
                        $restored = $session->get($voucherRestoredSessionKey);
                        $restoredAmount = $restored['amount'];
                        $voucherGrandTotal = $restored['status'] === 'CR' ? $response['grand_total'] - $restoredAmount : $response['grand_total'] + $restoredAmount;
                        $voucherGrandTotalWithTax = $restored['status'] === 'CR' ? $response['grand_total_with_tax'] - $restoredAmount : $response['grand_total_with_tax'] + $restoredAmount;

                        $session->remove($voucherRestoredSessionKey);
                    } else {
                        $voucherGrandTotal = $response['grand_total'];
                        $voucherGrandTotalWithTax = $response['grand_total_with_tax'];
                    }
                }

                $response['voucher_amount'] = $voucherAmount;
                $response['grand_total'] = abs($voucherGrandTotal);
                $response['grand_total_with_tax'] = abs($voucherGrandTotalWithTax);

                if ($voucherAmount <= 0) {
                    $response['status_grand_total'] = '';
                    $response['status_grand_total_with_tax'] = '';
                } else {
                    $response['status_grand_total'] = ($voucherGrandTotal < 0) ? 'CR' : '';
                    $response['status_grand_total_with_tax'] = ($voucherGrandTotalWithTax < 0) ? 'CR' : '';
                }
            }
        }

        return $this->view('', $response, 'json');
    }

    public function calculate()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $hash = $request->request->get('hash', null);
        $cost = abs($request->request->get('cost', '0'));
        // $withTax = $request->request->get('with_tax', null);
        $withTax = 'with';
        $voucherAmount = 0;
        $sessionKey = getenv('ORDER_CALCULATION_KEY');
        $voucherSessionKey = getenv('ORDER_VOUCHER_KEY');
        $orderAmountKey = getenv('ORDER_AMOUNT_KEY');
        $response = [
            'status' => false,
            'with_tax' => $withTax,
            'grand_total' => 0,
            'grand_total_with_tax' => 0,
            'tax_nominal' => 0,
            'voucher_amount' => $voucherAmount,
            'status_grand_total' => '',
            'status_grand_total_with_tax' => '',
            'b2gNonPkp' => false,
        ];

        // if ($this->getUser() && $this->getUser()->getRole() === 'ROLE_USER_GOVERNMENT') {
        //     foreach ($this->getUserCart()->getItems() as $cartItems) {
        //         foreach ($cartItems as $item) {
        //             if ($item['hash'] === $hash && $item['attributes']['is_pkp'] < 0) {
        //                 $response['with_tax'] = 'without';
        //                 $response['b2gNonPkp'] = true;
        //                 break;
        //             }
        //         }
        //     }
        // }

        if (!empty($hash) && $session->has($orderAmountKey)) {
            $orderAmount = $session->get($orderAmountKey);
            $grandTotal = $orderAmount['total'];
            $tax = $this->getParameter('tax_value');
            $grandTotalWithTax = $grandTotal + (($tax/100) * $grandTotal);
            //$grandTotalWithTax = $orderAmount['total_with_tax'];
            $vouchers = $session->has($voucherSessionKey) ? $session->get($voucherSessionKey) : [];

            if (!$session->has($sessionKey)) {
                $costs = [$hash => $cost];

                $session->set($sessionKey, $costs);
            } else {
                $costs = $session->get($sessionKey);

                if (array_key_exists($hash, $costs)) {
                    $costs[$hash] = ($cost > 0) ? $cost : 0;

                    $session->set($sessionKey, $costs);
                } else {
                    if ($cost > 0) {
                        $costs[$hash] = $cost;

                        $session->set($sessionKey, $costs);
                    }
                }
            }

            foreach ($vouchers as $idx => $voucher) {
                $voucherAmount += $voucher['amount'];
            }

            // (grand_total + shipping_cost) - voucher_amount
            $totalShipping = array_sum($costs);
            $subTotal = ($grandTotal + $totalShipping) - $voucherAmount;
            $subTotalWithTax = ($grandTotalWithTax + $totalShipping) - $voucherAmount;

            if ($withTax === 'with') {
                $response['tax_nominal'] = $subTotalWithTax - $subTotal;
            }

            $freeTaxForCategoryList = $this->getParameter('free_tax_for_category');
            $freeTaxNominal = 0;

            foreach ($this->getUserCart()->getItems() as $cartItems) {
                foreach ($cartItems as $item) {
                    if (isset($item['attributes']['category_id'])) {
                        if (in_array($item['attributes']['category_id'], $freeTaxForCategoryList, false)) {
                            $freeTaxNominal += (float)$item['attributes']['tax_nominal'];
                        }
                    }
                }
            }

            $response['status'] = true;
            $response['voucher_amount'] = $voucherAmount;
            $response['grand_total'] = abs($subTotal);
            $response['grand_total_with_tax'] = abs($subTotalWithTax);
            $response['status_grand_total'] = ($subTotal < 0) ? 'CR' : '';
            $response['status_grand_total_with_tax'] = ($subTotalWithTax < 0) ? 'CR' : '';
            $response['free_tax_nominal'] = $freeTaxNominal;
        }

        return $this->view('', $response, 'json');
    }

    public function applyVoucher($code)
    {
        $request = $this->getRequest();
        /** @var User $user */
        $user = $this->getUser();
        $response = [
            'status' => false,
            'message' => $this->getTranslation('messages.info.voucher_need_login'),
            'vouchers' => [],
        ];

        if (empty($user)) {
            if ($request->isXmlHttpRequest()) {
                return $this->view('', $response, 'json');
            }

            $this->addFlash('error', $response['message']);

            return $this->redirectToRoute('cart_index');
        }

        /** @var VoucherRepository $repository */
        $repository = $this->getRepository(Voucher::class);
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $sessionKey = getenv('ORDER_VOUCHER_KEY');
        $restoredSessionKey = getenv('ORDER_VOUCHER_RESTORED_KEY');
        $calculationSessionKey = getenv('ORDER_CALCULATION_KEY');
        $vouchers = [];

        if (!$session->has($sessionKey)) {
            if ($voucher = $repository->checkForValidity($code)) {
                $validFor = json_decode($voucher['v_validFor'], true);

                if (in_array($user->getRole(), $validFor, false)) {
                    $vouchers = [
                        $voucher['v_code'] => [
                            'valid' => $voucher['v_id'],
                            'amount' => $voucher['v_amount'],
                            'amount_formatted' => StaticHelper::formatForCurrency($voucher['v_amount']),
                            'processed' => false,
                        ]
                    ];

                    $session->set($sessionKey, $vouchers);
                }
            }
        } else {
            $vouchers = $session->get($sessionKey);
            $userCart = $this->getUserCart();
            $grandTotal = $userCart->getAttributeTotal();
            $costs = $session->has($calculationSessionKey) ? $session->get($calculationSessionKey) : [];
            $totalCost = 0;
            $totalVoucher = 0;
            $isPkpTransaction = $userCart->isPkpTransaction();

            foreach ($costs as $cost) {
                $totalCost += $cost * 1.1;
            }

            if ($isPkpTransaction) {
                $grandTotal = $userCart->getAttributeTotalWithTax();
            }

            foreach ($vouchers as $voucher) {
                $totalVoucher += $voucher['amount'];
            }

            // Check if voucher amount is equal or greater than order total amount
            if ($totalVoucher >= ($grandTotal + $totalCost)) {
                $voucherExceedMessage = $this->getTranslation('message.info.voucher_exceed');

                if ($request->isXmlHttpRequest()) {
                    $response['status'] = false;
                    $response['message'] = $voucherExceedMessage;
                    $response['vouchers'] = $vouchers;

                    return $this->view('', $response, 'json');
                }

                $this->addFlash('error', $voucherExceedMessage);

                return $this->redirectToRoute('cart_checkout');
            }

            if (!array_key_exists($code, $vouchers) && $voucher = $repository->checkForValidity($code)) {
                $validFor = json_decode($voucher['v_validFor'], true);

                if (in_array($user->getRole(), $validFor, false)) {
                    $vouchers[$code] = [
                        'valid' => $voucher['v_id'],
                        'amount' => $voucher['v_amount'],
                        'amount_formatted' => StaticHelper::formatForCurrency($voucher['v_amount']),
                        'processed' => false,
                    ];

                    $session->set($sessionKey, $vouchers);
                }
            }
        }

        if (!$session->has($restoredSessionKey)) {
            $session->set($restoredSessionKey, [
                'status' => 'D',
                'amount' => 0,
            ]);
        }

        if ($request->isXmlHttpRequest()) {
            $response['status'] = $vouchers ? true : false;
            $response['message'] = $vouchers ? null : $this->getTranslation('message.info.voucher_expired');
            $response['vouchers'] = $vouchers;

            return $this->view('', $response, 'json');
        }

        return $this->redirectToRoute('cart_checkout');
    }

    public function removeVoucher()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $code = $request->request->get('code', null);
        $status = $request->request->get('status', null);
        /** @var SessionInterface $session */
        $session = $this->getSession();
        $sessionKey = getenv('ORDER_VOUCHER_KEY');
        $restoredSessionKey = getenv('ORDER_VOUCHER_RESTORED_KEY');
        $response = ['deleted' => false];

        if (!empty($code) && $session->has($sessionKey)) {
            $vouchers = $session->get($sessionKey);

            if (array_key_exists($code, $vouchers)) {
                $restoredAmount = $vouchers[$code]['amount'];
                unset($vouchers[$code]);

                $session->set($sessionKey, $vouchers);
                $session->set($restoredSessionKey, [
                    'status' => $status === 'CR' ? 'CR' : 'D',
                    'amount' => $restoredAmount,
                ]);

                $response['deleted'] = true;
            }
        }

        return $this->view('', $response, 'json');
    }

    private function processItem(Request $request, string $type = 'add'): array
    {
        $response = ['status' => false];
        $quantity = abs($request->request->get('quantity', '0'));
        $quantity = $quantity < 1 ? 1 : $quantity;
        $hash = $request->request->get('hash', null);
        //$withTax = abs($request->request->get('with_tax', 0));
        $taxValue = $this->getParameter('tax_value');
        $productId = 0;
        $template = '@__main__/public/order/fragments/cart_in_header.html.twig';
        /** @var SessionInterface $session */
        $session = $this->getSession();

        if (!empty($hash)) {
            $encoder = new Hashids('BaliMallProduct', 16);
            $productId = current($encoder->decode($hash));
        }

        /** @var ProductRepository $repository */
        $repository = $this->getRepository(Product::class);
        /** @var Product $product */
        $product = $repository->findOneBy([
            'id' => $productId,
            'status' => 'publish',
        ]);

        $user = $this->getUser();

        try {
            $isB2g = $user->getRole() === 'ROLE_USER_GOVERNMENT';
        }catch (\Throwable $throwable) {
            $isB2g = false;
        }

//        if ($user && $user->getLkppRole() !== 'PP' && $user->getLkppRole() !== 'PPK') {
//            $response['message'] = $this->getTranslation('message.error.add_to_cart_forbidden');
//
//            return $response;
//        }

        $operatorRepository = $this->getRepository(Operator::class);

        try {
            $operator = $operatorRepository->count(['owner' => $user->getId()]);
        }catch (\Throwable $throwable) {
            $operator = null;
        }

//        if (!empty($user) && empty($operator)) {
//            $response['message'] = $this->getTranslation('message.error.no_workunit');
//
//            return $response;
//        }

        if ($product instanceof Product) {
            $maxQty = $product->getQuantity();

            if ($maxQty < 1 && $type !== 'remove') {
                $response['message'] = $this->getTranslation('label.no_stock');

                return $response;
            }

            $userCart = $this->getUserCart();
            // $withTax = $this->checkProductWithTaxByPKP($product);
            $withTax = true;
            $isPkp = $withTax;

            // if (($isB2g && $isPkp) || (!$isB2g && $isPkp)) {
            //     $withTax = true;
            // }

            $pkpCheck = $isPkp ? 'pkp' : 'non-pkp';
            $umkmCategory = $product->getStore()->getUMKMCategory() != null && $product->getStore()->getUMKMCategory() != '' ? $product->getStore()->getUMKMCategory() : '';

            if ($type == 'add' || $type == 'update') {
                if ($session->has($this->b2gSessionKey)) {
                    $prevent = $session->get($this->b2gSessionKey);

                    if (($pkpCheck === 'pkp' && $prevent['current'] !== $pkpCheck)
                        || ($pkpCheck === 'non-pkp' && $prevent['current'] !== $pkpCheck)) {
                        $response['message'] = $this->getTranslation($prevent['message']);

                        return $response;
                    }
                    if (isset($prevent['umkmCategory']) && $prevent['umkmCategory'] !== $umkmCategory) {
                        $response['message'] = $this->getTranslation($prevent['message_umkm']);

                        return $response;
                    }
                } else {
                    $session->set($this->b2gSessionKey, [
                        'current' => $pkpCheck,
                        'umkmCategory' => $umkmCategory,
                        'message_umkm' => 'message.info.b2g_cart_umkm_different',
                        'message' => $isPkp ? 'message.info.b2g_cart_is_pkp' : 'message.info.b2g_cart_is_non_pkp',
                    ]);
                }
            }

            $freeTaxForCategoryList = $this->getParameter('free_tax_for_category');

            $freeTax = false;

            if (in_array($product->getCategory(), $freeTaxForCategoryList, false)) {
                $freeTax = true;
            }

            $attributes = [
                'image' => $productId,
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'price' => $product->getPrice(),
                'weight' => ($product->getWeight() > 0) ? $product->getWeight() : 1,
                'unit' => $product->getUnit(),
                'max_qty' => $maxQty,
                'origin' => 0,
                'origin_id' => 0,
                'vendor' => 'N/A',
                'vendor_slug' => 'n-a',
                'vendor_couriers' => [],
                // Set to -1 because array_filter will remove: false, null, 0
                'with_tax' => $withTax ? 1 : -1,
                'tax_value' => $withTax ? $taxValue : -1,
                'tax_nominal' => -1,
                'is_pkp' => $isPkp ? 1 : -1,
                'category' => $this->getCategoryNameFromProduct($product),
                'category_id' => $product->getCategory(),
                'free_tax' => $freeTax ? 1 : -1,
            ];

            if ($quantity >= $maxQty) {
                $quantity = $maxQty;
            }

            if ($withTax && $taxValue > 0) {
                $attributes['tax_nominal'] = ($quantity * $product->getPrice()) * ($taxValue / 100);
            }

            if ($product->getStore() instanceof Store) {
                /** @var Store $store */
                $store = $product->getStore();
                /** @var User $owner */
                $owner = $store->getUser();
                /** @var User $user */
                $user = $this->getUser();

                // Note here because PHPStan will always detect below line as an error (it is intended)
                // If $user and $owner are the same, then they are not allowed to buy their own product
                if ($user instanceof User
                    && $owner instanceof User
                    && (int) $user->getId() === (int) $owner->getId()) {
                    return $response;
                }

                $attributes['origin'] = $store->getId();
                $attributes['origin_id'] = ((int) $store->getCityId() > 0) ? $store->getCityId() : $store->getProvinceId();
                $attributes['vendor'] = $store->getName();
                $attributes['vendor_umkm_category'] = !empty($store->getUmkmCategory()) ? $store->getUmkmCategory() : 'usaha_mikro';
                $attributes['vendor_slug'] = $store->getSlug();
                $attributes['vendor_couriers'] = $store->getDeliveryCouriers();
                $attributes['is_pkp'] = $isPkp ? 1 : -1;
            }

            if ($type === 'add' && $quantity > 0) {

                if ($isB2g && !$isPkp) {
                    $userCart->add($hash, $quantity, $attributes);

                    // if ($this->isB2gOverLimit('b2gLimitAmountForNonPkpStore')) {
                    //     $userCart->remove($hash);

                    //     $response['message'] = $this->getTranslator()->trans('message.error.b2g_over_limit');

                    //     return $response;
                    // }
                }

                if ($isB2g && $isPkp) {
                    $userCart->add($hash, $quantity, $attributes);

                    if ($this->isB2gOverLimit('b2gLimitAmountForPkpStore')) {
                        $userCart->remove($hash);

                        $response['message'] = $this->getTranslator()->trans('message.error.b2g_pkp_over_limit');

                        return $response;
                    }
                }

                $userCart->remove($hash);

                $response['status'] = $userCart->add($hash, $quantity, $attributes);
                $response['item'] = $this->getItemDetail($hash, $quantity, $attributes);
            } elseif ($type === 'update') {

                if ($isB2g && !$isPkp) {
                    $userCart->remove($hash);
                    $userCart->add($hash, $quantity, $attributes);

                    // if ($this->isB2gOverLimit('b2gLimitAmountForNonPkpStore')) {
                    //     $userCart->remove($hash);
                    //     $userCart->add($hash, 1, $attributes);

                    //     $response['message'] = $this->getTranslator()->trans('message.error.b2g_over_limit');

                    //     return $response;
                    // }
                }

                if ($isB2g && $isPkp) {
                    $userCart->remove($hash);
                    $userCart->add($hash, $quantity, $attributes);

                    if ($this->isB2gOverLimit('b2gLimitAmountForPkpStore')) {
                        $userCart->remove($hash);
                        $userCart->add($hash, 1, $attributes);

                        $response['message'] = $this->getTranslator()->trans('message.error.b2g_pkp_over_limit');

                        return $response;
                    }
                }

                $userCart->remove($hash);

                $response['status'] = $userCart->add($hash, $quantity, $attributes);
                $response['item'] = $this->getItemDetail($hash, $quantity, $attributes);

                //$response['status'] = $userCart->update($hash, $quantity, $attributes);
                //$response['item'] = $item;
            } elseif ($type === 'remove') {
                $response['status'] = $userCart->remove($hash);

                if ($userCart->getTotalItem() < 1) {
                    $session->remove($this->b2gSessionKey);
                }
            }

            $response['total_items'] = $userCart->getTotalItem();
            $response['grand_total'] = $userCart->getAttributeTotal();
            $response['grand_total_formatted'] = sprintf('Rp. %s', StaticHelper::formatForCurrency($userCart->getAttributeTotal()));
            $response['template'] = $this->renderView($template, ['user_cart' => $userCart]);

            $orderAmountKey = getenv('ORDER_AMOUNT_KEY');

            if ($session->has($orderAmountKey)) {
                $orderAmount = $session->get($orderAmountKey);
                // Untuk Mendapatkan harga/nilai Pajak Di Item Sebelumnya
                $oldTaxItem = $orderAmount['total_with_tax'] - $orderAmount['total'];

                $session->set(getenv('ORDER_AMOUNT_KEY'), [
                    'total' => $response['grand_total'],
                    'total_with_tax' => $withTax ? $oldTaxItem + $response['grand_total'] + $attributes['tax_nominal'] : $response['grand_total'],
                ]);
            } else {
                $session->set(getenv('ORDER_AMOUNT_KEY'), [
                    'total' => $response['grand_total'],
                    'total_with_tax' => $withTax ? $response['grand_total'] + $attributes['tax_nominal'] : $response['grand_total'],
                ]);
            }

            $session->remove(getenv('ORDER_CART_KEY'));
        }

        return $response;
    }

    private function isB2gOverLimit($parameter) : bool
    {
        $is_pkp = $parameter == 'b2gLimitAmountForNonPkpStore' ? -1 : 1;
        $limit  = $this->getParameter($parameter);
        $tempTotal = $this->getUserCart()->getAttributeTotalStore('price',$is_pkp);
        $grandTotalWithTax = $tempTotal; // Without tax
        //$grandTotalWithTax = $tempTotal + ($tempTotal * 0.1); // With tax

        if ($grandTotalWithTax > $limit) {
            return true;
        }

        return false;
    }

    private function checkProductWithTax(Product $product): bool
    {
        $categories = explode(',', $product->getCategory());
        /** @var ProductCategoryRepository $repository */
        $repository = $this->getRepository(ProductCategory::class);
        /** @var ProductCategory $productCategory */
        $productCategory = $repository->getCategoryFromProduct($categories);

        return !empty($productCategory) ? $productCategory->getWithTax() : false;
    }

    private function checkProductWithTaxByPKP(Product $product): bool
    {
        /** @var Store $store */
        $store = $product->getStore();

        return $store->getIsPKP();
    }

    private function getItemDetail(string $hash, int $quantity, array $attributes): array
    {
        $userCart = $this->getUserCart();
        $item = $userCart->getItem($hash, $attributes);

        if ($item) {
            $price = $item['attributes']['price'];
            $withTax = $item['attributes']['with_tax'];
            $taxValue = $item['attributes']['tax_value'];
            $taxNominal = $item['attributes']['tax_nominal'];

            $item['quantity'] = $quantity;
            $item['attributes']['with_tax'] = $withTax === 1;
            $item['attributes']['tax_value'] = $taxValue > 0 ? $taxValue : 0;
            $item['attributes']['tax_nominal'] = $taxNominal > 0 ? $taxNominal : 0;
            $item['attributes']['total_price'] = StaticHelper::formatForCurrency($quantity * $price);
        }

        return $item;
    }
}
