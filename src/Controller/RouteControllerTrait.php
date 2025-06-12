<?php

namespace App\Controller;

use App\Repository\BaseEntityRepository;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

trait RouteControllerTrait
{
    public function index()
    {
        /** @var Request $request */
        $request = $this->getRequest();

        $this->prepareTemplateSection();
        $this->prepareDataTable($request);
        // dd($this->dataTable, $request->query->all());

        return $this->view(sprintf($this->templates['index'], $this->sections['index'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $this->key),
            'token_id' => sprintf('%s_action', $this->key),
            'data_table' => $this->dataTable,
            'query_params' => $request->query->all(),
        ]);
    }

    public function create()
    {
        $this->prepareTemplateSection();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_create', $this->key);

        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $flashBag->get('form_data'),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
        ]);
    }

    public function save()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $redirect = $this->generateUrl($this->getAppRoute('create'));

        if ($request->isMethod('POST')) {
            $redirect = $this->actSaveData($request);
        }

        return $this->redirect($redirect);
    }

    public function read($id)
    {
        $this->prepareTemplateSection();

        $formData = $this->actReadData($id);

        if (!$formData) {
            /** @var TranslatorInterface $translator */
            $translator = $this->getTranslator();

            throw $this->createNotFoundException($translator->trans('message.error.404'));
        }
        // dd($formData);
        return $this->view(sprintf($this->templates['view'], $this->sections['view'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s_view', $this->key),
            'form_data' => $formData,
        ]);
    }

    public function edit($id)
    {
        $this->prepareTemplateSection();

        $formData = $this->actEditData($id);

        if (!$formData) {
            /** @var TranslatorInterface $translator */
            $translator = $this->getTranslator();

            throw $this->createNotFoundException($translator->trans('message.error.404'));
        }

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_edit', $this->key);
        // dd($formData);
        return $this->view(sprintf($this->templates['form'], $this->sections['form'] ?? 'default'), [
            'page_title' => sprintf('title.page.%s', $section),
            'form_data' => $formData,
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
        ]);
    }

    public function update($id)
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $redirect = $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);

        if ($request->isMethod('POST')) {
            $redirect = $this->actUpdateData($request, $id);
        }

        return $this->redirect($redirect);
    }

    public function delete()
    {
        $this->isAjaxRequest('POST');

        $response = $this->actDeleteData();

        return $this->view('', $response, 'json');
    }

    public function action()
    {
        /** @var Request $request */
        $request = $this->getRequest();

        $ids = (array) $request->request->get('id', null);
        $route = $this->getAppRoute();

        if (count($ids) < 1) {
            if ($request->getMethod() === 'POST') {
                /** @var TranslatorInterface $translator */
                $translator = $this->getTranslator();

                $this->addFlash('warning', $translator->trans('message.error.no_data'));
            }
        } else {
            $this->executeAction($request, $ids);
        }

        if (!empty($this->roleParam)) {
            return $this->redirectToRoute($route, ['role' => $this->roleParam]);
        }

        return $this->redirectToRoute($route);
    }

    public function import()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session.flash_bag');
        $section = sprintf('%s_import', $this->key);

        if ($request->isMethod('POST')) {
            /** @var ValidatorInterface $validator */
            $validator = $this->getValidator();
            $file = $request->files->get('file_doc', null);
            $violations = $validator->validate($file, [
                new Constraints\NotBlank(),
                new Constraints\File([
                    'maxSize' => $this->getParameter('max_upload_file'),
                    'mimeTypes' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                ]),
            ]);

            if (count($violations) === 0) {
                $type = 'warning';
                $message = 'No data was imported, please check your format!';

                if ($this->actImportData($file)) {
                    $type = 'success';
                    $message = 'Data imported!';
                }

                $flashBag->set($type, $message);
            } else {
                $errors = [];

                foreach ($violations as $error) {
                    $errors['file_doc'] = $error->getMessage();
                }

                $flashBag->set('warning', $this->getTranslator()->trans('message.info.check_form'));
                $flashBag->set('errors', $errors);
            }

            return $this->redirectToRoute($this->getAppRoute('import'));
        }

        return $this->view(sprintf($this->templates['import'], $this->sections['import'] ?? 'default'), [
            'page_title' => sprintf('title.import.%s', $this->key),
            'errors' => $flashBag->get('errors'),
            'token_id' => $section,
        ]);
    }

    public function export(): RedirectResponse
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $parameters = $request->isMethod('POST') ? $request->request->all() : $request->query->all();
        $writer = $this->actExportData($parameters);
        $fileName = sprintf('%s_export.xlsx', $this->key);

        if ($writer instanceof Xlsx) {
            try {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="'.$fileName.'"');
                $writer->save('php://output');
                exit;
            } catch (Exception $e) {
            }
        }

        return $this->redirectToRoute($this->getAppRoute());
    }

    public function fetchData()
    {
        $this->isAjaxRequest('POST');
        //$this->prepareDataTable();

        $request = $this->getRequest();
        $response = $this->actFetchData($request);

        return $this->view('', $response, 'json');
    }

    protected function prepareTemplateSection(): void
    {
        $this->sections = [
            'index' => 'default',
            'view' => $this->key,
            'form' => $this->key,
            'import' => 'default',
        ];
    }

    protected function prepareDataTable(Request $request): void
    {
        $this->prepareDataTableFilter($request);
        $this->prepareDataTableHeader();
        $this->prepareDataTableButton();
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
        $this->dataTable->setHeaders(['id', 'name', 'status', 'created', 'updated', 'actions']);
    }

    protected function prepareDataTableButton(): void
    {
        $this->dataTable->setButtons([
            'activate' => [],
            'deactivate' => [],
            'delete' => [
                'class' => 'btn-danger',
            ],
        ]);
    }

    protected function actFetchData(Request $request): array
    {
        return [
            'draw' => 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];
    }

    protected function actSaveData(Request $request): string
    {
        return $this->generateUrl($this->getAppRoute());
    }

    protected function actReadData(int $id)
    {
        /** @var BaseEntityRepository $repository */
        $repository = $this->getRepository($this->entity);

        return $repository->getDataById($id);
    }

    protected function actEditData(int $id)
    {
        /** @var BaseEntityRepository $repository */
        $repository = $this->getRepository($this->entity);

        return $repository->getDataById($id);
    }

    protected function actUpdateData(Request $request, int $id): string
    {
        return $this->generateUrl($this->getAppRoute('edit'), ['id' => $id]);
    }

    protected function actDeleteData(): array
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getTranslator();

        return [
            'status' => false,
            'message' => $translator->trans('message.error.delete', ['%name%' => 'data']),
        ];
    }

    protected function actImportData(UploadedFile $file): bool
    {
        return false;
    }

    protected function actExportData(array $parameters = []): ?Xlsx
    {
        return null;
    }

    protected function executeAction(Request $request, array $ids): void
    {
        //
    }
}
