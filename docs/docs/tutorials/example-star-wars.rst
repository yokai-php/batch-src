Example: StarWars import
============================================================

.. note::
   | The code involved in this example is part of the test suite of **Yokai Batch**.
   | You can find the original code in the source repository:
     `Data <https://github.com/yokai-php/batch-src/tree/0.x/tests/symfony/data/star-wars>`__,
     `Entities <https://github.com/yokai-php/batch-src/tree/0.x/tests/symfony/src/Entity/StarWars>`__,
     `Jobs <https://github.com/yokai-php/batch-src/tree/0.x/tests/symfony/src/Job/StarWars>`__,
     `Tests <https://github.com/yokai-php/batch-src/blob/0.x/tests/symfony/tests/StarWarsJobSet.php>`__


What are we trying to do?
------------------------------------------------------------

| We have been provided with `data from the StarWars universe <https://www.kaggle.com/jsphyg/star-wars>`__.
| Our job is to import these data in a relational database.

We will import the following data:

* Characters
* Species
* Planets

Designing the entities
------------------------------------------------------------

.. literalinclude:: star-wars/entity-specie.php
   :language: php

.. literalinclude:: star-wars/entity-planet.php
   :language: php

.. literalinclude:: star-wars/entity-character.php
   :language: php

Writing the import
------------------------------------------------------------

Install the packages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: console

    composer require yokai/batch
    composer require yokai/batch-openspout
    composer require yokai/batch-symfony-validator
    composer require yokai/batch-doctrine-persistence

An import for each entity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. literalinclude:: star-wars/job-planet.php
   :language: php

.. literalinclude:: star-wars/job-specie.php
   :language: php

.. literalinclude:: star-wars/job-character.php
   :language: php

Factorise common logic
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All three imports behavior the same way:

* read data from a CSV file
* convert data to an entity
* ensure entity is valid
* save entity to the database

| The thing is, most of the time, in your application, you will have similar jobs.
| **Yokai Batch** offers many reusable components, but you should also try to organise your code jobs.

| We chose the easiest way here: introducing an abstract class for all our jobs.
| We could have been creating a ``JobFactory``, but it's matter of taste.

.. literalinclude:: star-wars/job-parent.php
   :language: php

A job for the whole import
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

| So far, we created one job per entity to import.
| It is very convenient, because we can leverage the power of ``ItemJob``.

| But having to trigger the three jobs on a specific order for the import to work is boring.
| What if we introduce a job to trigger these three jobs in a row?

.. literalinclude:: star-wars/job-parent.php
   :language: php

Running the import
------------------------------------------------------------

Now, you can trigger this job anytime you want, using the ``JobLauncher`` configured in your application.

.. literalinclude:: star-wars/command.php
   :language: php

.. seealso::
   | :doc:`What is an item job? </core-concepts/item-job>`
