## SimpleMVC

[![Build status](https://github.com/simplemvc/framework/workflows/PHP%20test/badge.svg)](https://github.com/simplemvc/framework/actions)

**SimpleMVC** is an [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) framework
for PHP based on the [KISS](https://en.wikipedia.org/wiki/KISS_principle) principle:

> "Keep It Simple, Stupid"

The goal of this project is to offer a simple to use and fast framework for PHP applications
using [PSR](https://www.php-fig.org/psr/) standards.

SimpleMVC uses the [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) pattern to manage
the dependencies between classes and the [FastRoute](https://github.com/nikic/FastRoute) library
for implementing the routing system.

SimpleMVC uses the following PSR standards, from the [PHP-FIG](https://www.php-fig.org/) initiative:

- [PSR-11](https://www.php-fig.org/psr/psr-11/) for DI Container;
- [PSR-7](https://www.php-fig.org/psr/psr-7/) for HTTP message;
- [PSR-3](https://www.php-fig.org/psr/psr-3/) for logging;

This project was born as educational library for the course **PHP Programming** by [Enrico Zimuel](https://www.zimuel.it/)
at [ITS ICT Piemonte](http://www.its-ictpiemonte.it/) in Italy.

Since than, the project has been evoluted and used also for building web application
in production. We decided to create a more general purpose project and this was the beginning
of this repository.

## Introduction

SimpleMVC implements the [Model–View–Controller](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) (MVC)
architectural pattern using [Dependency injection](https://en.wikipedia.org/wiki/Dependency_injection)
and [PSR](https://www.php-fig.org/psr/) standards.

A SimpleMVC application looks as follows:

```php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use DI\ContainerBuilder;
use SimpleMVC\App;
use SimpleMVC\Emitter\SapiEmitter;

$builder = new ContainerBuilder();
$builder->addDefinitions('config/container.php');
$container = $builder->build();

$app = new App($container);
$app->bootstrap(); // optional
$response = $app->dispatch(); // PSR-7 response

SapiEmitter::emit($response);
```

In this example we use a DI container with [PHP-DI](https://php-di.org/). We create a `SimpleMVC\App` object
using the previous container. 

The application can be configured using the `config/container.php` file. An example is as follows:

```php
// config/container.php
use App\Controller;

return [
    'config' => [
        'routing' => [
            'routes' => [
                [ 'GET', '/', Controller\HomePage::class ]
            ]
        ]
    ]
];
```

The steps to manage the request are:

- `bootstrap()` (optional), here you can specify any bootstrap requirements;
- `dispatch()`, where the HTTP request is dispatched, executing the controller specified in the route.

The `dispatch()` returns a PSR-7 response. Finally, we can render the response using an emitter.
In this example we used a `SapiEmitter` to render the PSR-7 response in the standard output.

The previous PHP script is basically a front controller of an MVC application (see diagram below).

![MVC diagram](doc/mvc.png)

In this diagram the front controller is stored in a `public/index.php` file. 
The `public` folder is usually the document root of a web server.

## Quickstart

You can start using the framework with the [skeleton](https://github.com/simplemvc/skeleton) application.

## Copyright

The author of this software is [Enrico Zimuel](https://github.com/ezimuel/) and other [contributors](https://github.com/simplemvc/framework/graphs/contributors).

This software is released under the [MIT License](/LICENSE).
