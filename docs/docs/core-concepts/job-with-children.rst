Job with children
============================================================

| One of the goals of this library is to allow reusability of code.
| Whenever you built a ``Job``, you will ask yourself: "Am I doing this somewhere else?".
| If the answer is "yes", you should be tempted to reuse the job entirely.

This is where ``JobWithChildren`` special job can be used.

.. literalinclude:: job-with-children/example.php
   :language: php

.. note::
   | As always in our job, this is only a way to achieve this goal.
   | There are pros and cons, and you might consider alternatives.
   | Depending on how you acquire the ``JobRegistry``, you might end with the one with all jobs.
   | This is not bad, but still, you are having access to all jobs, which can be dangerous in many way.

.. seealso::
   | :doc:`What is a job? </core-concepts/job>`


The hierarchy of JobExecution
------------------------------------------------------------

Whenever you use the ``JobWithChildren``, you will have to dive into ``JobExecution`` hierarchy.

| When a job is started using the ``JobLauncherInterface``, a "root" ``JobExecution`` is created.
| This object have an id, logs, and parameters.

| But when a job is started in the ``JobWithChildren``, a "child" ``JobExecution`` is attached to the "root".
| This "child" object behave the same as the root, but:

* It does not have id on its own (it copy the id on the "root")
* It does not have logs on its own (all logs are merged at "root" level)
* It does not have parameters on its own (only "root" has)

.. note::
   | The "child" ``JobExecution`` is not always attached to the "root", it can be attached to another "child".
   | This occurs when you are nesting ``JobWithChildren``.

.. seealso::
   | :doc:`What is a job execution? </core-concepts/job-execution>`
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`
