# MemoryStorage

[![CI](https://github.com/positron48/memorystorage/workflows/CI/badge.svg)](https://github.com/positron48/memorystorage/actions)
[![PHP Version Require](http://poser.pugx.org/positron48/memorystorage/require/php)](https://packagist.org/packages/positron48/memorystorage)
[![Latest Stable Version](http://poser.pugx.org/positron48/memorystorage/v)](https://packagist.org/packages/positron48/memorystorage)
[![License](http://poser.pugx.org/positron48/memorystorage/license)](https://packagist.org/packages/positron48/memorystorage)

Library that will help you to store data in shared memory using PHP's System V shared memory functions.

## Features

- Store arrays of integers in shared memory
- Thread-safe access using semaphores
- Support for multiple independent storage instances
- Automatic memory management and cleanup
- Compatible with PHP 8.0, 8.1, 8.2, and 8.3

## Requirements

- PHP 8.0 or higher
- System V shared memory extension (`sysvsem`, `sysvshm`)
- `malkusch/lock` package for mutex functionality

## Install

```bash
composer require positron48/memorystorage
```

## Usage

```php
<?php
require 'vendor/autoload.php';

use MemoryStorage\ArrayMemoryStorage;

// Create a storage for 3 integers
$storage = new ArrayMemoryStorage('my_counter', 3);

// Set data
$storage->set([100, 200, 300]);

// Get data
$data = $storage->get(); // Returns [100, 200, 300]

// Use mutex for thread-safe operations
$mutex = $storage->getMutex();
$mutex->synchronized(function () use ($storage) {
    $current = $storage->get();
    $current[0]++;
    $storage->set($current);
});

// Clean up (removes shared memory and semaphore)
$storage->remove();
```

## Testing

The library includes comprehensive tests covering all functionality:

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/ArrayMemoryStorageWorkingTest.php
```

## Supported PHP Versions

This library is tested against:
- PHP 8.0 (recommended - library was originally designed for this version)
- PHP 8.1  
- PHP 8.2
- PHP 8.3 (may have compatibility issues due to changes in shared memory handling)

**Note**: The current implementation has known limitations on newer PHP versions due to memory allocation calculations. The library works best on PHP 8.0 for which it was originally designed.

## Development

### Running Tests Locally

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`

### Continuous Integration

The project uses GitHub Actions for CI/CD with the following workflows:

- **CI**: Runs tests on all supported PHP versions
- **Test Matrix**: Extended testing with different dependency versions and OS combinations

## License

This library is licensed under the GPL-3.0-or-later license. See the [LICENSE](LICENSE) file for details.