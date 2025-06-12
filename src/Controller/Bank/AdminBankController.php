<?php

namespace App\Controller\Bank;

use App\Controller\AdminController;
use DateTime;
use Doctrine\ORM\EntityManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use App\Entity\Bank;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminBankController extends AdminController
{
    protected $key = 'bank';
    protected $entity = Bank::class;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        parent::__construct($authorizationChecker, $translator, $validator);

        $this->authorizedRoles = [
            'ROLE_HELPDESK_USER', 'ROLE_HELPDESK_MERCHANT', 'ROLE_SUPER_ADMIN','ROLE_ACCOUNTING_1',
            'ROLE_ACCOUNTING_2',
        ];
    }

    protected function prepareDataTableFilter(Request $request): void
    {
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'status' => [
                'type' => 'select',
                'choices' => $this->getParameter('active_inactive'),
            ],
            // 'is_used' => [
            //     'type' => 'select',
            //     'choices' => $this->getParameter('yes_no'),
            // ],
            'is_used_start_at' => [
                'type' => 'date',
            ],
            'date_start' => [
                'type' => 'date',
            ],
            'date_end' => [
                'type' => 'date',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['no', 'bank_name', 'status', 'transfer', 'virtual_account', 'actions']);
    }

    protected function actSaveData(Request $request): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $bank = new Bank();
        $bank->setName(filter_var($formData['b_name'], FILTER_SANITIZE_STRING));
        $is_accept_transfer = isset($formData['b_is_accept_transfer']) ? true : false;
        $is_accept_va = isset($formData['b_is_accept_va']) ? true : false;
        $is_active = isset($formData['b_is_active']) ? true : false;
        $bank->setIsAcceptTransfer($is_accept_transfer);
        $bank->setIsAcceptVa($is_accept_va);
        $bank->setIsActive($is_active);
        $bank->setCreatedAt(new DateTime('now'));
        $bank->setBankSlug(str_replace(' ', '_', strtolower($formData['b_name'])));

        $validator = $this->getValidator();
        $bankErrors = $validator->validate($bank);

        if (count($bankErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($bank);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.bank_created', ['%name%' => $bank->getName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $bank->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($bankErrors as $error) {
                $errors['b_'.$error->getPropertyPath()] = $error->getMessage();
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
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $bank = $this->getRepository($this->entity)->find($id);
        $bank->setName(filter_var($formData['b_name'], FILTER_SANITIZE_STRING));
        $is_accept_transfer = isset($formData['b_is_accept_transfer']) ? true : false;
        $is_accept_va = isset($formData['b_is_accept_va']) ? true : false;
        $is_active = isset($formData['b_is_active']) ? true : false;
        $bank->setIsAcceptTransfer($is_accept_transfer);
        $bank->setIsAcceptVa($is_accept_va);
        $bank->setIsActive($is_active);
        $bank->setCreatedAt(new DateTime('now'));
        $bank->setBankSlug(str_replace(' ', '_', strtolower($formData['b_name'])));

        $validator = $this->getValidator();
        $bankErrors = $validator->validate($bank);

        if (count($bankErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($bank);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.bank_updated', ['%name%' => $bank->getName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $bank->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($bankErrors as $error) {
                $errors['b_'.$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    protected function actEditData(int $id)
    {
        /** @var BankRepository $repository */
        $repository = $this->getRepository($this->entity);
        $bankDetail = $repository->find($id);

        return $bankDetail;
    }


    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request, ['order_by' => 'b.id']);


        /** @var BankRepository $repository */
        $repository = $this->getRepository($this->entity);
        $results = $repository->getDataForTable($parameters);
        $total = $results['total'];
        $banks = $results['data'];
        $data = [];
        $no = 1;

        foreach ($banks as $bank) {
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $bank['b_id']]);
            $isActive = !empty($bank['b_is_active']) ? 'label.active' : 'label.inactive';
            $isAcceptTransfer = !empty($bank['b_is_accept_transfer']) ? 'label.yes' : 'label.no';
            $isAcceptVa = !empty($bank['b_is_accept_va']) ? 'label.yes' : 'label.no';

            $buttons = "<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";

            $data[] = [
                $no++,
                $bank['b_name'],
                $translator->trans($isActive),
                $translator->trans($isAcceptTransfer),
                $translator->trans($isAcceptVa),
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
}
