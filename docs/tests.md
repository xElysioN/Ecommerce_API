# Tests

For all our tests, we're using [PHPUnit](https://phpunit.de/) which is included
in our dev requirements.

## Smoke Tests

https://symfony.com/doc/current/best_practices.html#smoke-test-your-urls

### Execute smoke tests

```
make test-smoke
```

### Adding a new url to test

In this file :
`tests/Smoke/ApplicationAvailabilityFunctionalTest.php`, you need to add a new
yield in the function `urlProvider`

## Unit Testing

### Execute unit tests

```
make test-unit
```

## Functional Testing

### Execute functional tests

```
make test-functional
```
