<?php

namespace App\Controller\Voucher;

use App\Controller\AdminController;
use App\Entity\Order;
use App\Entity\Voucher;
use App\EventListener\MassVoucherEntityListener;
use App\EventListener\RegenerateQrImageForVoucher;
use App\EventListener\VoucherEntityListener;
use App\Helper\StaticHelper;
use App\Repository\OrderRepository;
use App\Repository\VoucherRepository;
use App\Service\QrCodeGenerator;
use DateTime;
use Doctrine\ORM\EntityManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Intervention\Image\ImageManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminVoucherController extends AdminController
{
    protected $key = 'voucher';
    protected $entity = Voucher::class;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->authorizedRoles = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN_VOUCHER'];
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'is_used' => [
                'type' => 'select',
                'choices' => $this->getParameter('yes_no'),
            ],
            'is_used_start_at' => [
                'type' => 'date',
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
            'jump_to_page' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'number', 'code', 'amount', 'is_used', 'used_by', 'used_at', 'invoice_no', 'order_status', 'start_at', 'end_at', 'created', 'actions']);
    }

    protected function prepareDataTableButton(): void
    {
        if ($this->isAuthorizedToManage()) {
            $buttons = [
                'delete' => [
                    'class' => 'btn-danger',
                ],
                'print.voucher' => [
                    'class' => 'btn-success',
                ],
            ];
        }else {
            $buttons = [
                'print.voucher' => [
                    'class' => 'btn-success',
                ],
            ];
        }

        $this->dataTable->setButtons($buttons);
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        // $buttonEdit = $translator->trans('button.edit');
        $buttonPrint = $translator->trans('button.print.voucher');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'v.id']);

        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();

        if (!empty($parameters['jump_to_page']) && $parameters['offset'] == 0 && $parameters['draw'] == 1) {
            $parameters['draw'] = $parameters['jump_to_page'];
            $parameters['offset'] = ($parameters['draw'] * 10) - 10;
        }
        
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->getRepository(Order::class);
        /** @var VoucherRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $vouchers = $results['data'];
        $data = [];

        foreach ($vouchers as $voucher) {
            $voucherId = (int) $voucher['v_id'];
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $voucherId]);
            //$urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $voucherId]);
            $urlPrint = $this->generateUrl($this->getAppRoute('print'), ['id' => $voucherId]);
            $isUsed = !empty($voucher['vul_id']) ? 'label.yes' : 'label.no';
            $invoice = null;
            $orderStatus = null;
            $numberLimit = strlen((string) $voucherId);

            if (!empty($voucher['vul_orderSharedId'])) {
                /** @var Order $order */
                $order = $orderRepository->findOneBy(['sharedId' => $voucher['vul_orderSharedId']]);
                $invoice = !empty($order) ? $order->getSharedInvoice() : null;
                $orderStatus = !empty($order) ? $order->getStatus() : null;
            }

            $checkbox = '';
            $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
            $buttons .= "\n<a href=\"$urlPrint\" class=\"btn btn-success\" target=\"_blank\">$buttonPrint</a>";

            if ($this->isAuthorizedToManage()) {
                if (empty($voucher['vul_id'])) {
                    $checkbox = "<input value=\"$voucherId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";
                    $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$voucherId\">$buttonDelete</a>";
                }
            }

            if ($numberLimit <= 5) {
                $number = substr('0000'.$voucherId, -5);
            } elseif ($numberLimit === 6) {
                $number = substr('00000'.$voucherId, -6);
            } elseif ($numberLimit === 7) {
                $number = substr('000000'.$voucherId, -7);
            } elseif ($numberLimit === 8) {
                $number = substr('0000000'.$voucherId, -8);
            } elseif ($numberLimit === 9) {
                $number = substr('00000000'.$voucherId, -9);
            } else {
                $number = substr('000000000'.$voucherId, -10);
            }

            $data[] = [
                $checkbox,
                $number,
                $voucher['v_code'],
                StaticHelper::formatForCurrency($voucher['v_amount']),
                $translator->trans($isUsed),
                trim($voucher['u_firstName'].' '.$voucher['u_lastName']),
                !empty($voucher['vul_createdAt']) ? date('d M Y', strtotime($voucher['vul_createdAt'])) : '-',
                $invoice, 
                $orderStatus,
                !empty($voucher['v_startAt']) ? $voucher['v_startAt']->format('d M Y') : '-',
                !empty($voucher['v_endAt']) ? $voucher['v_endAt']->format('d M Y') : '-',
                !empty($voucher['v_createdAt']) ? $voucher['v_createdAt']->format('d M Y H:i') : '-',
                $buttons,
            ];
        }

        return [
            'draw' => $parameters['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    protected function actSaveData(Request $request): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        $multiply = isset($formData['v_multiply']) ? abs($formData['v_multiply']) : 1;
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        if ($multiply > 1) {
            $errors = [];

            if (empty($formData['v_amount'])) {
                $errors['v_amount'] = $translator->trans('global.not_empty', [], 'validators');
            }

            if (empty($formData['v_startAt'])) {
                $errors['v_startAt'] = $translator->trans('global.not_empty', [], 'validators');
            }

            if (empty($formData['v_endAt'])) {
                $errors['v_endAt'] = $translator->trans('global.not_empty', [], 'validators');
            }

            if (count($errors) > 0) {
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);

                return $this->generateUrl($this->getAppRoute('create'));
            }

            $em = $this->getEntityManager();
            $multiply = $multiply >= 100 ? 100 : $multiply;
            $voucherName = !empty($formData['v_name']) ? $formData['v_name'] : 'Voucher';
            $tempCode = StaticHelper::generateStr(8);
            $vouchers = [];

            for ($i = 0; $i < $multiply; $i++) {
                $voucher = new Voucher();
                $voucher->setCode($tempCode.'-'.($i + 1));
                $voucher->setName(filter_var($voucherName.' - '.($i + 1), FILTER_SANITIZE_STRING));
                $voucher->setDescription($formData['v_description']);
                $voucher->setAmount((float) $formData['v_amount']);
                $voucher->setStatus('publish');
                $voucher->setValidFor(['ROLE_USER', 'ROLE_USER_BUYER']);

                try {
                    $voucher->setStartAt(new DateTime($formData['v_startAt']));
                } catch (Exception $e) {
                }

                try {
                    $voucher->setEndAt(new DateTime($formData['v_endAt'].' 23:59:59'));
                } catch (Exception $e) {
                }

                $em->persist($voucher);

                $vouchers[] = $voucher;
            }

            $em->flush();

            $this->appGenericEventDispatcher(new GenericEvent($vouchers, [
                'em' => $em,
                'qrFactory' => $this->get(QrCodeGenerator::class),
                'qrContent' => $this->generateUrl('cart_apply_voucher', ['code' => '--code--'], UrlGeneratorInterface::ABSOLUTE_URL),
            ]), 'app.voucher_save_many', new MassVoucherEntityListener());

            $this->addFlash('success', $translator->trans('message.success.many_voucher_created'));

            return $this->generateUrl($this->getAppRoute());
        }

        $voucher = new Voucher();
        $voucher->setCode(StaticHelper::generateStr(8));
        $voucher->setName(filter_var($formData['v_name'], FILTER_SANITIZE_STRING));
        $voucher->setDescription($formData['v_description']);
        $voucher->setAmount((float) $formData['v_amount']);
        $voucher->setStatus('publish');
        $voucher->setValidFor(['ROLE_USER', 'ROLE_USER_BUYER']);

        if (!empty($formData['v_startAt'])) {
            try {
                $voucher->setStartAt(new DateTime($formData['v_startAt']));
            } catch (Exception $e) {
            }
        }

        if (!empty($formData['v_endAt'])) {
            try {
                $voucher->setEndAt(new DateTime($formData['v_endAt'].' 23:59:59'));
            } catch (Exception $e) {
            }
        }

        $validator = $this->getValidator();
        $voucherErrors = $validator->validate($voucher);

        if (count($voucherErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($voucher);
            $em->flush();

            $this->appGenericEventDispatcher(new GenericEvent($voucher, [
                'em' => $em,
                'qrFactory' => $this->get(QrCodeGenerator::class),
                'qrContent' => $this->generateUrl('cart_apply_voucher', ['code' => '--code--'], UrlGeneratorInterface::ABSOLUTE_URL),
            ]), 'app.voucher_save', new VoucherEntityListener());

            $this->addFlash(
                'success',
                $translator->trans('message.success.voucher_created', ['%name%' => $voucher->getCode()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $voucher->getId()]);
            }
        } else {
            $errors = [];

            foreach ($voucherErrors as $error) {
                $errors['v_'.$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    protected function actUpdateData(Request $request, $id): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        /** @var Voucher $voucher */
        $voucher = $this->getRepository($this->entity)->find($id);
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);

        if ($voucher instanceof Voucher) {
            $voucher->setName(filter_var($formData['v_name'], FILTER_SANITIZE_STRING));
            $voucher->setDescription($formData['v_description']);

            if (!empty($formData['v_startAt'])) {
                try {
                    $voucher->setStartAt(new DateTime($formData['v_startAt']));
                } catch (Exception $e) {
                }
            }

            if (!empty($formData['v_endAt'])) {
                try {
                    $voucher->setEndAt(new DateTime($formData['v_endAt'].' 23:59:59'));
                } catch (Exception $e) {
                }
            }

            $validator = $this->getValidator();
            $voucherErrors = $validator->validate($voucher);

            if (count($voucherErrors) === 0) {
                $em = $this->getEntityManager();
                $em->persist($voucher);
                $em->flush();

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('message.success.voucher_updated', ['%name%' => $voucher->getCode()])
                );

                if ($formData['btn_action'] === 'save_exit') {
                    $redirect = $this->generateUrl($this->getAppRoute());
                }
            } else {
                $errors = [];

                foreach ($voucherErrors as $error) {
                    $errors['v_'.$error->getPropertyPath()] = $error->getMessage();
                }

                $flashBag = $this->get('session.flash_bag');
                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);
            }
        }

        return $redirect;
    }

    protected function actDeleteData(): array
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $request = $this->getRequest();
        $voucherId = abs($request->request->get('voucher', '0'));
        /** @var VoucherRepository $repository */
        $repository = $this->getRepository($this->entity);
        $voucher = $repository->findUnusedVoucher($voucherId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'voucher']),
        ];

        if ($voucher instanceof Voucher) {
            $voucherCode = $voucher->getCode();

            $voucher->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->persist($voucher);
            $em->flush();

            $response['status'] = true;
            $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $voucherCode]);
        }

        return $response;
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        $parameters['admin_merchant_cabang'] = $this->getAdminMerchantCabangProvince();
        $parameters['order_by'] = 'v.id';
        $parameters['sort_by'] = 'DESC';

        /** @var VoucherRepository $repository */
        $repository = $this->getRepository(Voucher::class);
        $data = $repository->getDataToExport($parameters);
        $writer = null;

        if (count($data['data']) > 0) {
            $number = 1;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'No.');
            $sheet->setCellValueByColumnAndRow(2, 1, 'Code');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Name');
            $sheet->setCellValueByColumnAndRow(4, 1, 'Amount');
            $sheet->setCellValueByColumnAndRow(5, 1, 'Is Used');
            $sheet->setCellValueByColumnAndRow(6, 1, 'Used By');
            $sheet->setCellValueByColumnAndRow(7, 1, 'Used At');
            $sheet->setCellValueByColumnAndRow(8, 1, 'Invoice No');
            $sheet->setCellValueByColumnAndRow(9, 1, 'Order Status');
            $sheet->setCellValueByColumnAndRow(10, 1, 'Start At');
            $sheet->setCellValueByColumnAndRow(11, 1, 'End At');
            $sheet->setCellValueByColumnAndRow(12, 1, 'Created At');
            $sheet->setCellValueByColumnAndRow(13, 1, 'Status');
            $sheet->setCellValueByColumnAndRow(14, 1, 'Description');

            foreach ($data['data'] as $item) {
                $sheet->setCellValueByColumnAndRow(1, ($number + 1), $number);
                $sheet->setCellValueByColumnAndRow(2, ($number + 1), $item['v_code']);
                $sheet->setCellValueByColumnAndRow(3, ($number + 1), $item['v_name']);
                $sheet->setCellValueByColumnAndRow(4, ($number + 1), $item['v_amount']);
                $sheet->setCellValueByColumnAndRow(5, ($number + 1), $item['vul_createdAt'] == NULL ? 'No' : 'Yes');
                $sheet->setCellValueByColumnAndRow(6, ($number + 1), $item['vul_createdAt'] == NULL ? '' : $item['u_firstName'].' '.$item['u_lastName']);
                $sheet->setCellValueByColumnAndRow(7, ($number + 1), $item['vul_createdAt'] == NULL ? '' : date('Y-m-d H:i:s', strtotime($item['vul_createdAt'])));
                $sheet->setCellValueByColumnAndRow(8, ($number + 1), $item['o_sharedInvoice'] ?? '');
                $sheet->setCellValueByColumnAndRow(9, ($number + 1), $item['o_status'] ?? '');
                $sheet->setCellValueByColumnAndRow(10, ($number + 1), $item['v_startAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(11, ($number + 1), $item['v_endAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(12, ($number + 1), $item['v_createdAt']->format('Y-m-d H:i:s'));
                $sheet->setCellValueByColumnAndRow(13, ($number + 1), ucfirst($item['v_status']));
                $sheet->setCellValueByColumnAndRow(14, ($number + 1), $item['v_description']);



                $number++;
            }

            $writer = new Xlsx($spreadsheet);
        }

        return $writer;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $action = $request->request->get('btn_action', 'invalid');
        $vouchers = [];
        $proceed = false;
        $sql = null;
        $now = new DateTime('now');
        /** @var VoucherRepository $repository */
        $repository = $this->getRepository($this->entity);

        foreach ($ids as $key => $id) {
            $id = abs($id);
            $voucher = $repository->findUnusedVoucher($id);

            if ($voucher instanceof Voucher) {
                $ids[$key] = $id;
                $vouchers[] = $voucher->getCode();
            } else {
                unset($ids[$key]);
            }
        }

        if ($action === 'print.voucher' && count($ids) > 100) {
            $this->addFlash('error', $this->getTranslator()->trans('message.error.too_many_data', ['%max%' => 100]));
            return;
        }

        switch ($action) {
            case 'delete':
                $sql = 'UPDATE App\Entity\Voucher t SET t.status = \'deleted\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
            case 'activate':
                $sql = 'UPDATE App\Entity\Voucher t SET t.status = \'publish\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
            case 'deactivate':
                $sql = 'UPDATE App\Entity\Voucher t SET t.status = \'draft\', t.updatedAt = \'%s\' WHERE t.id IN (%s)';
                $sql = sprintf($sql, $now->format('Y-m-d H:i:s'), implode(', ', $ids));
                $proceed = true;
                break;
            case 'print.voucher':
                $this->printMany($ids);
                exit;
        }

        if ($proceed) {
            /** @var EntityManager $em */
            $em = $this->getEntityManager();
            $query = $em->createQuery($sql);
            $query->execute();

            $success = sprintf('message.success.%s', $action);

            $this->addFlash(
                'success',
                $this->getTranslator()->trans($success, ['%name%' => implode(', ', $vouchers)])
            );
        }
    }

    public function print($id): Response
    {
        /** @var Voucher $voucher */
        $voucher = $this->getRepository($this->entity)->find($id);

        if (!$voucher instanceof Voucher) {
            return $this->redirectToRoute($this->getAppRoute());
        }

        $pdf = new Dompdf(new Options());
        $pdf->loadHtml($this->renderView('@__main__/admin/voucher/print/voucher.html.twig', [
            'images' => $this->createVoucherImage([$id]),
            'print_many' => false,
        ]));
        $pdf->setPaper('Legal', 'landscape');
        $pdf->render();

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=voucher',
        ]);
    }

    public function fixDuplicate(): RedirectResponse
    {
        $this->deniedAccess();

        /** @var VoucherRepository $repository */
        $repository = $this->getRepository(Voucher::class);
        /** @var Voucher[] $results */
        $results = $repository->findUnusedVouchers();
        $duplicates = [];
        $vouchers = [];

        foreach ($results as $voucher) {
            if (!in_array(strtolower($voucher->getCode()), $duplicates, false)) {
                $duplicates[] = strtolower($voucher->getCode());
            } else {
                $vouchers[] = $voucher;
            }
        }

        $this->appGenericEventDispatcher(new GenericEvent($vouchers, [
            'em' => $this->getEntityManager(),
            'qrFactory' => $this->get(QrCodeGenerator::class),
            'qrContent' => $this->generateUrl('cart_apply_voucher', ['code' => '--code--'], UrlGeneratorInterface::ABSOLUTE_URL),
        ]), 'app.voucher_fix_duplicate', new MassVoucherEntityListener());

        if (count($vouchers) > 0) {
            $this->addFlash('success', $this->getTranslator()->trans('message.success.many_voucher_created'));
        }

        return $this->redirectToRoute($this->getAppRoute());
    }

    public function regenerateQrImage(): RedirectResponse
    {
        $this->deniedAccess();

        $request = $this->getRequest();
        $page = abs($request->query->get('page', '1'));
        $limit = 100;
        $offset = $page <= 1 ? 0 : ($page * $limit) - $limit;

        /** @var VoucherRepository $repository */
        $repository = $this->getRepository(Voucher::class);
        /** @var Voucher[] $vouchers */
        $vouchers = $repository->findBy([], null, $limit, $offset);

        $this->appGenericEventDispatcher(new GenericEvent($vouchers, [
            'qrFactory' => $this->get(QrCodeGenerator::class),
            'qrContent' => $this->generateUrl('cart_apply_voucher', ['code' => '--code--'], UrlGeneratorInterface::ABSOLUTE_URL),
        ]), 'app.regenerate_qr_image', new RegenerateQrImageForVoucher());

        if (count($vouchers) > 0) {
            $this->addFlash('success', $this->getTranslator()->trans('message.success.many_voucher_created'));
        }

        return $this->redirectToRoute($this->getAppRoute());
    }

    private function printMany(array $ids): void
    {
        $pdf = new Dompdf(new Options());
        $pdf->loadHtml($this->renderView('@__main__/admin/voucher/print/voucher.html.twig', [
            'images' => $this->createVoucherImage($ids),
            'print_many' => true,
        ]));
        $pdf->setPaper('Legal', 'landscape');
        $pdf->render();
        $pdf->stream('vouchers.pdf', ['Attachment' => false]);
        exit;
    }

    private function createVoucherImage(array $ids): array
    {
        /** @var VoucherRepository $repository */
        $repository = $this->getRepository($this->entity);

        //$template = 'balimall-coupon.png'; // Original
        $template = 'balimall-coupon-galungan.png'; // Galungan

        $publicDir = $this->getParameter('public_dir_path');
        $baseFile = sprintf('%s/dist/img/%s', $publicDir, $template);
        $regularFont = sprintf('%s/assets/fonts/Roboto/Roboto-Regular.ttf', $publicDir);
        $boldFont = sprintf('%s/assets/fonts/Roboto/Roboto-Black.ttf', $publicDir);

        $manager = new ImageManager(['driver' => 'gd']);
        $vouchers = [];

        foreach ($ids as $id) {
            /** @var Voucher $voucher */
            $voucher = $repository->find($id);

            if (!$voucher instanceof Voucher) {
                continue;
            }

            $img = $manager->make($baseFile);
            $numberLimit = strlen($id);

            // Voucher Number
            if ($numberLimit <= 5) {
                $number = sprintf('No. %s', substr('0000'.$id, -5));
                $img->text($number, 1400, 60, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(40);
                    $font->color('#000');
                });
            } elseif ($numberLimit === 6) {
                $number = sprintf('No. %s', substr('00000'.$id, -6));
                $img->text($number, 1400, 60, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(36);
                    $font->color('#000');
                });
            } elseif ($numberLimit === 7) {
                $number = sprintf('No. %s', substr('000000'.$id, -7));
                $img->text($number, 1400, 60, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(32);
                    $font->color('#000');
                });
            } elseif ($numberLimit === 8) {
                $number = sprintf('No. %s', substr('0000000'.$id, -8));
                $img->text($number, 1400, 60, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(30);
                    $font->color('#000');
                });
            } elseif ($numberLimit === 9) {
                $number = sprintf('No. %s', substr('00000000'.$id, -9));
                $img->text($number, 1400, 50, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(28);
                    $font->color('#000');
                });
            } else {
                $number = sprintf('No. %s', substr('000000000'.$id, -10));
                $img->text($number, 1395, 50, function ($font) use ($regularFont) {
                    $font->file($regularFont);
                    $font->size(26);
                    $font->color('#000');
                });
            }

            // Voucher amount
            $amount = sprintf('Rp. %s,-', StaticHelper::formatForCurrency($voucher->getAmount()));
            $img->text($amount, 41, 135, function ($font) use ($boldFont) {
                $font->file($boldFont);
                $font->size(48);
                $font->color('#4b85cc');
            });

            // Voucher code
            $img->text($voucher->getCode(), 41, 260, function ($font) use ($boldFont) {
                $font->file($boldFont);
                $font->size(48);
                $font->color('#000');
            });

            // Voucher validity
            $start = indonesiaDateFormat($voucher->getStartAt()->format('Y-m-d'));
            $end = indonesiaDateFormat($voucher->getEndAt()->format('Y-m-d'));
            $validity = sprintf('%s - %s', $start, $end);
            $img->text($validity, 41, 570, function ($font) use ($boldFont) {
                $font->file($boldFont);
                $font->size(22);
                $font->color('#000');
            });

            $vouchers[] = (string) $img->encode('data-url');
        }

        return $vouchers;
    }

    protected function manipulateDataPackage(): void
    {
        if (!$this->isAuthorizedToManage()) {
            $this->dataPackage->setAbleToCreate(false);
        }

        $this->dataPackage->setAbleToExport(true);
    }
}
