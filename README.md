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

## Licence

MIT
