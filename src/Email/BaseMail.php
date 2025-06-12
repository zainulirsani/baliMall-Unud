<?php

namespace App\Email;

use App\Service\SwiftMailerService;
use Exception;
use Twig\Environment;

class BaseMail
{
    /** @var Environment $twig */
    protected $twig;

    /** @var SwiftMailerService $mailerService */
    protected $mailerService;

    /** @var array $mailData */
    protected $mailData = [];

    /** @var array $mailAttachments */
    protected $mailAttachments = [];

    /** @var string $mailSubject */
    protected $mailSubject;

    /** @var string $mailTemplate */
    protected $mailTemplate;

    /** @var array $mailRecipient */
    protected $mailRecipient;

    /** @var string $mailSender */
    protected $mailSender;

    /** @var bool $mailDebug */
    protected $mailDebug = false;

    public function __construct(Environment $twig, SwiftMailerService $mailerService, string $mailSender)
    {
        $this->twig = $twig;
        $this->mailerService = $mailerService;
        $this->mailSender = $mailSender;
    }

    public function setMailData(array $mailData): void
    {
        $this->mailData = $mailData;
    }

    public function setMailAttachments(array $mailAttachments = []): void
    {
        $this->mailAttachments = $mailAttachments;
    }

    public function setMailSubject(string $mailSubject): void
    {
        $this->mailSubject = $mailSubject;
    }

    public function setMailTemplate(string $mailTemplate): void
    {
        $this->mailTemplate = $mailTemplate;
    }

    public function setMailRecipient(string $mailRecipient): void
    {
        $this->mailRecipient = explode(',', $mailRecipient);
    }

    public function setMailSender(string $mailSender): void
    {
        $this->mailSender = $mailSender;
    }

    public function setToAdmin(): void
    {
        $this->setMailRecipient(getenv('MAIL_RECEIVER'));
    }

    public function setMailDebug(): void
    {
        $this->mailDebug = true;
    }

    public function send(): void
    {
        $body = 'Test mail sent!';
        $contentType = 'text/plain';

        if (!$this->mailDebug) {
            try {
                $body = $this->twig->render($this->mailTemplate, $this->mailData);
                $contentType = 'text/html';
            } catch (Exception $e) {
                // dd($e);
            }
        }

        $data = [
            'to' => $this->mailRecipient,
            'from' => $this->mailSender,
            'subject' => $this->mailSubject,
            'body' => $body,
            'content_type' => $contentType,
        ];

        if (count($this->mailAttachments) > 0) {
            $data['attachments'] = $this->mailAttachments;
        }

        $this->mailerService->send($data);
    }
}
