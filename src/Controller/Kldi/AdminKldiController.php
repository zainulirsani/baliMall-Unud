<?php

namespace App\Controller\Kldi;

use App\Controller\AdminController;
use App\Entity\Kldi;
use App\Repository\KldiRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdminKldiController extends AdminController
{

    protected $key = 'kldi';
    protected $entity = Kldi::class;
   
    protected function prepareDataTableFilter(Request $request): void
    {
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'id_lpse' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'kldi', 'id_lpse', 'actions']);
    }

    protected function prepareDataTableButton(): void
    {
        if($this->isAuthorizedToManage()){
            $buttons = [
                'delete' => [
                    'class' => 'btn-danger',
                ],
            ];
        }
        $this->dataTable->setButtons($buttons);
    }

    protected function actFetchData(Request $request): array
    {
        $translator = $this->getTranslator();
        $buttonView = $translator->trans('button.view');
        $buttonEdit = $translator->trans('button.edit');
        $buttonDelete = $translator->trans('button.delete');

        $parameters = $this->populateParametersForDataTable($request,[
            'order_by' => 'k.id'
        ]);
       
         /** @var KldiRepository $repository */
         $repository = $this->getRepository(Kldi::class);
         $result = $repository->getDataForTable($parameters);
         $total = $result['total'];
         $kldis = $result['data'];
         $data = [];

         foreach ($kldis as $kldi) {
            $kldiId = (int) $kldi['k_id'];
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $kldi['k_id']]);
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $kldiId]);

            $checkbox = '';
            $checkbox = "<input value=\"$kldiId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";

            $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
            $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
            $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$kldiId\">$buttonDelete</a>";

            $data[] = [
                $checkbox,
                $kldi['k_kldi_name'],
                $kldi['k_id_lpse'],
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
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $kldi = new Kldi();
        $kldi->setKldiName(filter_var($formData['k_name'], FILTER_SANITIZE_STRING));
        $kldi->setIdLpse($formData['id_lpse']);
      
        $validator = $this->getValidator();
        $kldiErrors = $validator->validate($kldi);

        if (count($kldiErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($kldi);
            $em->flush();

            // $kldi->setDigitVa(str_pad($kldi->getId(), 8, "0", STR_PAD_LEFT));
            // $em->persist($kldi);
            // $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.kldi_created', ['%name%' => $kldi->getKldiName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $kldi->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($kldiErrors as $error) {
                $errors['k_'.$error->getPropertyPath()] = $error->getMessage();
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
        $kldiDetail = $repository->find($id);

        return $kldiDetail;
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

        $kldi = $this->getRepository($this->entity)->find($id);
        $kldi->setKldiName(filter_var($formData['k_name'], FILTER_SANITIZE_STRING));
        $kldi->setIdLpse($formData['id_lpse']);
        
        $validator = $this->getValidator();
        $kldiErrors = $validator->validate($kldi);

        if (count($kldiErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($kldi);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.kldi_updated', ['%name%' => $kldi->getKldiName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $kldi->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($kldiErrors as $error) {
                $errors['k_'.$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    protected function actDeleteData(): array
    {
        $request = $this->getRequest();
        $kldiId = abs($request->get('kldi'));
        $kldiRepository = $this->getRepository(Kldi::class);
        $kldi = $kldiRepository->find($kldiId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'kldi']),
        ];

        if ($kldi instanceof Kldi) {
            // $kldi->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->remove($kldi);
            $em->flush();

            $response['status'] = true;
            $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $kldi->getKldiName()]);
        }

        return $response;
    }

    protected function actReadData(int $id)
    {
        $kldiRepository = $this->getRepository(Kldi::class);
        $kldi = $kldiRepository->find($id);

        return $kldi;
    }
    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $action = $request->request->get('btn_action', 'invalid');
        $kldis = [];
        $proceed = false;
        $sql = null;
        /** @var KldiRepository $repository */
        $repository = $this->getRepository($this->entity);

        if ($this->isAuthorizedToManage()) {
            foreach ($ids as $key => $id) {
                $id = abs($id);
                $ids[$key] = $id;

                $kldi = $repository->find($id);

                if ($kldi instanceof Kldi) {
                    $kldis[] = $kldi->getKldiName();
                }
            }

            switch ($action) {
                case 'delete':
                    $sql = 'DELETE from App\Entity\Kldi t WHERE t.id IN (%s)';
                    $sql = sprintf($sql, implode(', ', $ids));
                    $proceed = true;
                    break;
            }

            if ($proceed) {
                /** @var EntityManager $em */
                $em = $this->getEntityManager();
                $query = $em->createQuery($sql);
                $query->execute();

                // $this->removeUserStoresDataFromCache();

                $success = sprintf('message.success.%s', $action);

                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans($success, ['%name%' => implode(', ', $kldis)])
                );
            }
        } else {
            $this->addFlash('error', $this->getTranslator()->trans('message.error.403'));
        }
    }
}