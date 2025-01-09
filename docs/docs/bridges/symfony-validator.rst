Bridge with ``symfony/validator``
============================================================

See `official documentation <https://symfony.com/doc/current/validation.html>`__ on Symfony's website.


Skip invalid item processor
------------------------------------------------------------

| This item processor will validate every item, and throw an exception if at least one violation is raised.
| The exception is caught by ``ItemJob``, and the item will be skipped.

.. literalinclude:: symfony-validator/skip-invalid-item.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`
