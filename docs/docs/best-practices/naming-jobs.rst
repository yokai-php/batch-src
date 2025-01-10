Naming Jobs
============================================================

    | *There are only two hard things in Computer Science: cache invalidation and naming things.*
    | -- Phil Karlton


Within the library, ``Jobs`` are identified with a ``name``, inside the ``JobRegistry``.
A name is a simple ``string``, with the only requirement that every job must have a unique one.


Decide on a convention
------------------------------------------------------------

There is no specific recommandation, you can use whichever notation you prefer, such as:

* ``ImportUser``
* ``import_user``
* ``import.user``
* or any other format

However, we strongly recommend that you choose a convention and adhere to it consistently.


In a Symfony project
------------------------------------------------------------

| Because of the way the framework works, ``Jobs`` must be registered as services.
| All services tagged with ``yokai_batch.job`` are collected in a ``CompilerPass``, and provided to the ``JobRegistry``.

You can manually tag all your jobs and specify the job name withing the tag:

.. literalinclude:: naming-jobs/symfony-manual-tag.php
   :language: php

Alternatively, you can autoconfigure all services implementing ``JobInterface`` to be tagged.
In this case, the job name will be the service ID (usually the FQCN):

.. literalinclude:: naming-jobs/symfony-autowire-tag.yaml
   :language: yaml

Finally, if you want to autoconfigure all services while still manually specifying job names,
you can implement the ``JobWithStaticNameInterface`` in your jobs:

.. literalinclude:: naming-jobs/symfony-interface-tag.php
   :language: php

