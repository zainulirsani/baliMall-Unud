<?php

namespace App\Service;

use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\Factory\QrCodeFactory;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use ErrorException;

class QrCodeGenerator
{
    private $uploadDir;
    private $uploadDirPath;
    private $targetDirectory = 'vouchers';
    private $dataSize = 200;

    public function __construct(string $uploadDir, string $uploadDirPath)
    {
        $this->uploadDir = $uploadDir;
        $this->uploadDirPath = $uploadDirPath;
    }

    public function setTargetDirectory(?string $targetDirectory): void
    {
        if (!empty($targetDirectory)) {
            $this->targetDirectory .= '/'.$targetDirectory;
        }
    }

    public function resetTargetDirectory(): void
    {
        $this->targetDirectory = 'vouchers';
    }

    /**
     * @param string $content
     * @param string $path
     *
     * @return string
     * @throws ErrorException
     */
    public function generate(string $content, string $path = 'voucher.png'): string
    {
        $directory = sprintf('%s/%s', $this->uploadDirPath, $this->targetDirectory);

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ErrorException('Unable to create directory!');
        }

        try {
            $factory = new QrCodeFactory();
            $data = $factory->create($content, ['size' => $this->dataSize]);
            $data->writeFile($directory.'/'.$path);
        } catch (ValidationException $e) {
            //
        }

        return sprintf('%s/%s/%s', $this->uploadDir, $this->targetDirectory, $path);
    }

    public function stream(string $content): ?QrCodeResponse
    {
        try {
            $factory = new QrCodeFactory();
            $data = $factory->create($content, ['size' => $this->dataSize]);
            $response = new QrCodeResponse($data);
            $response->send();
        } catch (ValidationException $e) {
            $response = null;
        }

        return $response;
    }

    public function dataUri(string $content): ?string
    {
        try {
            $factory = new QrCodeFactory();
            $data = $factory->create($content, ['size' => $this->dataSize]);
            $response = $data->writeDataUri();
        } catch (ValidationException $e) {
            $response = null;
        }

        return $response;
    }
}
