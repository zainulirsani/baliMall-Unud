<?php

namespace App\Controller\Satker;

use App\Controller\AdminController;
use App\Entity\Satker;
use App\Entity\User;
use App\Repository\SatkerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;

class AdminSatkerController extends AdminController
{
    
    protected $key = 'satker';
    protected $entity = Satker::class;
   
    protected function prepareDataTableFilter(Request $request): void
    {
        $this->dataTable->setFilters([
            'keywords' => [
                'type' => 'text',
            ],
            'id_lpse' => [
                'type' => 'number',
            ],
            'id_satker' => [
                'type' => 'text',
            ],
        ]);
    }

    protected function prepareDataTableHeader(): void
    {
        $this->dataTable->setHeaders(['id', 'satker', 'virtual_account', 'pp_name', 'id_lpse', 'id_satker', 'actions']);
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
            'order_by' => 'satker.id'
        ]);
       
         $repository = $this->getRepository(Satker::class);
         $userRepository = $this->getRepository(User::class);
         $result = $repository->getDataForTable($parameters);
         $total = $result['total'];
         $satkers = $result['data'];
         $data = [];

         foreach ($satkers as $satker) {
            $satkerId = (int) $satker['satker_id'];
            $urlEdit = $this->generateUrl($this->getAppRoute('edit'), ['id' => $satker['satker_id']]);
            $urlView = $this->generateUrl($this->getAppRoute('view'), ['id' => $satkerId]);

            $checkbox = '';
            $checkbox = "<input value=\"$satkerId\" type=\"checkbox\" name=\"id[]\" class=\"check-single\">";

            $buttons = "<a href=\"$urlView\" class=\"btn btn-info\">$buttonView</a>";
            $buttons .= "\n<a href=\"$urlEdit\" class=\"btn btn-info\">$buttonEdit</a>";
            $buttons .= "\n<a href=\"javascript:void(0);\" class=\"btn btn-danger confirm-delete\" data-id=\"$satkerId\">$buttonDelete</a>";
            $pp_name = '-';
            $obj_satker = $repository->find($satker['satker_id']);
            if (!empty($obj_satker->getUser())) {
                $pp_name = $obj_satker->getUser()->getUsername();
            }
            $idLpse = $satker['satker_idLpse'];
            $idSatker = $satker['satker_idSatker'];
            $data[] = [
                $checkbox,
                $satker['satker_satkerName'],
                $satker['satker_digitVa'],
                $pp_name,
                $idLpse ?? '-',
                $idSatker ?? '-',
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

    public function create()
    {
        $this->prepareTemplateSection();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);
        $userRepository = $this->getRepository(User::class);
        $user_pp = $userRepository->getAllPpUsers();

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'user_pp' => $user_pp,
        ]);
    }

    protected function actSaveData(Request $request): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }
        $userRepository = $this->getRepository(User::class);

        $formData = $request->request->all();
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());
        
        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);
        $satker = new Satker();
        $satker->setSatkerName(filter_var($formData['satker_name'], FILTER_SANITIZE_STRING));
         // $user_pp = $userRepository->find($formData['user_pp']);
         $satker->setUser(null);
         $satker->setIdLpse($formData['id_lpse']);
         $satker->setIdSatker($formData['id_lpse'] . "-" . $formData['id_satker']);


      
        $validator = $this->getValidator();
        $satkerErrors = $validator->validate($satker);

        if (count($satkerErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($satker);
            $em->flush();

            $satker->setDigitVa(str_pad($satker->getId(), 8, "0", STR_PAD_LEFT));
            $em->persist($satker);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.satker_created', ['%name%' => $satker->getSatkerName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $satker->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($satkerErrors as $error) {
                $errors['satker_'.$error->getPropertyPath()] = $error->getMessage();
            }

            $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
            $flashBag->set('errors', $errors);

            $redirect = $this->generateUrl($this->getAppRoute('create'));
        }

        return $redirect;
    }

    public function edit($id)
    {
        
        $this->prepareTemplateSection();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);
        $userRepository = $this->getRepository(User::class);
        $user_pp = $userRepository->getAllPpUsers();

        $repository = $this->getRepository($this->entity);
        $satkerDetail = $repository->find($id);

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $satkerDetail,
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
            'user_pp' => $user_pp,
        ]);
    }

    protected function actUpdateData(Request $request, $id): string
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $formData = $request->request->all();
        $translator = $this->getTranslator();
        $redirect = $this->generateUrl($this->getAppRoute());
        $userRepository = $this->getRepository(User::class);

        $flashBag = $this->get('session.flash_bag');
        $flashBag->set('form_data', $formData);

        $satker = $this->getRepository($this->entity)->find($id);
        $satker->setSatkerName(filter_var($formData['satker_name'], FILTER_SANITIZE_STRING));
        // $user_pp = $userRepository->find($formData['user_pp']);
        // $satker->setUser($user_pp);
        $satker->setIdLpse($formData['id_lpse']);
        $satker->setIdSatker($formData['id_lpse'] . "-" . $formData['id_satker']);
        
        $validator = $this->getValidator();
        $satkerErrors = $validator->validate($satker);

        if (count($satkerErrors) === 0) {
            $em = $this->getEntityManager();
            $em->persist($satker);
            $em->flush();

            $this->addFlash(
                'success',
                $translator->trans('message.success.satker_updated', ['%name%' => $satker->getSatkerName()])
            );

            if ($formData['btn_action'] === 'save') {
                $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $satker->getId()]);
            }else if ($formData['btn_action'] === 'save_exit') {
                $redirect = $this->generateUrl($this->getAppRoute());
            }
        } else {
            $errors = [];

            foreach ($satkerErrors as $error) {
                $errors['satker_'.$error->getPropertyPath()] = $error->getMessage();
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
        $satkerId = abs($request->get('satker'));
        $satkerRepository = $this->getRepository(Satker::class);
        $satker = $satkerRepository->find($satkerId);
        $response = [
            'status' => false,
            'message' => $this->getTranslator()->trans('message.error.delete', ['%name%' => 'satker']),
        ];

        if ($satker instanceof Satker) {
            // $kldi->setStatus('deleted');

            $em = $this->getEntityManager();
            $em->remove($satker);
            $em->flush();

            $response['status'] = true;
            $response['message'] = $this->getTranslator()->trans('message.success.delete', ['%name%' => $satker->getSatkerName()]);
        }

        return $response;
    }

    protected function actReadData(int $id)
    {
        $satkerRepository = $this->getRepository(Satker::class);
        $satker = $satkerRepository->find($id);

        return $satker;
    }
    protected function executeAction(Request $request, array $ids): void
    {
        if (!$this->isAuthorizedToManage()) {
            throw new AccessDeniedException($this->getTranslator()->trans('message.error.403'));
        }

        $action = $request->request->get('btn_action', 'invalid');
        $satkers = [];
        $proceed = false;
        $sql = null;
        /** @var SatkerRepository $repository */
        $repository = $this->getRepository($this->entity);

        if ($this->isAuthorizedToManage()) {
            foreach ($ids as $key => $id) {
                $id = abs($id);
                $ids[$key] = $id;

                $satker = $repository->find($id);

                if ($satker instanceof Satker) {
                    $satkers[] = $satker->getSatkerName();
                }
            }

            switch ($action) {
                case 'delete':
                    $sql = 'DELETE from App\Entity\Satker t WHERE t.id IN (%s)';
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
                    $this->getTranslator()->trans($success, ['%name%' => implode(', ', $satkers)])
                );
            }
        } else {
            $this->addFlash('error', $this->getTranslator()->trans('message.error.403'));
        }
    }
}
