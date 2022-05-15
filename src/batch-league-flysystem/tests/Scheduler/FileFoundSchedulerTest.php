<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Scheduler;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use Yokai\Batch\Bridge\League\Flysystem\Scheduler\FileFoundScheduler;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy\CannotCheckMemoryAdapter;
use Yokai\Batch\Trigger\Scheduler\ScheduledJob;

class FileFoundSchedulerTest extends TestCase
{
    public function testNoConfig(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');
        $execution = JobExecution::createRoot('123', 'schedule-jobs');

        $scheduler = new FileFoundScheduler($filesystem, $location, 'test.scheduled');

        $scheduled = $scheduler->get($execution);
        self::assertSame([], $scheduled, 'file.txt does not exists on filesystem, no job scheduled');

        $filesystem->write('file.txt', 'UNUSED');
        $scheduled = $scheduler->get($execution);
        self::assertEquals(
            [new ScheduledJob('test.scheduled')],
            $scheduled,
            'file.txt exists on filesystem, job was scheduled'
        );
    }

    public function testStaticConfig(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');
        $execution = JobExecution::createRoot('123', 'schedule-jobs');

        $scheduler = new FileFoundScheduler(
            $filesystem,
            $location,
            'test.scheduled',
            ['parameter' => 'value', 'important' => true],
            'fake.job.id'
        );

        $scheduled = $scheduler->get($execution);
        self::assertSame([], $scheduled, 'file.txt does not exists on filesystem, no job scheduled');

        $filesystem->write('file.txt', 'UNUSED');
        $scheduled = $scheduler->get($execution);
        self::assertEquals(
            [new ScheduledJob('test.scheduled', ['parameter' => 'value', 'important' => true], 'fake.job.id')],
            $scheduled,
            'file.txt exists on filesystem, job was scheduled'
        );
    }

    public function testClosureConfig(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');
        $execution = JobExecution::createRoot('123', 'schedule-jobs');

        $scheduler = new FileFoundScheduler(
            $filesystem,
            $location,
            'test.scheduled',
            fn() => ['parameter' => 'value', 'important' => true],
            fn() => 'fake.job.id'
        );

        $scheduled = $scheduler->get($execution);
        self::assertSame([], $scheduled, 'file.txt does not exists on filesystem, no job scheduled');

        $filesystem->write('file.txt', 'UNUSED');
        $scheduled = $scheduler->get($execution);
        self::assertEquals(
            [new ScheduledJob('test.scheduled', ['parameter' => 'value', 'important' => true], 'fake.job.id')],
            $scheduled,
            'file.txt exists on filesystem, job was scheduled'
        );
    }

    public function testWrongLocationType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expecting argument to be string, but got null.');

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor(null);
        $execution = JobExecution::createRoot('123', 'schedule-jobs');

        $scheduler = new FileFoundScheduler($filesystem, $location, 'test.scheduled');

        $scheduler->get($execution);
    }

    public function testFilesystemException(): void
    {
        $filesystem = new Filesystem(new CannotCheckMemoryAdapter(new UnableToCheckExistence()));
        $location = new StaticValueParameterAccessor('file.txt');
        $execution = JobExecution::createRoot('123', 'schedule-jobs');

        $scheduler = new FileFoundScheduler($filesystem, $location, 'test.scheduled');

        $filesystem->write('file.txt', 'UNUSED');
        $scheduled = $scheduler->get($execution);
        self::assertSame(
            [],
            $scheduled,
            'file.txt exists on filesystem but got exception on checking existence, no job scheduled'
        );
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString(
            'Unable to assert that location exists on filesystem.',
            (string)$execution->getLogs()
        );
    }
}
