## Raygun4PHP CodeIgniter installation instructions

### Step 1: Install Raygun4PHP with Composer (see README.md)

### Step 2: Enable hooks

In `/application/config/config.php` set *enable_hooks* to true:

```
$config['enable_hooks'] = TRUE;
```

### Step 3: Create the file: `/application/hooks/RaygunSetup.php` with the following content:

```
<?php namespace
{
    require_once FCPATH . 'vendor/autoload.php';

    class RaygunSetup {
        private $client;

        public function __construct()
        {
            $this->client = new \Raygun4php\RaygunClient("API_KEY");
        }

        public function set_exception_handler()
        {
            set_exception_handler( array( $this,'exception_handler' ) );
            set_error_handler( array( $this, 'error_handler' ) );
            register_shutdown_function( array( $this, 'fatal_error_handler' ) );
        }

        function exception_handler($exception) {
            $this->client->SendException($exception);
        }

        function error_handler( $errno, $errstr, $errfile, $errline) {
            $this->client->SendError($errno, $errstr, $errfile, $errline);
        }
        
        // Handle fatal errors
        function fatal_error_handler() {
            $last_error = error_get_last();

            if (!is_null($last_error)) {
                $errno = $last_error['type'];
                $errstr = $last_error['message'];
                $errfile = $last_error['file'];
                $errline = $last_error['line'];
                $this->client->SendError($errno, $errstr, $errfile, $errline);
            }
        }

    }
}
```

**Important:** Make sure you change *API_KEY* to your Raygun API key.

### Step 4: In `/config/hooks.php`:

```
$hook['pre_controller'][] = array(
    'class'    => 'RaygunSetup',
    'function' => 'set_exception_handler',
    'filename' => 'RaygunSetup.php',
    'filepath' => 'hooks'
);
```
