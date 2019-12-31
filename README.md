# mezzio-router

[![Build Status](https://travis-ci.org/mezzio/mezzio-router.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-router)

Router subcomponent for [Mezzio](https://github.com/mezzio/mezzio].

This package provides the following classes and interfaces:

- `RouterInterface`, a generic interface to implement for providing routing
  capabilities around [PSR-7](http://www.php-fig.org/psr/psr-7/)
  `ServerRequest` messages.
- `Route`, a value object describing routed middleware.
- `RouteResult`, a value object describing the results of routing.
- `RouteResultObserverInterface`, which allows you to create observers for
  `Mezzio\Application` that will be updated when a `RouteResult` has
  been obtained.

## Installation

Typically, you will install this when installing Mezzio. However, it can be
used standalone to provide a generic way to provide routed PSR-7 middleware. To
do this, use:

```bash
$ composer require mezzio/mezzio-router
```

We currently support and provide the following routing integrations:

- [Aura.Router](https://github.com/auraphp/Aura.Router):
  `composer require mezzio/mezzio-aurarouter`
- [FastRoute](https://github.com/nikic/FastRoute):
  `composer require mezzio/mezzio-fastroute`
- [Laminas MVC Router](https://github.com/laminas/laminas-mvc):
  `composer require mezzio/mezzio-laminasrouter`

## Documentation

Mezzio provides [routing documentation](http://mezzio.readthedocs.org/en/latest/router/intro/).
