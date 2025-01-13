Bridge with ``symfony/validator``
============================================================

Refer to the  `official documentation <https://symfony.com/doc/current/validation.html>`__ on Symfony's website.

This bridge provides a ways to skip invalid items in batch job execution.


Skip invalid item processor
------------------------------------------------------------

| This item processor will validate each item, and throws an exception if at least one violation is raised.
| The exception is caught by ``ItemJob``, causing the item to be skipped.

.. literalinclude:: symfony-validator/skip-invalid-item.php
   :language: php

.. seealso::
   | :doc:`What is an item processor? </core-concepts/item-job/item-processor>`
