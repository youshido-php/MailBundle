<?php
/**
 * Date: 10.09.15
 *
 * @author Portey Vasil <portey@gmail.com>
 * @author Alexandr Viniychuk <a@viniychuk.com>
 */

namespace Youshido\MailBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\MailBundle\Exception\MailerException;

class Mailer
{

    use ContainerAwareTrait {
        setContainer as baseSetContainer;
    }

    /** @var array */
    private $emailConfigs;

    /** @var  array */
    private $contentIds;

    /**
     * @param        $emailId
     * @param        $to
     * @param array  $variables
     * @param string $subject
     * @param array  $attachments
     *
     * @throws \Exception
     */
    public function sendHtmlEmailWithId($emailId, $to, $variables = [], $subject = '', $attachments = [])
    {
        return $this->sendHtmlEmailWithConfig($this->getEmailConfig($emailId), $to, $variables, $subject, $attachments);
    }

    public function sendHtmlEmailWithConfig($config, $to, $variables, $subject = '', $attachments = [])
    {
        $this->assertValidEmailParameters($config, $to, $subject);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject ?: $config['subject'])
            ->setFrom($this->container->getParameter('y_mail.from'))
            ->setTo($to);

        $message->setBody(
            $this->container->get('templating')->render(
                $config['template'],
                array_merge(
                    $variables,
                    $this->prepareContentIds($message)
                )
            ),
            'text/html'
        );

        if (isset($config['headers'])) {
            foreach ($config['headers'] as $header) {
                $message->getHeaders()->addTextHeader($header['key'], $header['value']);
            }
        }

        if ($attachments) {
            foreach ($attachments as $attachment) {
                if (!array_key_exists('filePath', $attachment) || !array_key_exists('fileName', $attachment)) {
                    throw new \Exception('Each attachment must contain filePath and fileName property');
                }

                $message->attach(
                    \Swift_Attachment::fromPath($attachment['filePath'])->setFilename($attachment['fileName'])
                );
            }
        }

        return $this->container->get('mailer')->send($message);
    }

    public function sendHtmlEmailWithBody($body, $to, $subject = '', $attachments = [])
    {

    }

    protected function assertValidEmailParameters($emailConfig, $to, $subject = '') {
        if (!$subject && !isset($emailConfig['subject'])) {
            throw new \Exception('You must set email subject');
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new MailerException(sprintf('Not valid email address %s', $to));
        }

    }

    private function prepareContentIds(\Swift_Message $message)
    {
        $cid = [];

        foreach ($this->contentIds as $contentId) {
            $cid[$contentId['id']] = $message->embed(\Swift_Image::fromPath($contentId['path']));
        }

        return $cid;
    }

    private function getEmailConfig($id)
    {
        if (array_key_exists($id, $this->emailConfigs)) {
            return $this->emailConfigs[$id];
        }

        throw new \Exception(sprintf('No config found for email with id \'%s\'', $id));
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->emailConfigs = $container->getParameter('y_mail.emails');
        $this->contentIds   = $container->getParameter('y_mail.cid');

        $this->baseSetContainer($container);
    }


}