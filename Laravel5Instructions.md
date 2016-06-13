### Laravel 5 Setup Instructions

#### Step 1. Install the *laravel-raygun* wrapper with Composer

See instructions: https://github.com/davibennun/laravel-raygun

#### Step 2. Add Raygun into your ExceptionHandler report method

File: `app\Exceptions\Handler.php`

Add this line before the class declaration:

```
use Raygun;
```

To send **all** exceptions to Raygun, add this line into the `report` method :

```
public function report(Exception $e)
{
  // Send all exceptions:
  Raygun::SendException($e);

  parent::report($e);
}
```

Alternatively, you can select which exceptions you want to send using the _instanceof_ operator:

```
public function report(Exception $e)
{
  // Send only a particular type of exception:
  if($e instanceof MyCustomException) {
    Raygun::SendException($e);
  }

  parent::report($e);
}
```
