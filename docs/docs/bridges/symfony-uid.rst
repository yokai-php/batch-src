Bridge with ``symfony/uid``
============================================================

The UID component provides utilities to work with unique identifiers (UIDs) such as UUIDs and ULIDs.
Please have a look to `Symfony's component documentation <https://symfony.com/doc/current/components/uid.html>`__.


Random based UUIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``RandomBasedUuidJobExecutionIdGenerator`` for time based UUIDs ``JobExecution`` ids.

.. code-block:: php

    <?php

    use Symfony\Component\Uid\Factory\UuidFactory;
    use Yokai\Batch\Bridge\Symfony\Uid\Factory\RandomBasedUuidJobExecutionIdGenerator;
    use Yokai\Batch\Factory\JobExecutionFactory;
    use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;

    (new JobExecutionFactory(
        (new RandomBasedUuidJobExecutionIdGenerator(new UuidFactory())),
        new NullJobExecutionParametersBuilder(),
    ))->create('job.foo');


Time based UUIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``TimeBasedUuidJobExecutionIdGenerator`` for time based UUIDs ``JobExecution`` ids.

.. code-block:: php

    <?php

    use Symfony\Component\Uid\Factory\UuidFactory;
    use Yokai\Batch\Bridge\Symfony\Uid\Factory\TimeBasedUuidJobExecutionIdGenerator;
    use Yokai\Batch\Factory\JobExecutionFactory;
    use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;

    (new JobExecutionFactory(
        (new TimeBasedUuidJobExecutionIdGenerator(new UuidFactory())),
        new NullJobExecutionParametersBuilder(),
    ))->create('job.foo');


ULIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``UlidJobExecutionIdGenerator`` for ULIDs ``JobExecution`` ids.

.. code-block:: php

    <?php

    use Symfony\Component\Uid\Factory\UuidFactory;
    use Yokai\Batch\Bridge\Symfony\Uid\Factory\UlidJobExecutionIdGenerator;
    use Yokai\Batch\Factory\JobExecutionFactory;
    use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;

    (new JobExecutionFactory(
        (new UlidJobExecutionIdGenerator(new UuidFactory())),
        new NullJobExecutionParametersBuilder(),
    ))->create('job.foo');
