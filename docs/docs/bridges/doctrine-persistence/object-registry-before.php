<?php

declare(strict_types=1);

use App\Entity\Product;
use Doctrine\Persistence\ObjectRepository;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

final class DenormalizeProductProcessor implements ItemProcessorInterface
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
