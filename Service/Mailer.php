<?php
/**
 * Date: 10.09.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\MailBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Mailer extends ContainerAware
{

    /** @var array */
    private $letterConfigs;

    public function setLetter($id, $to, $parameters = [], $subject = '', $attachments = [])
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Not valid email');
        }

        $config = $this->getLetterConfig($id);

        if (!$subject && !isset($config['subject'])) {
            throw new \Exception('You must set letter subject');
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject ?: $config['subject'])
            ->setFrom($this->container->getParameter('ymail.from'))
            ->setTo($to)
            ->setBody($this->container->get('templating')->render($config['template'], $parameters), 'text/html');

        if (isset($config['headers'])) {
            foreach ($config['headers'] as $header){
                $message->getHeaders()->addTextHeader($header['key'], $header['value']);
            }
        }

        if($attachments){
            foreach($attachments as $attachment){
                if(!array_key_exists('filePath', $attachment) || !array_key_exists('fileName', $attachment)){
                    throw new \Exception('Each attachment must contain filePath and fileName property');
                }

                $message->attach(
                    \Swift_Attachment::fromPath($attachment['filePath'])->setFilename($attachment['fileName'])
                );
            }
        }

        $this->container->get('mailer')->send($message);
    }


    private function getLetterConfig($id)
    {
        if (array_key_exists($id, $this->letterConfigs)) {
            return $this->letterConfigs[$id];
        }

        throw new \Exception(sprintf('No config found for letter with id \'%s\'', $id));
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->letterConfigs = $container->getParameter('ymail.letters');
        parent::setContainer($container);
    }


}