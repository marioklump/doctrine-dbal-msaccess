# Doctrine DBAL for Microsoft Access
An extension of the [doctrine/dbal](https://github.com/doctrine/dbal) library to support **Microsoft Access databases** in **Microsoft OS**.

## OS Requirements
- Microsoft Access Database Engine Redistributable ([2010](https://www.microsoft.com/download/details.aspx?id=13255) or [2016](https://www.microsoft.com/download/details.aspx?id=54920)).

## Installation
The recommended way to install this library is through [Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version:

```bash
php composer.phar require zoilomora/doctrine-dbal-msaccess
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

## Configuration in the system
There are 2 options to connect to the database:
- Use a Data Source Name (**DSN**).
- Use the connection data directly.

### Use a Data source name (DSN)

We don't need to reinvent the wheel, on the internet there are hundreds of tutorials on how to set up a DSN for Microsoft Access.
I leave you a [video](https://www.youtube.com/watch?v=biSjA8ms_Wk) that I think explains it perfectly.

Once the DSN is configured we will have to configure the connection in the following way:

```php
$conn = \Doctrine\DBAL\DriverManager::getConnection([
    'driverClass' => \ZoiloMora\Doctrine\DBAL\Driver\MsAccess\Driver::class,
    'dsn' => 'name of the created dsn',
]);
```

### Use the connection data directly

An example of configuration could be this:

```php
$conn = \Doctrine\DBAL\DriverManager::getConnection([
    'driverClass' => \ZoiloMora\Doctrine\DBAL\Driver\MsAccess\Driver::class,
    'odbc_driver' => '{Microsoft Access Driver (*.mdb)}',
    'filename' => 'C:\database.mdb',
]);
```

## Discovered problems

### Character encoding problems
The default character encoding in Access databases is [Windows-1252](https://en.wikipedia.org/wiki/Windows-1252).
If you want to convert the data to UTF-8, a simple solution would be:

```php
$field = mb_convert_encoding($field, 'UTF-8', 'Windows-1252');
```
