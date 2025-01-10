Bridge with ``doctrine/dbal``
============================================================

Refer to the `official documentation <https://www.doctrine-project.org/projects/dbal.html>`__ on Doctrine's website.


Store JobExecution objects in an SQL database
------------------------------------------------------------

.. literalinclude:: doctrine-dbal/job-execution-storage.php.php
   :language: php

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`


Read from a paginated SQL query
------------------------------------------------------------

| The reader will yield each row fetched by an SQL query, one at a time, as an associative array.
| The provided SQL query MUST include {limit} and {offset} placeholders to function correctly.
| The limit and offset will be used to execute the query as many times as needed.

.. literalinclude:: doctrine-dbal/offset-reader.php
   :language: php

.. warning::
   Due to the nature of SQL offset <https://hackernoon.com/dont-offset-your-sql-querys-performance>__,
   if the total result set is very large, consider using a cursor-based query instead.


.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`


Read from a cursored SQL query
------------------------------------------------------------

| The reader will yield each row fetched by an SQL query, one at a time, as an associative array.
| The provided SQL query MUST include {after} and {limit} placeholders to function correctly.
| The after and limit will be used to execute the query as many times as needed.

.. literalinclude:: doctrine-dbal/cursor-reader.php
   :language: php

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`


Write items using inserts in a SQL table
------------------------------------------------------------

| The writer will insert each item into the same SQL table.
| It expects that items are associative arrays.

.. literalinclude:: doctrine-dbal/insert-writer.php
   :language: php

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`


Write items using upsert in a SQL table
------------------------------------------------------------

| The writer will insert or update each item in an SQL table, with the item determining the behavior.
| It expects that items are DoctrineDBALUpsert objects.

.. literalinclude:: doctrine-dbal/upsert-writer.php
   :language: php

| Typically, this writer requires an ItemProcessorInterface to transform items into DoctrineDBALUpsert objects.
| These objects should contain:

* The table where the record should be inserted or updated
* All the data to insert or update
* A field and value that will be used to identify the record to update (when applicable)

.. literalinclude:: doctrine-dbal/upsert-writer-example.php
   :language: php

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`
