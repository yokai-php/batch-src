Bridge with ``doctrine/dbal``
============================================================

See `official documentation <https://www.doctrine-project.org/projects/dbal.html>`__ on Doctrine's website.


Store JobExecution objects in an SQL database
------------------------------------------------------------

.. literalinclude:: doctrine-dbal/job-execution-storage.php.php
   :language: php

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`


Read from a paginated SQL query
------------------------------------------------------------

| The reader will yield every row fetched by an SQL query, one after the other, as an associative array.
| The provided SQL query **MUST** contains ``{limit}`` and ``{offset}`` placeholders to work.
| The limit & offset will be used to execute the query as many time as needed.

.. literalinclude:: doctrine-dbal/offset-reader.php
   :language: php

.. warning::
   Because of how `SQL offset works <https://hackernoon.com/dont-offset-your-sql-querys-performance>`__,
   if the total result set you are querying is very large, you might consider using a cursor query instead.

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`


Read from a cursored SQL query
------------------------------------------------------------

| The reader will yield every row fetched by an SQL query, one after the other, as an associative array.
| The provided SQL query **MUST** contains ``{after}`` and ``{limit}`` placeholders to work.
| The after & limit will be used to execute the query as many time as needed.

.. literalinclude:: doctrine-dbal/cursor-reader.php
   :language: php

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`


Write items using inserts in a SQL table
------------------------------------------------------------

| The writer will insert every item in the same SQL table.
| It expect that items are associative arrays.

.. literalinclude:: doctrine-dbal/insert-writer.php
   :language: php

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`


Write items using upsert in a SQL table
------------------------------------------------------------

| The writer will insert or update every item in a SQL table the item can control.
| It expect that items are ``DoctrineDBALUpsert`` objects.

.. literalinclude:: doctrine-dbal/upsert-writer.php
   :language: php

| Usually, this writer requires a ``ItemProcessorInterface`` that will transform items into ``DoctrineDBALUpsert``.
| The object will contain:

* the table where record should be inserted/updated
* all the data you expect to insert/update
* one field and value that will be used to update whenever identity is known

.. literalinclude:: doctrine-dbal/upsert-writer-example.php
   :language: php

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`
