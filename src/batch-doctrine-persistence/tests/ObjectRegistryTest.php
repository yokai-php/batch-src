<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence;

use Doctrine\ORM\Configuration;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy\DecoratedRepositoryFactory;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy\FindOneByCalledOnlyOnceWhenFoundRepositoryDecorator;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Auth\User;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Shop\Product;

class ObjectRegistryTest extends DoctrinePersistenceTestCase
{
    private User $emmet;
    private User $lucy;
    private Product $galaxyExplorer;
    private Product $boutiqueHotel;

    protected function setUpConfigs(Configuration $authConfig, Configuration $shopConfig): void
    {
        $shopConfig->setRepositoryFactory(
            new DecoratedRepositoryFactory(
                FindOneByCalledOnlyOnceWhenFoundRepositoryDecorator::class,
                $shopConfig->getRepositoryFactory()
            )
        );
    }

    protected function setUpFixtures(): void
    {
        $this->authManager->persist($this->emmet = new User('Emmet'));
        $this->authManager->persist($this->lucy = new User('Lucy'));
        $this->authManager->flush();

        $this->shopManager->persist($this->galaxyExplorer = new Product('Galaxy Explorer'));
        $this->shopManager->persist($this->boutiqueHotel = new Product('Boutique Hotel'));
        $this->shopManager->flush();
    }

    public function testFindOneBy(): void
    {
        $registry = new ObjectRegistry($this->doctrine);

        foreach ([1, 2] as $ignored) {
            self::assertSame($this->emmet, $registry->findOneBy(User::class, ['name' => 'Emmet']));
            self::assertSame($this->lucy, $registry->findOneBy(User::class, ['name' => 'Lucy']));
            self::assertNull($registry->findOneBy(User::class, ['name' => 'John']));

            self::assertSame($this->galaxyExplorer, $registry->findOneBy(Product::class, ['name' => 'Galaxy Explorer']));
            self::assertSame($this->boutiqueHotel, $registry->findOneBy(Product::class, ['name' => 'Boutique Hotel']));
            self::assertNull($registry->findOneBy(Product::class, ['name' => 'Haunted House']));
        }
    }

    public function testFindOneUsing(): void
    {
        $registry = new ObjectRegistry($this->doctrine);

        $closureFactory = function (ObjectManager $expectedManager, string $expectedEntityClass, array $criteria) {
            return function (ObjectRepository $repository, ObjectManager $manager) use (
                $expectedManager,
                $expectedEntityClass,
                $criteria,
            ) {
                self::assertSame($expectedManager, $manager);
                self::assertSame($expectedEntityClass, $repository->getClassName());

                return $repository->findOneBy($criteria);
            };
        };

        $emmetClosure = $closureFactory($this->authManager, User::class, ['name' => 'Emmet']);
        $lucyClosure = $closureFactory($this->shopManager, User::class, ['name' => 'Lucy']);
        $johnClosure = $closureFactory($this->shopManager, User::class, ['name' => 'John']);
        $galaxyExplorerClosure = $closureFactory($this->shopManager, Product::class, ['name' => 'Galaxy Explorer']);
        $boutiqueHotelClosure = $closureFactory($this->shopManager, Product::class, ['name' => 'Boutique Hotel']);
        $hauntedHouseClosure = $closureFactory($this->shopManager, Product::class, ['name' => 'Haunted House']);

        foreach ([1, 2] as $ignored) {
            self::assertSame($this->emmet, $registry->findOneUsing(User::class, $emmetClosure));
            self::assertSame($this->lucy, $registry->findOneUsing(User::class, $lucyClosure));
            self::assertNull($registry->findOneUsing(User::class, $johnClosure));

            self::assertSame($this->galaxyExplorer, $registry->findOneUsing(Product::class, $galaxyExplorerClosure));
            self::assertSame($this->boutiqueHotel, $registry->findOneUsing(Product::class, $boutiqueHotelClosure));
            self::assertNull($registry->findOneUsing(Product::class, $hauntedHouseClosure));
        }
    }
}
