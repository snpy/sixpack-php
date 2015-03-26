# Sixpack

PHP client library for SeatGeak's [Sixpack](https://github.com/sixpack/sixpack) ab testing framework.

## Installation

1. Install [Composer](https://getcomposer.org/doc/00-intro.md).
2. In the root of your project install sixpack via `composer require snpy/sixpack-php`.
3. If computer autoloader is not already included put `require 'vendor/autoload.php';` at the top of you script.

## Usage

Basic example:

The PHP client stores a unique client id in the current user's cookie by default.

```PHP
use SeatGeak\Sixpack\Session\Session;

$response = (new Session())->participate('experiment-name', ['blue', 'red']);
$variant  = $response->getAlternativeName();
if ('blue' === $variant) {
    //
    // do something "blue"
    //
} else {
    //
    // do something "red"
    //
}
```

Each session has a `client_id` associated with it that must be preserved across requests. The PHP client handles this automatically. If you'd wish to change that behaviour, you can do so like this:

```PHP
use SeatGeak\Sixpack\Session\Session;

$response = (new Session())->participate('experiment-name', ['blue', 'red']);
PDO::saveClientId($response->getClientId());
```

For future requests, create the `Session` using the `client_id` stored in the cookie:

```PHP
use SeatGeak\Sixpack\Session\Session;

$session = new Session(['clientId' => PDO::findClientId()]);
$session->convert('experiment-name');
```

All possible options for the Session c-tor:

  * _clientId_ - custom client ID; use when you don't want to use cookies; default: NULL,
  * _baseUrl_ - Sixpack Server's location on the web; default: http://localhost:5000,
  * _cookiePrefix_ - set the prefix for cookie (not applicable when clientId is set); default: `sixpack`,
  * _forcePrefix_ - set the prefix for "force Sixpack experiment" GET parameter; default: `sixpack-force-`.

If you'd like to force the Sixpack server to return a specific alternative for development or testing, you can do so by passing a query parameter prefixed with `<forcePrefix>` option (see above) to that page being tested.

If you're using default configuration this would look like this:

`http://example.com/?sixpack-force-<experiment name>=<alternative name>`

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git add -p` + `git commit -m 'Added some feature'`; never use `git commit -a`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## License

sixpack-php is released under the [BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause).
