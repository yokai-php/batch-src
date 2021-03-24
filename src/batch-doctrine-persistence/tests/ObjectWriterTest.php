<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Exception\InvalidArgumentException;

class ObjectWriterTest extends TestCase
{
    use ProphecyTrait;

    public function testWrite()
    {
        $user1 = new User('1');
        $user2 = new User('2');
        $group1 = new Group('1');

        /** @var ObjectProphecy|ObjectManager $userManager */
        $userManager = $this->prophesize(ObjectManager::class);
        $userManager->persist($user1)
            ->shouldBeCalledTimes(1);
        $userManager->persist($user2)
            ->shouldBeCalledTimes(1);
        $userManager->flush()
            ->shouldBeCalledTimes(1);
        $userManager->clear()
            ->shouldBeCalledTimes(1);

        /** @var ObjectProphecy|ObjectManager $groupManager */
        $groupManager = $this->prophesize(ObjectManager::class);
        $groupManager->persist($group1)
            ->shouldBeCalledTimes(1);
        $groupManager->flush()
            ->shouldBeCalledTimes(1);
        $groupManager->clear()
            ->shouldBeCalledTimes(1);

        /** @var ObjectProphecy|ObjectManager $productManager */
        $productManager = $this->prophesize(ObjectManager::class);
        $productManager->persist(Argument::any())
            ->shouldNotBeCalled();
        $productManager->flush()
            ->shouldNotBeCalled();
        $productManager->clear()
            ->shouldNotBeCalled();

        /** @var ObjectProphecy|ManagerRegistry $doctrine */
        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(User::class)
            ->shouldBeCalledTimes(1)
            ->willReturn($userManager->reveal());
        $doctrine->getManagerForClass(Group::class)
            ->shouldBeCalledTimes(1)
            ->willReturn($groupManager->reveal());
        $doctrine->getManagerForClass(Product::class)
            ->shouldNotBeCalled()
            ->willReturn($productManager->reveal());

        $writer = new ObjectWriter($doctrine->reveal());
        $writer->write([$user1, $user2, $group1]);
    }

    public function testWriteThrowExceptionWithNonObject()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var ObjectProphecy|ManagerRegistry $doctrine */
        $doctrine = $this->prophesize(ManagerRegistry::class);

        $writer = new ObjectWriter($doctrine->reveal());
        $writer->write(['string']);
    }

    public function testWriteThrowExceptionWithNonManagedObjects()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var ObjectProphecy|ManagerRegistry $doctrine */
        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(User::class)
            ->willReturn(null);

        $writer = new ObjectWriter($doctrine->reveal());
        $writer->write([new User('1')]);
    }
}
