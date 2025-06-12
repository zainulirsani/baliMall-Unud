<?php

namespace App\Controller\Media;

use App\Controller\PublicController;
use App\Entity\User;
use App\Traits\ImageProcessorTrait;
use App\Utility\UploadHandler;
use ErrorException;
use Symfony\Component\HttpFoundation\Response;

class FileController extends PublicController
{
    use ImageProcessorTrait;

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
        $overwrite = $request->request->get('overwrite', 'no');
        $useThumb = (bool) $request->request->get('use_thumb', '0');

        switch ($type) {
            case 'file':
                $target = 'default/files/';
                $paramName = 'file_file';
                $maxSize = $this->getParameter('max_upload_file');
                $allowed = '/\.(doc|xls|pdf)$/i';
                break;
            case 'video':
                $target = 'default/videos/';
                $paramName = 'file_video';
                $maxSize = $this->getParameter('max_upload_video');
                $allowed = '/\.(mp4|webm|ogv|flv)$/i';
                break;
            case 'payment':
                $target = 'default/payment/';
                $paramName = 'file_payment';
                $maxSize = $this->getParameter('max_upload_image');
                $allowed = '/\.(gif|jpe?g|png|pdf)$/i';
                break;
            default:
                $target = 'default/images/';
                $paramName = 'file_image';
                $maxSize = $this->getParameter('max_upload_image');
                $allowed = '/\.(gif|jpe?g|png)$/i';
                break;
        }

        if (empty($dir)) {
            $uploadDir = $this->getParameter('upload_dir').'/'.$target;
        } else {
            $target = ltrim($dir, '/');
            $target = rtrim($target, '/');
            $uploadDir = $this->getParameter('upload_dir').'/'.$target.'/';
        }

        $directory = $this->getParameter('upload_dir_path').'/'.$target;
        $uploadUrl = $this->getBaseUrl().'/'.$uploadDir;

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ErrorException($this->getTranslator()->trans('message.error.create_dir'));
        }

        $defaultData = $this->getDefaultData();
        $messages = null;
        $options = [
            'upload_dir' => $uploadDir,
            'upload_url' => $uploadUrl,
            'param_name' => $paramName,
            'max_file_size' => $maxSize,
            'accept_file_types' => $allowed,
            'overwrite_file' => $overwrite === 'yes',
        ];

        if (!$useThumb) {
            $options['image_versions'] = []; // Disable thumbnails
        }

        if ($defaultData['locale'] === 'id') {
            $messages = [
                1 => 'File yang diunggah melebihi direktif upload_max_filesize di php.ini',
                2 => 'File yang diunggah melebihi direktif MAX_FILE_SIZE yang ditentukan dalam formulir HTML',
                3 => 'File yang diunggah hanya sebagian yang diunggah',
                4 => 'Tidak ada file yang diunggah',
                6 => 'Tidak ada folder sementara',
                7 => 'Gagal menulis file ke disk',
                8 => 'Ekstensi PHP menghentikan unggahan file',
                'post_max_size' => 'File yang diunggah melebihi direktif upload_max_filesize di php.ini',
                'max_file_size' => 'File terlalu besar',
                'min_file_size' => 'File terlalu kecil',
                'accept_file_types' => 'Tipe file tidak diizinkan',
                'max_number_of_files' => 'Jumlah file maksimum terlampaui',
                'max_width' => 'Gambar melebihi lebar maksimum',
                'min_width' => 'Gambar membutuhkan lebar minimum',
                'max_height' => 'Gambar melebihi tinggi maksimum',
                'min_height' => 'Gambar membutuhkan tinggi minimum',
                'abort' => 'Unggahan file dibatalkan',
                'image_resize' => 'Gagal mengubah ukuran gambar',
            ];
        }

        $handler = new UploadHandler($options, true, $messages);

        if (!isset($handler->response[$paramName][0]->error)) {
            $fileLocation = $directory.'/'.$handler->response[$paramName][0]->name;

            // Case: image uploaded from iPhone will have different orientation
            if (exif_imagetype($fileLocation) === 2) {
                $this->rotateImage($fileLocation);
            }
        }

        return new Response('');
    }

    public function fileDelete()
    {
        $this->isAjaxRequest('POST');

        $request = $this->getRequest();
        $id = abs($request->request->get('id', '0'));
        $path = $request->request->get('path', '');
        $path = !empty($path) ? ltrim($path, '/') : 'no-data.jpg';
        //$source = $request->request->get('src', '');
        //$source = !empty($source) ? $source : 'no_source';
        $file = $this->getParameter('public_dir_path').'/'.$path;
        $response = ['deleted' => true];

        if ($id < 1 && is_file($file) && !in_array($path, $this->getParameter('images_reserved_names'), false)) {
            unlink($file);
        }

        return $this->view('', $response, 'json');
    }
}
