<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Job;

use League\Flysystem\CorruptedPathDetected;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\League\Flysystem\Job\MoveFilesJob;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy\CannotDeleteMemoryAdapter;
use Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy\CannotReadMemoryAdapter;
use Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy\CannotWriteMemoryAdapter;

class MoveFilesJobTest extends TestCase
{
    public function testWithOneFile(): void
    {
        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('file.txt', 'SOURCE TO BE MOVED');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute(JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertFalse($source->has('file.txt'), 'file.txt does exists anymore on source filesystem');
        self::assertTrue($destination->has('file.txt'), 'file.txt now exists on destination filesystem');
        self::assertSame('SOURCE TO BE MOVED', $destination->read('file.txt'));
    }

    public function testWithMultipleFiles(): void
    {
        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('file-1.txt', 'FILE 1');
        $source->write('file-2.txt', 'FILE 2');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor(['file-1.txt', 'file-2.txt']);

        self::assertFalse($destination->has('file-1.txt'), 'file-1.txt does not exists yet on destination filesystem');
        self::assertFalse($destination->has('file-2.txt'), 'file-2.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute(JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertFalse($source->has('file-1.txt'), 'file-1.txt does exists anymore on source filesystem');
        self::assertTrue($destination->has('file-1.txt'), 'file-1.txt now exists on destination filesystem');
        self::assertSame('FILE 1', $destination->read('file-1.txt'));
        self::assertFalse($source->has('file-2.txt'), 'file-2.txt still does exists anymore on source filesystem');
        self::assertTrue($destination->has('file-2.txt'), 'file-2.txt now exists on destination filesystem');
        self::assertSame('FILE 2', $destination->read('file-2.txt'));
    }

    public function testWithPathTransformation(): void
    {
        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('source-file.txt', 'SOURCE TO BE MOVED');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('source-file.txt');
        $transformDestination = fn() => 'destination-file.txt';

        self::assertFalse(
            $destination->has('source-file.txt'),
            'file-1.txt does not exists yet on destination filesystem'
        );
        self::assertFalse(
            $destination->has('destination-file.txt'),
            'destination-file.txt does not exists yet on destination filesystem'
        );

        $job = new MoveFilesJob($location, $source, $destination, $transformDestination);
        $job->execute(JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertFalse($source->has('source-file.txt'), 'source-file.txt does exists anymore on source filesystem');
        self::assertFalse(
            $destination->has('source-file.txt'),
            'source-file.txt still not exists on destination filesystem'
        );
        self::assertTrue(
            $destination->has('destination-file.txt'),
            'destination-file.txt now exists on destination filesystem'
        );
        self::assertSame('SOURCE TO BE MOVED', $destination->read('destination-file.txt'));
    }

    public function testCannotReadSource(): void
    {
        $source = new Filesystem(new CannotReadMemoryAdapter(new UnableToReadFile()));
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($source->has('file.txt'), 'file.txt does not exists on source filesystem');
        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertFalse($destination->has('file.txt'), 'file.txt still not exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString('Unable to read file from filesystem.', (string)$execution->getLogs());
    }

    public function testFilesystemExceptionOnRead(): void
    {
        $source = new Filesystem(new CannotReadMemoryAdapter(new CorruptedPathDetected()));
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertFalse($destination->has('file.txt'), 'file.txt still not exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
    }

    public function testCannotWriteDestination(): void
    {
        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('file.txt', 'SOURCE THAT WILL NOT BE MOVED');
        $destination = new Filesystem(new CannotWriteMemoryAdapter(new UnableToWriteFile()));
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertTrue($source->has('file.txt'), 'file.txt still exists on source filesystem');
        self::assertFalse($destination->has('file.txt'), 'file.txt still not exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString('Unable to write file to filesystem.', (string)$execution->getLogs());
    }

    public function testFilesystemExceptionOnWrite(): void
    {
        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('file.txt', 'SOURCE THAT WILL NOT BE MOVED');
        $destination = new Filesystem(new CannotWriteMemoryAdapter(new CorruptedPathDetected()));
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertTrue($source->has('file.txt'), 'file.txt still exists on source filesystem');
        self::assertFalse($destination->has('file.txt'), 'file.txt still not exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString('Unable to move file.', (string)$execution->getLogs());
    }

    public function testCannotDeleteDestination(): void
    {
        $source = new Filesystem(new CannotDeleteMemoryAdapter(new UnableToDeleteFile()));
        $source->write('file.txt', 'SOURCE THAT WILL BE MOVED BUT NOT REMOVED');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertTrue($source->has('file.txt'), 'file.txt still exists on source filesystem');
        self::assertTrue($destination->has('file.txt'), 'file.txt now exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString('Unable to delete file from filesystem.', (string)$execution->getLogs());
    }

    public function testFilesystemExceptionOnDelete(): void
    {
        $source = new Filesystem(new CannotDeleteMemoryAdapter(new CorruptedPathDetected()));
        $source->write('file.txt', 'SOURCE THAT WILL BE MOVED BUT NOT REMOVED');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('file.txt');

        self::assertFalse($destination->has('file.txt'), 'file.txt does not exists yet on destination filesystem');

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute($execution = JobExecution::createRoot('123456', 'phpunit-move-file'));

        self::assertTrue($source->has('file.txt'), 'file.txt still exists on source filesystem');
        self::assertTrue($destination->has('file.txt'), 'file.txt now exists on destination filesystem');
        self::assertCount(1, $execution->getFailures());
        self::assertStringContainsString('Unable to move file.', (string)$execution->getLogs());
    }

    public function testWrongLocationType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expecting argument to be string|string[], but got null.');

        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor(null);

        $job = new MoveFilesJob($location, $source, $destination);
        $job->execute(JobExecution::createRoot('123456', 'phpunit-move-file'));
    }

    public function testWrongDestinationType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expecting argument to be string, but got null.');

        $source = new Filesystem(new InMemoryFilesystemAdapter());
        $source->write('file.txt', 'FILE');
        $destination = new Filesystem(new InMemoryFilesystemAdapter());
        $location = new StaticValueParameterAccessor('source');
        $transformation = fn() => null;

        $job = new MoveFilesJob($location, $source, $destination, $transformation);
        $job->execute(JobExecution::createRoot('123456', 'phpunit-move-file'));
    }
}
