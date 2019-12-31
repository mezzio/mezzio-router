# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.1.0 - 2015-12-06

### Added

- [zendframework/zend-expressive-router#4](https://github.com/zendframework/zend-expressive-router/pull/4) adds
  `RouteResultSubjectInterface`, a complement to `RouteResultObserverInterface`,
  defining the following methods:
  - `attachRouteResultObserver(RouteResultObserverInterface $observer)`
  - `detachRouteResultObserver(RouteResultObserverInterface $observer)`
  - `notifyRouteResultObservers(RouteResult $result)`

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-router#4](https://github.com/zendframework/zend-expressive-router/pull/4) removes
  the deprecation notice from `RouteResultObserverInterface`.

### Fixed

- Nothing.

## 1.0.1 - 2015-12-03

### Added

- Nothing.

### Deprecated

- [zendframework/zend-expressive-router#3](https://github.com/zendframework/zend-expressive-router/pull/3) deprecates `RouteResultObserverInterface`, which
  [has been moved to the `Mezzio` namespace and package](https://github.com/zendframework/zend-expressive/pull/206).

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-router#1](https://github.com/zendframework/zend-expressive-router/pull/1) fixes the
  coveralls support to trigger after scripts, so the status of the check does
  not make the tests fail. Additionally, ensured that coveralls can receive
  the coverage report!

## 1.0.0 - 2015-12-02

First stable release.

See the [Mezzio CHANGELOG](https://github.com/mezzio/mezzio/blob/master/CHANGELOG.md]
for a history of changes prior to 1.0.
