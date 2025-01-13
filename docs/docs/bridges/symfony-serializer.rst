Bridge with ``symfony/serializer``
============================================================

Refer to the `official documentation <https://symfony.com/doc/current/serializer.html>`__ on Symfony's website.

This bridge provides a ways to (de)normalize items in batch job execution.


Denormalize item processor
------------------------------------------------------------

This item processor will denormalize scalar items to an object, and return the denormalized version.

.. literalinclude:: symfony-serializer/denormalize-processor.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`


Normalize item processor
------------------------------------------------------------

This item processor will normalize each item and returns the normalized version.

.. literalinclude:: symfony-serializer/normalize-processor.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`
