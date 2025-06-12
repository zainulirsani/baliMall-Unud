<?php

namespace App\Service;

use App\Exception\SendInBlueMailException;
use Exception;
use Psr\Log\LoggerInterface;
use Sendinblue\Mailin;

class SendInBlueMailService
{
    private $apiKey;
    private $logger;

    public function __construct(string $apiKey, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * @param array $data
     *
     * @throws SendInBlueMailException
     */
    public function send(array $data): void
    {
        try {
            $mail = new Mailin('https://api.sendinblue.com/v2.0', $this->apiKey);
            $mail->send_email($data);
        } catch (Exception $e) {
            $message = sprintf('SendInBlue delivery error: %s', $e->getMessage());

            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error($message);

            throw new SendInBlueMailException($message);
        }
    }
}
