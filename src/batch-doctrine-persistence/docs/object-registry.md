# Object registry

Imagine that in an `ItemJob` you need to find objects from a database.

```php
use App\Entity\Product;
use Doctrine\Persistence\ObjectRepository;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

class DenormalizeProductProcessor implements ItemProcessorInterface
{
    public function __construct(
        private ObjectRepository $repository,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     */
    public function process(mixed $item): Product
    {
        $product = $this->repository->findOneBy(['sku' => $item['sku']]);

        $product ??= new Product($item['sku']);

        $product->setName($item['name']);
        $product->setPrice($item['price']);
        //...

        return $product;
    }
}
```

The problem here is that every time you will call `findOneBy`, you will have to query the database.
The object might already be in Doctrine's memory, so it won't be hydrated twice, but the query will be done every time.

The role of the `ObjectRegistry` is to remember found objects identities, and query these objects with it instead.

```diff
use App\Entity\Product;
-use Doctrine\Persistence\ObjectRepository;
+use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

class DenormalizeProductProcessor implements ItemProcessorInterface
{
    public function __construct(
-        private ObjectRepository $repository,
+        private ObjectRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     */
    public function process(mixed $item): Product
    {
-        $product = $this->repository->findOneBy(['sku' => $item['sku']]);
+        $product = $this->registry->findOneBy(Product::class, ['sku' => $item['sku']]);

        $product ??= new Product($item['sku']);

        $product->setName($item['name']);
        $product->setPrice($item['price']);
        //...

        return $product;
    }
}
```

The first time, the query will hit the database, and the object identity will be remembered in the registry.
Everytime after that, the registry will call `Doctrine\Persistence\ObjectManager::find` instead.
If the object is still in Doctrine's memory, it will be returned directly.
Otherwise, the query will be the fastest possible because it will use the object identity.


## On the same subject

- [What is an item job ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job.md)
