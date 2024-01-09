<?php

/*
 * This file is part of the Tucania project.
 *
 * Copyright (C) Tucania (https://www.tucania.com/) - All Rights Reserved
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Job;

use Exception;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;

class LoggerTest extends TestCase
{
    private JobExecutionFactory $jobExecutionFactory;

    public function setUp(): void
    {
        $idGenerator = new UniqidJobExecutionIdGenerator();
        $this->jobExecutionFactory = new JobExecutionFactory($idGenerator);
    }

    public function testLogError()
    {
        $errorToLog = 'test assert logErrorMethod';
        $jobExecution = $this->jobExecutionFactory->create('job-test');
        $jobExecution->logError(new Exception(), $errorToLog);

        self::assertStringContainsString($errorToLog, $jobExecution->getLogs()->__toString());
    }
}
