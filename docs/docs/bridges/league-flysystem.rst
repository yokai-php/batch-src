Bridge with ``league/flysystem``
============================================================

Refer to the `official documentation <https://flysystem.thephpleague.com>`__ on The League of Extraordinary Packages's website.


Copy files job
------------------------------------------------------------

| This job will copy one or multiple files from a filesystem to another.
| It must be provided with a ``JobParameterAccessorInterface`` which will be used to fetch the list of files to copy.

.. literalinclude:: league-flysystem/copy-files-job.php
   :language: php

.. seealso::
   | :doc:`What is an job? </core-concepts/job>`
   | :doc:`How do I access parameters of a job? </core-concepts/job-parameter-accessor>`


Move files job
------------------------------------------------------------

| This job will move one or multiple files from a filesystem to another.
| It must be provided with a ``JobParameterAccessorInterface`` which will be used to fetch the list of files to move.

.. literalinclude:: league-flysystem/move-files-job.php
   :language: php

.. seealso::
   | :doc:`What is an job? </core-concepts/job>`
   | :doc:`How do I access parameters of a job? </core-concepts/job-parameter-accessor>`
