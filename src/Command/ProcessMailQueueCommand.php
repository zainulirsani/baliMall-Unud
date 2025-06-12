<?php

namespace App\Command;

use App\Entity\MailQueue;
use App\Repository\MailQueueRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessMailQueueCommand extends Command
{
    /** @var ObjectManager $manager */
    private $manager;

    /** @var \Swift_Mailer $mailer */
    private $mailer;

    public function __construct(Registry $registry, \Swift_Mailer $mailer)
    {
        parent::__construct();

        $this->manager = $registry->getManager();
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:process-mail-queue')
            ->setDescription('Process mail queue stored in database.')
            ->setHelp('This command allows you to process mail queue and send them to the recipients.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->manager;
        /** @var MailQueueRepository $repository */
        $repository = $em->getRepository(MailQueue::class);
        /** @var MailQueue[] $queues */
        $queues = $repository->findBy(['success' => 0], ['createdAt' => 'ASC'], 50);

        if (count($queues) > 0) {
            foreach ($queues as $queue) {
                $data = $queue->getPayload();
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

                if (!$this->mailer->send($message, $failures)) {
                    $queue->setFailed(1);
                    $em->persist($queue);
                } else {
                    $queue->setSuccess(1);
                    $em->persist($queue);
                    //$em->remove($queue);
                }
            }

            $em->flush();
        }

        return 1;
    }
}
