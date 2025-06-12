<?php

namespace App\Controller\Sftp;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\PublicController;

class SftpController extends PublicController
{
    public function showFile(string $path): Response
    {
        // Tentukan direktori di server SFTP tempat gambar disimpan
        $remoteDirectory = $_ENV['SFTP_REMOTE_DIR'];
        $file = $path;
        // dd($file);
        $extensionFile = substr($file, -3);
        // dd($extensionFile);
        // Ambil gambar dari server SFTP
        $data = $this->sftpUploader->getImageData($remoteDirectory . $file);

        // Periksa apakah gambar ditemukan
        if ($data === false) {
            throw $this->createNotFoundException('Image not found');
        }
        
        $response = new Response($data);

        // Tentukan tipe konten gambar
        if ($extensionFile == 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf'); 
        } else {
            $response->headers->set('Content-Type', 'image/jpeg'); 
        }

        // download
        // $response->headers->set('Content-Disposition', 'attachment; filename="test.pdf"');
        
        return $response;
    }

    public function downloadFile(string $path): Response 
    {
        
        // Tentukan direktori di server SFTP tempat gambar disimpan
        $remoteDirectory = $_ENV['SFTP_REMOTE_DIR'];
        $file = $path;
        $explode = explode('/', $file);

        // Ambil gambar dari server SFTP
        $data = $this->sftpUploader->getImageData($remoteDirectory . $file);
        // dd($data);
        // Periksa apakah gambar ditemukan
        if ($data === false) {
            // dd('test');
            throw $this->createNotFoundException('Image not found');
        }
        
        $response = new Response($data);
        // dd($response);
        // download
        $response->headers->set('Content-Disposition', 'attachment; filename='. $explode[3] . '');
        
        return $response;
    }
}
