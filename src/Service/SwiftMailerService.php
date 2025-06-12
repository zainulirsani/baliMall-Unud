<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Swift_TransportException;

class SwiftMailerService
{
    private $mailer;
    private $logger;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function send(array $data): void
    {
        if (!isset($data['subject'], $data['from'], $data['to'], $data['body'])) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('%s: Invalid email data', __FUNCTION__));

            return;
        }

        $message = (new \Swift_Message())
            ->setSubject($data['subject'])
            ->setFrom($data['from'])
            ->setTo($data['to'])
            ->setBody($data['body'], 'text/html');

        if (isset($data['bcc'])) {
            $message->setBcc($data['bcc']);
        }

        if (isset($data['attachment'])) {
            $message->attach(\Swift_Attachment::fromPath($data['attachment']));
        }

        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $message->attach(\Swift_Attachment::fromPath($attachment));
            }
        }

        try {
            if (!$this->mailer->send($message, $failures)) {
                $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
                $this->logger->error(sprintf('%s: Sending failures!', __CLASS__), $failures);
            }
        } catch (Swift_TransportException $e) {
            $this->logger->error(sprintf('*** %s Custom Debug Message', getenv('APP_NAME')));
            $this->logger->error(sprintf('%s error: %s', __CLASS__, $e->getMessage()));
        }
    }
}
