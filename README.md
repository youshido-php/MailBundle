# Mail Bundle

### 1. Basic config: 
``` yaml
youshido_mail:
    config:
        from: test@test.com # default from for all emails
    emails:
        registration_success: # email config id
            subject:  Welcome to our service #not necessary
            template: '@App/emails/register-success.html.twig'
            from: billing@test.com # optional From for this config
        personal_reminder: # email config id
            subject:  Hi %name%, your reminder is here!
            template: '@App/emails/register-success.html.twig'
            from: billing@test.com # optional From for this config
```

### 2. Usage:
``` php
$mailer = $this->container->get('youshido.mailer');
$mailer->sendEmailWithId('registration_success', 'user@test.com', ['name' => 'Alex']);

$mailer->sendEmailWithId('personal_reminder', 'user@test.com', ['name' => 'Alex']);
```