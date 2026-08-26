# Solution Notes

A small PHP library for consuming the Great Food Ltd REST API, built on Guzzle,
plus the two scenario scripts required by the test. See `README.md` for the
original task description.

## Requirements

- PHP 8.1+
- Composer (dependencies: `guzzlehttp/guzzle`; dev: `phpunit/phpunit`)

## Setup and running

```bash
composer install

php scenarios/scenario1.php   # Takeaway menu products as a table
php scenarios/scenario2.php   # Fix product 84 "Chpis" -> "Chips" + proof of the PUT
vendor/bin/phpunit            # Test suite
```

The scenario scripts also work through a web server such as XAMPP: open
`scenarios/scenario1.php` or `scenario2.php` in a browser.

The Great Food API does not actually exist, so the scripts default to Guzzle's
`MockHandler` fed by the JSON fixtures in `responses/`. Running against a real
server needs no code changes:

```bash
GREAT_FOOD_API_URL=https://api.greatfood.example php scenarios/scenario1.php
```

## Architecture

```
src/
  Client/ApiClient.php                 Thin Guzzle wrapper: bearer-token handling,
                                       JSON decoding/validation
  Auth/AccessToken.php                 Token value object with expiry tracking
  GreatFood/AuthenticationService.php  OAuth client-credentials flow
  GreatFood/MenuService.php            GET /menus, find a menu id by name
  GreatFood/ProductService.php         GET products, PUT product updates
  Model/Product.php                    Typed product model (mirrors the API shape)
scenarios/                             The two scenarios + shared setup
tests/                                 PHPUnit tests using Guzzle MockHandler
responses/                             Fixture files supplied with the test
```

## Design decisions

Guzzle sits behind an `ApiClient` wrapper. The services depend on a small
surface (`get`/`post`/`put`) rather than on Guzzle directly, so the HTTP library
could be swapped without touching business code. Guzzle's `http_errors`
behaviour means 4xx and 5xx responses surface as exceptions instead of bad data.

There is one service per API concern (auth, menus, products), which keeps each
class single-purpose and independently testable.

Authentication is transparent. Constructing `AuthenticationService` registers it
as the `ApiClient`'s token provider. The first request triggers the
client-credentials POST lazily, the token is cached and reused, and a fresh one
is fetched automatically shortly before expiry (30s leeway, with an injectable
clock so the expiry logic can be unit-tested). Callers never touch tokens, and a
missing `access_token` in the response throws rather than failing silently.

`scenarios/setup.php` acts as a small composition root. One
`greatFoodApiClient()` factory builds the Guzzle stack, wires in authentication
and returns a ready-to-use client, so each scenario declares only its own mock
queue and business logic. Credentials live there as constants with
environment-variable overrides, mirroring `GREAT_FOOD_API_URL`.

Tests use Guzzle's `MockHandler` together with the history middleware, so they
assert not just return values but the actual wire format: form-encoded auth
body, bearer headers, PUT path and JSON payload.

## Scenario notes

Scenario 1 authenticates, resolves the "Takeaway" menu id by name from
`GET /menus` rather than hard-coding it, fetches its products and prints the
ID/name table.

Scenario 2 first GETs menu 7's products so the PUT body matches the full product
model returned by the API, corrects the name, sends `PUT /menu/7/product/84`,
and prints the recorded request (method, path, headers, body) plus the response
as proof.

One note on the fixtures: the files supplied with the test have no data for the
menu 7 / product 84 that scenario 2 refers to, so `responses/menu-products.json`
has had the product 84 "Chpis" row added. The mock handler serves queued
responses regardless of path, so that single products fixture answers the GET in
both scenarios, and scenario 2 still issues `PUT /menu/7/product/84` exactly as
the brief specifies.
