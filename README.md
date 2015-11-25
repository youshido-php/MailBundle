# Mail Bundle

### 1. Basic config: 
``` yaml
youshido_mail:
    config:
        from: test@test.com
    letters:
        register_success: #mail id
            subject:  Welcome to KONTENT CORE #not necessary
            template: '@App/emails/register-success.html.twig'
```

### 2. Usage:
``` php
$this->container->get('ymailer')->setLetter('new_paid_order', 'to@to.com', $parameters, $subject, $attachments);
```