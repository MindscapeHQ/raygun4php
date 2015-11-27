## Raygun4PHP CodeIgniter installation instructions

### Step 1: Install Raygun4PHP with Composer (see README.md)

### Step 2: Enable hooks

In `/application/config/config.php` set *enable_hooks* to true:

```
$config['enable_hooks'] = TRUE;
```

### Step 3: Create the file: `/application/hooks/RaygunSetup.php` with the following content:

```
namespace
{
    require_once 'vendor/autoload.php';

    class RaygunSetup {
        private $client;

        public function __construct()
        {
            $this->client = new \Raygun4php\RaygunClient("apiKey");
        }

        public function set_exception_handler()
        {
            set_exception_handler( array( $this,'exception_handler' ) );
            set_error_handler( array( $this, "error_handler" ) );
        }

        private function exception_handler($exception) {
            $this->client->SendException($exception);
        }

        private function error_handler( $errno, $errstr, $errfile, $errline) {
            $this->client->SendError($errno, $errstr, $errfile, $errline);
        }

    }
}
```

**Important:** Make sure you change *apiKey* to your Raygun API key.

### Step 4: In `/config/hooks.php`:

```
$hook['pre_controller'][] = array(
    'class'    => 'RaygunSetup',
    'function' => 'set_exception_handler',
    'filename' => 'RaygunSetup.php',
    'filepath' => 'hooks'
);
```