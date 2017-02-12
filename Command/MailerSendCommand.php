<?php

namespace Youshido\MailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailerSendCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mailer:send')
            ->addArgument('emailId', InputArgument::REQUIRED)
            ->addArgument('to', InputArgument::REQUIRED)
            ->addArgument('subject', InputArgument::OPTIONAL)
            ->setDescription('Sends email with ID');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emailId = $input->getArgument('emailId');
        $to      = $input->getArgument('to');
        $subject = $input->getArgument('subject');

        if ($this->getContainer()->get('youshido.mailer')->sendHtmlEmailWithId($emailId, $to, [], $subject)) {
            $output->writeln('Email has been sent');
        };
    }
}
