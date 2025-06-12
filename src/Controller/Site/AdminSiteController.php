<?php

namespace App\Controller\Site;

use App\Controller\AdminController;
use App\Entity\ProductFile;
use App\Utility\UploadHandler;
use ErrorException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class AdminSiteController extends AdminController
{
    public function index()
    {
        return $this->view('@__main__/admin/site/index.html.twig');
    }

    public function notification()
    {
        return $this->view('@__main__/admin/site/notification.html.twig');
    }

    public function download(): RedirectResponse
    {
        $request = $this->getRequest();
        $token = $request->query->get('token', null);
        $origin = $request->query->get('origin', null);
        /** @var Session $session */
        $session = $this->getSession();

        if (empty($token) || ($token && !$session->has($token))) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $data = $session->get($token);
        $writer = null;
        $fileName = sprintf('%s.xlsx', $origin);
        $spreadsheet = new Spreadsheet();

        if (isset($data['year']) && !empty($data['year'])) {
            $fileName = sprintf('%s_%s.xlsx', $origin, $data['year']);
        }

        if ($origin === 'transaction_per_month' || $origin === 'transaction_nominal_per_month') {
            $labels = json_decode($data['labels'], true);
            $dataRegular = json_decode($data['regular'], true);
            $dataB2G = json_decode($data['b2g'], true);
            $sheet = $spreadsheet->getActiveSheet();

            $index = 1;
            $sheet->setCellValueByColumnAndRow($index, 1, 'Type');

            foreach ($labels as $label) {
                $index++;
                $sheet->setCellValueByColumnAndRow($index, 1, $label);
            }

            $index = 1;
            $sheet->setCellValueByColumnAndRow($index, 2, 'Regular');

            foreach ($dataRegular as $value) {
                $index++;
                $sheet->setCellValueByColumnAndRow($index, 2, $value);
            }

            $index = 1;
            $sheet->setCellValueByColumnAndRow($index, 3, 'B2G');

            foreach ($dataB2G as $value) {
                $index++;
                $sheet->setCellValueByColumnAndRow($index, 3, $value);
            }

            $writer = new Xlsx($spreadsheet);
        } elseif ($origin === 'export_merchant_transaction') {
            $labels = json_decode($data['labels'], true);
            $total = json_decode($data['total'], true);
            $nominal = json_decode($data['nominal'], true);
            $status = json_decode($data['status'], true);
            $sheet = $spreadsheet->getActiveSheet();
            $trans = $this->getTranslator();
            $sheet->setCellValueByColumnAndRow(1, 1, 'Nama Merchant');
            $sheet->setCellValueByColumnAndRow(2, 1, 'Total Transaksi');
            $sheet->setCellValueByColumnAndRow(3, 1, 'Nominal');
            $nextNo = 4;
            foreach ($status[0] as $key => $value) {
                if ($key == 'cancel') {
                    $status_trans = 'cancelled';
                } else {
                    $status_trans = $key;
                }

                $label_status = $trans->trans('label.'.$status_trans);
                $sheet->setCellValueByColumnAndRow($nextNo++, 1, $label_status);
            }
            foreach ($labels as $idx => $label) {
                $row = $idx + 1;
                $sheet->setCellValueByColumnAndRow(1, $row+1, $label);
                $sheet->setCellValueByColumnAndRow(2, $row+1, $total[$idx] ?? '');
                $sheet->setCellValueByColumnAndRow(3, $row+1, $nominal[$idx] ?? '');
                $nextNo2 = 4;
                foreach ($status[$idx] as $key => $value) {
                    $sheet->setCellValueByColumnAndRow($nextNo2++, $row+1, $value);
                }
            }

            $writer = new Xlsx($spreadsheet);
        } else {
            $labels = json_decode($data['labels'], true);
            $data = json_decode($data['data'], true);
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($labels as $idx => $label) {
                $sheet->setCellValueByColumnAndRow($idx + 1, 1, $label);
            }

            foreach ($data as $idx => $value) {
                $sheet->setCellValueByColumnAndRow($idx + 1, 2, $value);
            }

            $writer = new Xlsx($spreadsheet);
        }

        if ($writer instanceof Xlsx) {
            try {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="'.$fileName.'"');
                $writer->save('php://output');
                exit;
            } catch (Exception $e) {
            }
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * @return Response
     * @throws ErrorException
     */
    public function fileUpload(): Response
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $dir = $request->request->get('dir', '');
        $type = $request->request->get('type', 'image');
        $useThumb = (bool) $request->request->get('use_thumb', '0');

        switch ($type) {
            case 'document':
                $target = 'default/docs/';
                $paramName = 'file_document';
                $maxSize = $this->getParameter('max_upload_file');
                $allowed = '/\.(doc|xls|pdf)$/i';
                break;
            case 'video':
                $target = 'default/videos/';
                $paramName = 'file_video';
                $maxSize = $this->getParameter('max_upload_video');
                $allowed = '/\.(mp4|webm|ogv|flv)$/i';
                break;
            default:
                $target = 'default/images/';
                $paramName = 'file_image';
                $maxSize = $this->getParameter('max_upload_image');
                $allowed = '/\.(gif|jpe?g|png)$/i';
                break;
        }

        if (!empty($dir)) {
            $target = ltrim($dir, '/');
            $target = rtrim($target, '/');
            $uploadDir = $this->getParameter('upload_dir').'/'.$target.'/';
        } else {
            $uploadDir = $this->getParameter('upload_dir').'/'.$target;
        }

        $directory = $this->getParameter('upload_dir_path').'/'.$target;
        $uploadUrl = $this->getBaseUrl().'/'.$uploadDir;

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ErrorException($this->getTranslator()->trans('message.error.create_dir'));
        }

        $options = [
            'upload_dir' => $uploadDir,
            'upload_url' => $uploadUrl,
            'param_name' => $paramName,
            'max_file_size' => $maxSize,
            'accept_file_types' => $allowed,
            'overwrite_file' => true,
        ];

        if (!$useThumb) {
            $options['image_versions'] = []; // Disable thumbnails
        }

        new UploadHandler($options);

        return new Response('');
    }

    public function fileDelete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $post = $request->request->all();
        $id = $post['id'] ?? 0;
        $path = $request->request->get('path', 'no-data.jpg');

        if (!empty($path)) {
            $path = ltrim($post['path'], '/');
        }

        $source = $post['src'] ?? 'no_source';
        $file = $this->getParameter('public_dir_path').'/'.$path;
        $response = ['deleted' => true];

        if (abs($id) > 0) {
            // Proceed to delete data from DB
            $em = $this->getEntityManager();
            $entity = null;

            if ($source === 'product_file') {
                $entity = $this->getRepository(ProductFile::class)->find($id);
            }

            if (null !== $entity) {
                $em->remove($entity);
                $em->flush();
            }
        }

        if (is_file($file) && !in_array($path, $this->getParameter('images_reserved_names'), false)) {
            unlink($file);
        }

        return $this->view('', $response, 'json');
    }

    public function dev()
    {
        $request = $this->getRequest();

        return $this->view('@__main__/admin/site/dev.html.twig', [
            'query' => $request->query->all(),
        ]);
    }
}
