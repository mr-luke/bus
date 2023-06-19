# Bus - Laravel multi-purpose bus implementation.

[![Latest Stable Version](https://poser.pugx.org/mr-luke/bus/v)](//packagist.org/packages/mr-luke/bus)
[![Total Downloads](https://poser.pugx.org/mr-luke/bus/downloads)](//packagist.org/packages/mr-luke/bus)
[![License](https://poser.pugx.org/mr-luke/bus/license)](//packagist.org/packages/mr-luke/bus)

![Tests Workflow](https://github.com/mr-luke/bus/actions/workflows/run-testsuit.yaml/badge.svg)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mr-luke_bus&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=mr-luke_bus)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=mr-luke_bus&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=mr-luke_bus)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=mr-luke_bus&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=mr-luke_bus)

* [Getting Started](#getting-started)
* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
* [Plans](#plans)

## If you have used unstable versions

This major `1.0.0` version has a breaking changes compares to `1.0.0-rc.x`. It is required to drop 
database table `bus_processes` & migrate.

## Getting Started

Setting Manager supported versions:
* Laravel 10
* Laravel 9
* Laravel 8

## Installation

To install through composer, simply put the following in your composer.json file and run `composer update`

```json
{
    "require": {
        "mr-luke/bus": "~1.0"
    }
}
```
Or use the following command

```bash
composer require "mr-luke/bus"
```

Next, add the service provider to `app/config/app.php`

```
Mrluke\Bus\BusServiceProvider::class,
```
*Note: Package is auto-discoverable!*

## Configuration

Comming soon...
