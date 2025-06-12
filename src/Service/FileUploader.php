<?php

namespace App\Service;

use Cocur\Slugify\Slugify;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Psr\Log\LoggerInterface;
use App\Service\SftpUploader;

class FileUploader
{
    private $publicDirectory;
    private $logger;
    private $targetDirectory;
    private $oldFilePath = null;
    protected $sftpUploader;
    private $flash;

    public function __construct(string $publicDirectory, ?SftpUploader $sftpUploader = null, LoggerInterface $logger, FlashBagInterface $flash)
    {
        $this->publicDirectory = $publicDirectory;
        $this->sftpUploader = $sftpUploader;
        $this->logger = $logger;
        $this->flash = $flash;
    }

    public function upload(UploadedFile $file, bool $encryptName = false, bool $removeOldFile = false): string
    {   
        // dd($file, $file->getMimeType(), $file->getClientOriginalName(), $file->getClientOriginalExtension(), $convertToBinary);
        // $slugger = new Slugify();


        $convertToBinary = unpack('H*', $file->getContent());
        $convertToBinary = strtoupper(array_shift($convertToBinary));
        $extension = $file->getClientOriginalExtension();

        // if (substr($convertToBinary, 0, 4) == 'D0CF' || // doc, xls, ppt (versi lama)
        //     substr($convertToBinary, 0, 6) == 'FFD8FF' || // jpg, jpeg
        //     substr($convertToBinary, 0, 8) == '89504E47' || // png
        //     substr($convertToBinary, 0, 4) == '504B0304' || // docx, xlsx, pptx (versi baru)
        //     substr($convertToBinary, 0, 4) == '25504446') { // pdf
        // } else {
        //     $this->logger->error('Unsupported file extension detected for upload.', [
        //         'fileName' => $file->getClientOriginalName(),
        //         'mimeType' => $file->getMimeType(),
        //         'binaryHeader' => substr($convertToBinary, 0, 8),
        //     ]);
        //     $this->flash->add('error', 'Ekstensi file tidak didukung untuk unggah.');
        //     throw new FileException('Ekstensi file tidak didukung.');
        // }

        $blockedExtensions = [
            'php', 'js', 'py', 'bat',
            'sh', 'exe', 'msi', 'bin', 'com', 'pif', 'cmd', 'vb', 'vbs', 'vba', 'svg', 'html', 'htm',
        ];

        if (in_array($extension, $blockedExtensions)) {
            $this->logger->error('Blocked file extension detected for upload.', [
                'fileName' => $file->getClientOriginalName(),
                'extension' => $extension,
            ]);
            $this->flash->add('error', 'Ekstensi file diblokir dan tidak diizinkan untuk unggah.');
            throw new FileException('Ekstensi file diblokir.');
        }


        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $originalName;
        $newName = $encryptName ? sha1($safeName) : $safeName . '-' . uniqid('', false);
        $fileName = $newName . '.' . $extension;
        $targetDirectory = $this->getTargetDirectory();
        


        if ($extension == 'xlsx') {
            $file->move($this->getUploadedPath(), $fileName);
        } else {
            $prefixPath = 'uploads/';
            $remoteDir  = $prefixPath . $targetDirectory . '/';
            $localFilePath = $file->getPathname();
            $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $fileName);
        }

        try {
            if ($removeOldFile) {
                $this->remove();
            }
        } catch (FileException $e) {
            //
        }

        return !empty($targetDirectory) ? $targetDirectory . '/' . $fileName : $fileName;
    }

    public function remove(): void
    {
        if (!empty($this->getOldFilePath())) {
            $path = sprintf('%s/%s', $this->getUploadedPath(), $this->getOldFilePath());

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function getPublicDirectory(): string
    {
        return $this->publicDirectory;
    }

    public function getTargetDirectory(): string
    {
        return rtrim($this->targetDirectory, '/');
    }

    public function setTargetDirectory(string $targetDirectory): void
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function getUploadedPath(): string
    {
        $publicDirectory = $this->getPublicDirectory();
        $targetDirectory = $this->getTargetDirectory();

        return !empty($targetDirectory) ? $publicDirectory . '/' . $targetDirectory : $publicDirectory;
    }

    public function getOldFilePath(): ?string
    {
        return $this->oldFilePath;
    }

    public function setOldFilePath(string $oldFilePath): void
    {
        $this->oldFilePath = $oldFilePath;
    }
}
