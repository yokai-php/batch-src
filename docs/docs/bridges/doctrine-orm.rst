Bridge with ``doctrine/orm``
============================================================

Refer to the  `official documentation <https://www.doctrine-project.org/projects/orm.html>`__ on Doctrine's website.

This bridge provides SQL database querying mechanisms, wrapped in entities, whenever you need to read/write from it.


Read from entities in database
------------------------------------------------------------

The reader will yield each entity of a specified class, one at a time.

.. literalinclude:: doctrine-orm/entity-reader.php
   :language: php

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`
