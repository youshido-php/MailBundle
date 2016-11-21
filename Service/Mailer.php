<?php
/**
 * Date: 10.09.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\MailBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Mailer
{

    use ContainerAwareTrait {
        setContainer as baseSetContainer;
    }

    /** @var array */
    private $letterConfigs;

    /** @var  array */
    private $contentIds;

    /**
     * @param        $id
     * @param        $to
     * @param array  $parameters
     * @param string $subject
     * @param array  $attachments
     *
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function sendEmail($id, $to, $parameters = [], $subject = '', $attachments = [])
    {
        if (is_array($to)) {
            foreach ($to as $item) {
                if (!filter_var($item, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Not valid email');
                }
            }
        } else {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Not valid email');
            }
        }

        $config = $this->getLetterConfig($id);

        if (!$subject && !isset($config['subject'])) {
            throw new \Exception('You must set letter subject');
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject ?: $config['subject'])
            ->setFrom($this->container->getParameter('ymail.from'))
            ->setTo($to);

        $message->setBody(
            $this->container->get('templating')->render(
                $config['template'],
                array_merge(
                    $parameters,
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

        $this->container->get('mailer')->send($message);
    }

    /**
     * @param        $id
     * @param        $to
     * @param array  $parameters
     * @param string $subject
     * @param array  $attachments
     *
     * @throws \Exception
     * @throws \Twig_Error
     *
     * @deprecated use method sendEmail
     */
    public function setLetter($id, $to, $parameters = [], $subject = '', $attachments = [])
    {
        $this->setLetter($id, $to, $parameters, $subject, $attachments);
    }

    private function prepareContentIds(\Swift_Message $message)
    {
        $cid = [];

        foreach ($this->contentIds as $contentId) {
            $cid[$contentId['id']] = $message->embed(\Swift_Image::fromPath($contentId['path']));
        }

        return $cid;
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
        $this->contentIds    = $container->getParameter('ymail.cid');

        $this->baseSetContainer($container);
    }


}