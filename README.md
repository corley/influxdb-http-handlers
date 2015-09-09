# InfluxDB response handlers

Thanks to GuzzleHTTP middleware infrastructure we can convert HTTP responses
from InfluxDB in a more simple data structure, in the same way we can also
manage query errors.

## Convert your data structure
Actually the InfluxDB-PHP-SDK (by Corley) do not manage the JSON messages
received from InfluxDB. That mean that you have to deal with this kind of data:

```php
array(1) {
  'results' =>
  array(1) {
    [0] =>
    array(1) {
      'series' =>
      array(1) {
        ...
      }
    }
  }
}
```

If you prefere a more simple data structure, you can append response handlers to
your GuzzleHTTP client in order to convert the InfluxDB response directly at
runtime and obtain something more simple and readable

```php
array(1) {
  'cpu' => array(2) {
    [0] => array(4) {
      'time' => string(30) "2015-09-09T20:42:07.927267636Z"
      'value1' => int(1)
      'value2' => int(2)
      'valueS' => string(6) "string"
    }
    [1] => array(4) {
      'time' => string(30) "2015-09-09T20:42:51.332853369Z"
      'value1' => int(2)
      'value2' => int(4)
      'valueS' => string(11) "another-one"
    }
  }
}
```

That is more simple to use than the InfluxDB default response

### Append the response handler

During your GuzzleHTTP client setup just push the `message_handler` handler

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as HttpClient;
use InfluxDB\Client;
use InfluxDB\Options;
use InfluxDB\Adapter\GuzzleAdapter;

$stack = new HandlerStack();
$stack->setHandler(new CurlHandler());
$stack->push(\InfluxDB\Handler\message_handler()); // Push the response handler

$http = new HttpClient(['handler' => $stack]);

$options = new Options();
$adapter = new GuzzleAdapter($http, $options);

$client = new Client($adapter);
```

## Error/Exception management

Using the same approach you can convert InfluxDB errored responses in PHP
Exception using the `exception_handler`. In that way when a query fail with an
error message the handler convert that failure in a valid PHP Exception
`UnexceptionValueException`

Just push the `exception_handler` layer in your GuzzleHTTP client

```php
$stack->push(\InfluxDB\Handler\exception_handler()); // Push the response handler
```

## Layer order

Handlers that modifies the InfluxDB response should be placed in order:

 * `exception_hanlder` as first
 * `message_handler`

You can append how many handlers you want

```php
$stack->push(\InfluxDB\Handler\exception_handler());
$stack->push(\InfluxDB\Handler\message_handler());
```

In that way you will get new responses format and error messages

