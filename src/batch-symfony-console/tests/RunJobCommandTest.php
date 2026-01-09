<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yokai\Batch\Bridge\Symfony\Console\RunJobCommand;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;
use Yokai\Batch\Warning;

class RunJobCommandTest extends TestCase
{
    private const JOBNAME = 'testing';

    private MockObject&JobInterface $job;
    private JobExecutionAccessor $accessor;
    private JobExecutor $executor;

    protected function setUp(): void
    {
        $this->job = $this->createMock(JobInterface::class);

        $this->accessor = new JobExecutionAccessor(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator(), new NullJobExecutionParametersBuilder()),
            new InMemoryJobExecutionStorage(),
        );
        $this->executor = new JobExecutor(
            JobRegistry::fromJobArray([self::JOBNAME => $this->job]),
            new InMemoryJobExecutionStorage(),
            null,
        );
    }

    public function testRunWithMalformedConfiguration(): void
    {
        $this->expectException(JsonException::class);

        $this->job->expects($this->never())->method('execute');
        $this->execute('{]');
    }

    public function testRunWithInvalidConfiguration(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->job->expects($this->never())->method('execute');
        $this->execute('"string"');
    }

    #[DataProvider('verbosity')]
    public function testRunWithErrors(int $verbosity): void
    {
        $this->job->method('execute')
            ->willReturnCallback(function (JobExecution $jobExecution): never {
                $jobExecution->addFailureException(new \RuntimeException('1st exception', 100));
                $jobExecution->addFailureException(new \LogicException('2nd exception', 200));

                throw new \Exception('The exception that failed the job', 300);
            });

        [$code, $display] = $this->execute(null, $verbosity);

        self::assertSame(RunJobCommand::EXIT_ERROR_CODE, $code);

        if ($verbosity === OutputInterface::VERBOSITY_QUIET) {
            self::assertSame('', $display);
        } else {
            self::assertStringContainsString('An error occurred during the testing execution.', $display);
            self::assertStringContainsString('Error #100 of class RuntimeException: 1st exception', $display);
            self::assertStringContainsString('Error #200 of class LogicException: 2nd exception', $display);
            self::assertStringContainsString(
                'Error #300 of class Exception: The exception that failed the job',
                $display,
            );
        }
    }

    #[DataProvider('verbosity')]
    public function testRunWithWarnings(int $verbosity): void
    {
        $this->job->method('execute')
            ->willReturnCallback(function (JobExecution $jobExecution) {
                $jobExecution->addWarning(new Warning('1st warning'));
                $jobExecution->addWarning(new Warning('2nd warning'));
            });

        [$code, $display] = $this->execute(null, $verbosity);

        self::assertSame(RunJobCommand::EXIT_WARNING_CODE, $code);

        if ($verbosity === OutputInterface::VERBOSITY_QUIET) {
            self::assertSame('', $display);
        } else {
            self::assertStringContainsString('testing has been executed with 2 warnings.', $display);
            if ($verbosity > OutputInterface::VERBOSITY_NORMAL) {
                self::assertStringContainsString('1st warning', $display);
                self::assertStringContainsString('2nd warning', $display);
            } else {
                self::assertStringNotContainsString('1st warning', $display);
                self::assertStringNotContainsString('2nd warning', $display);
            }
        }
    }

    #[DataProvider('verbosity')]
    public function testRunSuccessful(int $verbosity): void
    {
        $this->job->method('execute')
            ->willReturnCallback(function () {});

        [$code, $display] = $this->execute(null, $verbosity);

        self::assertSame(RunJobCommand::EXIT_SUCCESS_CODE, $code);
        if ($verbosity === OutputInterface::VERBOSITY_QUIET) {
            self::assertSame('', $display);
        } else {
            self::assertStringContainsString('testing has been successfully executed.', $display);
        }
    }

    public static function verbosity(): \Generator
    {
        yield 'quiet' => [OutputInterface::VERBOSITY_QUIET];
        yield 'normal' => [OutputInterface::VERBOSITY_NORMAL];
        yield 'verbose' => [OutputInterface::VERBOSITY_VERBOSE];
        yield 'very verbose' => [OutputInterface::VERBOSITY_VERY_VERBOSE];
        yield 'debug' => [OutputInterface::VERBOSITY_DEBUG];
    }

    private function execute(
        string|null $configuration = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
    ): array {
        $options = ['verbosity' => $verbosity];
        $input = ['job' => self::JOBNAME];
        if ($configuration !== null) {
            $input['configuration'] = $configuration;
        }

        $tester = new CommandTester(new RunJobCommand($this->accessor, $this->executor));
        $tester->execute($input, $options);

        return [$tester->getStatusCode(), $tester->getDisplay()];
    }
}
