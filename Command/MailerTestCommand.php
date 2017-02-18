<?php

namespace Youshido\MailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailerTestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mailer:test')
            ->addArgument('to', InputArgument::REQUIRED)
            ->setDescription('Sends test email');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $to     = $input->getArgument('to');
        $mailer = $this->getContainer()->get('youshido.mailer');
        $host   = $mailer->getWebHost();


        if ($mailer->sendHtmlEmailWithBody($to, sprintf("Test Email from [%s] host", $host), 'This is a test body.<br/> Your links will look like this: ' . $mailer->generateAbsoluteUrlFromString("/test-link"))) {
            $output->writeln('Email has been sent');
        };
    }
}
