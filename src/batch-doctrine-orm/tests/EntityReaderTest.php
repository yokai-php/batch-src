<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\ORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yokai\Batch\Bridge\Doctrine\ORM\EntityReader;
use Yokai\Batch\Exception\UnexpectedValueException;

class EntityReaderTest extends TestCase
{
    use ProphecyTrait;

    private const ENTITY = 'App\Entity\User';

    public function testRead()
    {
        /** @var ObjectProphecy|AbstractQuery $query */
        $query = $this->prophesize(AbstractQuery::class);
        $query->toIterable()
            ->shouldBeCalledTimes(1)
            ->willReturn(
                new \ArrayIterator([[$user1 = new User('1')], [$user2 = new User('2')], [$user3 = new User('3')]])
            );

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('e')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(self::ENTITY)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query->reveal()));

        /** @var ObjectProphecy|EntityManager $manager */
        $manager = $this->prophesize(EntityManager::class);
        $manager->createQueryBuilder()
            ->shouldBeCalledTimes(1)
            ->willReturn($queryBuilder);

        /** @var ObjectProphecy|ManagerRegistry $doctrine */
        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(self::ENTITY)
            ->shouldBeCalledTimes(1)
            ->willReturn($manager->reveal());

        $reader = new EntityReader($doctrine->reveal(), self::ENTITY);
        $entities = $reader->read();

        self::assertInstanceOf(\Generator::class, $entities);
        self::assertSame([$user1, $user2, $user3], iterator_to_array($entities));
    }

    public function testReadExceptionWithUnknownEntityClass()
    {
        $this->expectException(UnexpectedValueException::class);

        /** @var ObjectProphecy|ManagerRegistry $doctrine */
        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(self::ENTITY)
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $reader = new EntityReader($doctrine->reveal(), self::ENTITY);
        iterator_to_array($reader->read()); // the method is using a yield expression
    }
}
