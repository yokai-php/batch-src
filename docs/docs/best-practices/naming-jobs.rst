Naming Jobs
============================================================

    | *There are only two hard things in Computer Science: cache invalidation and naming things.*
    | -- Phil Karlton


Within the library, ``Jobs`` are identified with a ``name``, inside the ``JobRegistry``.
A name is a simple ``string``, with the only requirement that every job must have a unique one.


Decide on a convention
------------------------------------------------------------

There is no recommandation we can make here, you can use whatever notation you prefer:

* ``ImportUser``
* ``import_user``
* ``import.user``
* or anything else

But we strongly recommend that you pick a convention and try to stick to it.


In a Symfony project
------------------------------------------------------------

| Because this is how the Framework works, ``Jobs`` must be registered as services.
| All services tagged with ``yokai_batch.job`` are collected in a ``CompilerPass``, and given to the ``JobRegistry``.

You can tag manually all your jobs, and fill the job name withing the tag:

.. literalinclude:: naming-jobs/symfony-manual-tag.php
   :language: php

You can autoconfigure all services with ``JobInterface`` to be tagged,
and the job name will be the service id (most of the time the FQCN):

.. literalinclude:: naming-jobs/symfony-autowire-tag.yaml
   :language: yaml

Finally, if you want all services to be tagged using autoconfigure, but still want to pick job names,
you can implement ``JobWithStaticNameInterface`` in your jobs:

.. literalinclude:: naming-jobs/symfony-interface-tag.php
   :language: php

