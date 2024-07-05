Job parameter accessor
============================================================

When a job (or a component within a job) can be working with a parameterized value, it can rely on a
`JobParameterAccessorInterface <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/JobParameterAccessorInterface.php>`__
instance to retrieve that value.

.. code-block:: php

    <?php

    use Yokai\Batch\Job\JobInterface;
    use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
    use Yokai\Batch\JobExecution;

    class FooJob implements JobInterface
    {
        public function __construct(
            private JobParameterAccessorInterface $path,
        ) {
        }

        public function execute(JobExecution $jobExecution): void
        {
            /** @var string $path */
            $path = $this->path->get($jobExecution);
            // do something with $path
        }
    }

What types of parameter accessors exists?
------------------------------------------------------------

**Built-in parameter accessors:**

* `ChainParameterAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/ChainParameterAccessor.php>`__:
  try multiple parameter accessors, the first that is not failing is used.
* `ClosestJobExecutionAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/ClosestJobExecutionAccessor.php>`__:
  try another parameter accessor on each job execution in hierarchy, until not failed.
* `DefaultParameterAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/DefaultParameterAccessor.php>`__:
  try accessing parameter using another parameter accessor, use default value if failed.
* `JobExecutionParameterAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/JobExecutionParameterAccessor.php>`__:
  extract value from job execution’s `parameters <https://github.com/yokai-php/batch/blob/0.x/src/src/JobParameters.php>`__.
* `JobExecutionSummaryAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/JobExecutionSummaryAccessor.php>`__:
  extract value from job execution’s `summary <https://github.com/yokai-php/batch/blob/0.x/src/src/Summary.php>`__.
* `ParentJobExecutionAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/ParentJobExecutionAccessor.php>`__:
  use another parameter accessor on job execution’s parent execution.
* `ReplaceWithVariablesParameterAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/ReplaceWithVariablesParameterAccessor.php>`__:
  use another parameter accessor to get string value, and replace variables before returning.
* `RootJobExecutionAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/RootJobExecutionAccessor.php>`__:
  use another parameter accessor on job execution’s root execution.
* `SiblingJobExecutionAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/SiblingJobExecutionAccessor.php>`__:
  use another parameter accessor on job execution’s sibling execution.
* `StaticValueParameterAccessor <https://github.com/yokai-php/batch/blob/0.x/src/src/Job/Parameters/StaticValueParameterAccessor.php>`__:
  use static value provided at construction.

**Parameter accessors from bridges:**

* From ``symfony/framework-bundle`` bridge:

  * `ContainerParameterAccessor <https://github.com/yokai-php/batch-symfony-framework/blob/0.x/src/src/ContainerParameterAccessor.php>`__:
    use a parameter from Symfony’s container.

.. seealso::
   | :doc:`What is a job? </core-concepts/job>`
   | :doc:`When does a job execution hierarchy is created? </core-concepts/job-with-children>`
   | :doc:`What is a job execution? </core-concepts/job-execution>`
