# Image-Generator
==========================================

PHP lib for Image-generator.

Requirements
------------
* PHP >=5.4
* imagick extension for PHP

**Note**: 

Installation
------------
You must install this library through Composer:

```bash
# Install Composer
brew install composer

# Install lib dependencies through composer
composer install
```

Once you have installed the library, you will need to require image-generator based on your need. To do this, place the following line of PHP code at the top of your application's PHP files:

```php
require_once __DIR__ . "/image-generator/lib/ImageGenerator.php";
or
require_once __DIR__ . "/image-generator/lib/RackspaceIG.php";
or
require_once __DIR__ . "/image-generator/lib/S3IG.php";
```

**Note**: you can use Rackspace or S3 cloud to store your images.

And you're ready to go!


- - -
