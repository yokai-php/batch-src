Bridge with ``symfony/serializer``
============================================================

See `official documentation <https://symfony.com/doc/current/serializer.html>`__ on Symfony's website.


Denormalize item processor
------------------------------------------------------------

This item processor will denormalize scalar items to an object, and return the denormalized version.

.. literalinclude:: symfony-serializer/denormalize-processor.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`


Normalize item processor
------------------------------------------------------------

This item processor will normalize every item and return the normalized version.

.. literalinclude:: symfony-serializer/normalize-processor.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`
