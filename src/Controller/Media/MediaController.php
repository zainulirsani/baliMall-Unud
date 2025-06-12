<?php

namespace App\Controller\Media;

use App\Controller\PublicController;
use Cocur\Slugify\Slugify;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MediaController extends PublicController
{
    public function gallery()
    {
        $request = $this->getRequest();
        $path = $this->getParameter('media_dir_path');
        $mediaDir = $this->getParameter('media_dir');
        $reserved = $this->getParameter('reserved_image_mime_types');
        $dir = scandir($path, SCANDIR_SORT_ASCENDING);
        $images = [];

        foreach ($dir as $file) {
            if ($file !== '.' && $file !== '..') {
                $image = new File($path.'/'.$file);

                if (in_array(strtolower($image->getMimeType()), $reserved, false)) {
                    $images[] = $this->getBaseUrl().'/'.$mediaDir.'/'.$image->getFilename();
                }
            }
        }

        return $this->view('@__main__/public/media/gallery.html.twig', [
            'images' => $images,
            'func_num' => $request->query->get('CKEditorFuncNum'),
        ]);
    }

    public function upload(): Response
    {
        $request = $this->getRequest();
        $file = $request->files->get('upload');
        $funcNum = $request->query->get('CKEditorFuncNum', '');
        $langCode = $request->query->get('langCode', $this->getLocale());
        $translator = $this->getTranslator();
        $message = 'message.error.file_type';
        $url = '';

        if ($file) {
            $ext = strtolower($file->guessExtension());
            $allowed = $this->getParameter('allowed_image_ext');

            if (in_array($ext, $allowed, false)) {
                $size = $file->getClientSize();
                $max = $this->getParameter('max_upload_image');
                $message = 'message.error.max_size';

                if ($size < $max) {
                    $path = $this->getParameter('media_dir');
                    $mediaDir = $this->getParameter('media_dir');
                    $name = $this->setFileName($file);
                    $success = $file->move($path, $name);
                    $message = $success ? 'message.success.upload' : 'message.error.upload';
                    $url = $success ? $this->getBaseUrl().'/'.$mediaDir.'/'.$name : '';
                }
            }

            $message = $translator->trans($message, [], 'messages', $langCode);
            $response = '<script type="text/javascript">';
            $response .= 'window.parent.CKEDITOR.tools.callFunction('.$funcNum.', "'.$url.'", "'.$message.'")';
            $response .= '</script>';

            return new Response($response);
        }

        throw new AccessDeniedException($translator->trans('message.error.403'));
    }

    private function setFileName(UploadedFile $file): string
    {
        $slug = new Slugify();
        $originalName = $file->getClientOriginalName();
        $originalExt = $file->getClientOriginalExtension();
        $fileName = ltrim(str_replace($originalExt, '', $originalName), '.');

        return $slug->slugify($fileName).'.'.$file->guessClientExtension();
    }
}
