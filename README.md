# YadmBundle

## Install

Install library

```bash
$ composer require makasim/yadm-bundle
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
            new \Makasim\Yadm\Bundle\YadmBundle(),
        ];

        return $bundles;
    }
}
```

## Configure

```yaml
yadm:
    mongo_uri: 'mongodb://mongo:27017/'
    models:
      category:
          class: 'Acme\Model\Category'
          collection: 'category'
          database: 'acme'
      product:
          class: 'Acme\Model\Product'
          collection: 'product'
          hydrator: 'app.product.hydrator'
          database: 'acme'
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