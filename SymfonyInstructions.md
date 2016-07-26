## Raygun4PHP Symfony installation instructions

### Step 1: Install Raygun4PHP with Composer (see README.md)

### Step 2: Create the file: `src/AppBundle/EventListener/RaygunExceptionListener` with the following content:

```php
<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Raygun;

class RaygunExceptionListener
{

    private $client;

    public function __construct()
    {
        $this->client = new \Raygun4php\RaygunClient("apiKey");
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();
        $this->client->SendException($exception);
    }
}

?>
```
**Important:** Make sure you change *apiKey* to your Raygun API key.

### Step 3: Register the hook service in `app/config/services.yml`
```
app.exception_listener:
    class: AppBundle\EventListener\RaygunExceptionListener
    tags:
        - { name: kernel.event_listener, event: kernel.exception }
```

## Further Information:
Information about the [Symfony Event Listeners can be located here](http://symfony.com/doc/current/cookbook/event_dispatcher/event_listener.html).
