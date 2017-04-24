<?php
/**
 * Date: 10.09.15
 *
 * @author Alexandr Viniychuk <a@viniychuk.com>
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\MailBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Youshido\MailBundle\Exception\MailerException;

class Mailer
{

    use ContainerAwareTrait {
        setContainer as baseSetContainer;
    }

    /** @var array */
    protected $emailConfigs = [];

    /** @var  array */
    protected $contentIds = [];

    /** @var Router */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param $emailId
     * @param $to
     * @param array $variables
     * @param string $subject
     * @param array $attachments
     * @return mixed
     */
    public function sendHtmlEmailWithId($emailId, $to, $variables = [], $subject = '', $attachments = [])
    {
        return $this->sendHtmlEmailWithConfig($this->getEmailConfig($emailId), $to, $variables, $subject, $attachments);
    }

    /**
     * @param $config
     * @param $to
     * @param array $variables
     * @param string $subject
     * @param array $attachments
     * @return mixed
     * @throws \Exception
     */
    public function sendHtmlEmailWithConfig($config, $to, $variables = [], $subject = '', $attachments = [])
    {
        $config = $this->getValidatedConfig($config, $to, $subject, $variables, $attachments);

        $message      = $this->createEmailInstanceWithConfig($config);
        $renderedBody = $this->container->get('templating')->render(
            $config['template'],
            array_merge(
                $variables,
                $this->prepareContentIds($message)
            )
        );

        $message->setBody(
            $renderedBody,
            'text/html'
        );
        return $this->sendEmail($message, $config);
    }

    public function sendHtmlEmailWithBody($to, $subject, $body, $variables = [], $attachments = [])
    {
        $config = $this->getValidatedConfig([], $to, $subject, $variables, $attachments);

        $message = $this->createEmailInstanceWithConfig($config);
        $message->setBody($body, 'text/html');
        return $this->sendEmail($message, $config);
    }

    protected function sendEmail(\Swift_Message $message, $config)
    {
        if (isset($config['headers'])) {
            foreach ($config['headers'] as $header) {
                $message->getHeaders()->addTextHeader($header['key'], $header['value']);
            }
        }

        if (!empty($config['attachments'])) {
            foreach ($config['attachments'] as $attachment) {
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

    protected function createEmailInstanceWithConfig($config)
    {
        return \Swift_Message::newInstance()
            ->setSubject($config['subject'])
            ->setFrom($config['from'])
            ->setTo($config['to']);

    }

    public function generateAbsoluteUrlFromString($url)
    {
        $webHost      = $this->container->getParameter('y_mail.config')['host'];
        $rc           = $this->router->getContext();

        $res = sprintf("%s://%s/%s", $rc->getScheme(), ($webHost ? $webHost : $rc->getHost()), $url);
        return $res;
    }

    public function generateUrl($route, array $params = [])
    {
        $originalHost = $this->router->getContext()->getHost();
        $webHost      = $this->container->getParameter('y_mail.config')['host'];
        if (!$webHost) {
            $webHost = $originalHost;
        }

        $this->router->getContext()->setHost(!empty($params['host']) ? $params['host'] : $webHost);
        $url = $this->router->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);

        $this->router->getContext()->setHost($originalHost);

        return $url;
    }

    public function replaceVariables($template, $variables)
    {
        foreach ($variables as $var => $value) {
            $template = preg_replace('/{{\s*' . $var . '\s*}}/is' , $value, $template);
        }
        return $template;
    }

    protected function getValidatedConfig($config, $to, $subject = '', $variables = [], $attachments = [])
    {
        if (empty($config['subject'])) {
            $config['subject'] = $subject;
        }
        if (empty($config['subject'])) {
            throw new \Exception('You must set email subject');
        }

        $config['subject'] = $this->replaceVariables($config['subject'], $variables);
        if (is_string($to) && !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new MailerException(sprintf('Not valid email address %s', $to));
        }
        $config['to'] = $to;
        if (empty($config['from'])) {
            $config['from'] = $this->container->getParameter('y_mail.config')['from'];
        }
        if (!empty($attachments)) {
            $config['attachments'] = $attachments;
        }
        if (!empty($variables)) {
            $config['variables'] = $variables;
        }
        return $config;

    }

    protected function prepareContentIds(\Swift_Message $message)
    {
        $cid = [];

        foreach ($this->contentIds as $contentId) {
            $cid[$contentId['id']] = $message->embed(\Swift_Image::fromPath($contentId['path']));
        }

        return $cid;
    }

    protected function getEmailConfig($id)
    {
        if (array_key_exists($id, $this->emailConfigs)) {
            return $this->emailConfigs[$id];
        }

        throw new \Exception(sprintf('No config found for email with id \'%s\'', $id));
    }

    public function addCidDefinitions(array $cidDefinitions)
    {
        $this->contentIds = array_merge($this->contentIds, $cidDefinitions);
    }

    public function setEmailConfigs(array $emailConfigs)
    {
        $this->emailConfigs = array_merge($this->emailConfigs, $emailConfigs);
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

    public function getWebHost()
    {
        return $this->container->getParameter('y_mail.config')['host'];
    }

}