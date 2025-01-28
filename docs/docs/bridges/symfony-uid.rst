Bridge with ``symfony/uid``
============================================================

| The UID component provides utilities to work with unique identifiers (UIDs) such as UUIDs and ULIDs.
| Refer to the `official documentation <https://symfony.com/doc/current/components/uid.html>`__ on Symfony's website.

This bridge provides ways to generate ``JobExecution`` ids.


Random based UUIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``RandomBasedUuidJobExecutionIdGenerator`` for time based UUIDs ``JobExecution`` ids.

.. literalinclude:: symfony-uid/random-based-uuid-job-execution-id-generator.php
   :language: php

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`


Time based UUIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``TimeBasedUuidJobExecutionIdGenerator`` for time based UUIDs ``JobExecution`` ids.

.. literalinclude:: symfony-uid/time-based-uuid-job-execution-id-generator.php
   :language: php

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`


ULIDs ``JobExecution`` ids
------------------------------------------------------------

Use ``UlidJobExecutionIdGenerator`` for ULIDs ``JobExecution`` ids.

.. literalinclude:: symfony-uid/ulid-job-execution-id-generator.php
   :language: php

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`
