Getting started in a Symfony project
============================================================

Installation
------------------------------------------------------------

.. code-block:: console

    composer require yokai/batch
    composer require yokai/batch-symfony-framework

.. note::

    | ``yokai/batch`` and ``yokai/batch-symfony-framework`` are the only packages required.
    | But there are many bridge packages you can install if you want to unlock more components.
    | Have a look to dedicated documentation: :doc:`</bridges>`.

.. code-block:: php

    <?php

    // config/bundles.php
    return [
        // ...
        Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle::class => ['all' => true],
    ];

Creating a job in a Symfony project
------------------------------------------------------------

Creating the job class
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    namespace App\Job;

    use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
    use Yokai\Batch\Job\JobInterface;
    use Yokai\Batch\JobExecution;

    final class ImportJob implements JobInterface, JobWithStaticNameInterface
    {
        public static function getJobName(): string
        {
            return 'import';
        }

        public function execute(JobExecution $jobExecution): void
        {
            $fileToImport = $jobExecution->getParameter('path');
            // your import logic here
        }
    }

.. hint::
   | When registering jobs with dedicated class, you can use the
     `JobWithStaticNameInterface <https://github.com/yokai-php/batch-symfony-framework/blob/0.x/src/src/JobWithStaticNameInterface.php>`__
     interface to be able to specify the job name of your service.
   | Otherwise, the service id will be used, and in that case, the service id is the FQCN.

.. seealso::
   | :doc:`What is a job? </core-concepts/job>`

Registering the job as a service
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

| We need to provide the library with all the implemented jobs we have.
| We will be using Symfony's dependency injection system for that.

| As Symfony supports registering all classes in ``src/`` as a service, we
  can leverage this behaviour to register all jobs in ``src/``.
| We will add a tag to every found class in ``src/`` that implements ``Yokai\Batch\Job\JobInterface``:

.. code-block:: yaml

    # config/services.yaml
    services:
      _defaults:
        _instanceof:
          Yokai\Batch\Job\JobInterface:
            tags: ['yokai_batch.job']

.. note::

    | In a Symfony project, we will prefer using one class per job, because service discovery is so easy to use.
    | But also because it will be very far easier to configure your job using PHP than any other format.
    | For example, there are components that requires a ``Closure``, in their constructors.
    | But keep in mind you can register your jobs with any other format of your choice.

Launching a job in a Symfony project
------------------------------------------------------------

Then the job will be triggered with its name:

.. code-block:: php

    <?php

    namespace App\Job;

    use App\Job\ImportJob;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Yokai\Batch\Launcher\JobLauncherInterface;

    final class ImportController extends AbstractController
    {
        public function __invoke(JobLauncherInterface $jobLauncher): Response
        {
            $jobExecution = $jobLauncher->launch(ImportJob::getJobName());
            // now you can look for information in JobExecution
            // or if execution is asynchronous, redirect user to a UI where he will watch it 🍿
        }
    }

.. hint::
   | Depending on the bundle configuration, you might be injecting different implementation of ``JobLauncherInterface``.
   | Have a look to completed documentation: :doc:`/bridges/symfony-framework`.
