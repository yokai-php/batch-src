Bridge with ``doctrine/persistence``
============================================================

Refer to the  `official documentation <https://www.doctrine-project.org/projects/persistence.html>`__ on Doctrine's website.


Object item writer
------------------------------------------------------------

| The writer will persist every items on the appropriate ``ObjectManager``.
| It expect that items are objects.
|  Objects can belong to different ``ObjectManager`` instances, only the encountered ones will be flushed.

``ObjectManager->flush()`` is called every time the ``ItemJob`` reaches the batch size.

.. literalinclude:: doctrine-persistence/object-writer.php
   :language: php

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`


Object registry util
------------------------------------------------------------

Imagine that in an ``ItemJob`` you need to find objects from a database.

.. literalinclude:: doctrine-persistence/object-registry-before.php
   :language: php

| The problem here is that every time you will call ``findOneBy``, you
  will have to query the database. The object might already be in
| Doctrine’s memory, so it won’t be hydrated twice, but the query will be
  done every time.

| The role of the ``ObjectRegistry`` is to remember found objects
  identities, and query these objects with it instead.

.. literalinclude:: doctrine-persistence/object-registry-after.diff
   :language: diff

| The first time, the query will hit the database, and the object identity
  will be remembered in the registry.
| Everytime after that, the registry will call
  ``Doctrine\Persistence\ObjectManager::find`` instead.
| If the object is still in Doctrine’s memory, it will be returned directly.
| Otherwise, the query will be the fastest possible because it will use the object identity.

.. seealso::
   | :doc:`What is an item job? </core-concepts/item-job>`
