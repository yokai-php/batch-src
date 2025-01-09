Bridge with ``league/flysystem``
============================================================

Copy files job
------------------------------------------------------------

| This job will copy one or multiple files from a filesystem to another.
| It need to be provided with a ``JobParameterAccessorInterface`` that will be asked to fetch what files to copy.

.. literalinclude:: league-flysystem/copy-files-job.php
   :language: php

.. seealso::
   | :doc:`What is an job? </core-concepts/job>`
   | :doc:`How do I access parameters of a job? </core-concepts/job-parameter-accessor>`


Move files job
------------------------------------------------------------

| This job will move one or multiple files from a filesystem to another.
| It need to be provided with a ``JobParameterAccessorInterface`` that will be asked to fetch what files to move.

.. literalinclude:: league-flysystem/move-files-job.php
   :language: php

.. seealso::
   | :doc:`What is an job? </core-concepts/job>`
   | :doc:`How do I access parameters of a job? </core-concepts/job-parameter-accessor>`
