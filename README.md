# YadmBundle

## Install

Install library

```bash
$ composer require formapro/yadm-bundle "mikemccabe/json-patch-php:dev-master as 0.1.1"
```

Register the bundle

```php
<?php
# /app/AppKernel.php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new \Formapro\Yadm\Bundle\YadmBundle(),
        ];

        return $bundles;
    }
}
```

## Configure

```yaml
yadm:
    mongo_uri: 'mongodb://mongo:27017/db_name'
    migrations: ~ # enable migrations
    models:
      category:
          class: 'Acme\Model\Category'
          collection: 'category'
      product:
          class: 'Acme\Model\Product'
          collection: 'product'
          hydrator: 'app.product.hydrator'
```

## Usage

In your code you can get the storage from registry:

```php
<?php

$registry = $container->get('yadm');

$productStorage = $registry->getStorage('Acme\Model\Category');
```

## Migrations

Generate new migration class

```bash
$ ./bin/console yadm:migrations:generate
  Generated new migration class to "/app/YadmMigrations/Migration20190301122316.php"
```

Generated migration class example

```php
<?php

declare(strict_types=1);

namespace App\YadmMigrations;

use Formapro\Yadm\Migration\Migration;
use Formapro\Yadm\Registry;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Migration20190301122316 implements Migration
{
    public function execute(Registry $yadm): void
    {

    }
}
```

Execute migrations

```bash
$ ./bin/console yadm:migrations:migrate
  Next migrations will be executed: 20190301122316, 20190301122500, 20190301122502
  WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)
  Execute migration: 20190301122316
  Execute migration: 20190301122500
  Execute migration: 20190301122502
  
    ------------------------
    ++ finished in 0s
    ++ 3 migrations executed
```


## Licence

MIT
