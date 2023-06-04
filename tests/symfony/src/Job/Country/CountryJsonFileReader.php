<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\Country;

use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;

final class CountryJsonFileReader implements ItemReaderInterface, JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    private JobParameterAccessorInterface $filePath;

    public function __construct(JobParameterAccessorInterface $filePath)
    {
        $this->filePath = $filePath;
    }

    
    public function read(): iterable
    {
        $data = (array)\json_decode(
            (string)\file_get_contents(
                (string)$this->filePath->get($this->jobExecution)
            )
        );
        foreach ($data as $code => $value) {
            yield ['code' => $code, 'value' => $value];
        }
    }
}
