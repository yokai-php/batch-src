Yokai Batch: Batch processing with PHP
============================================================

👀 Overview
------------------------------------------------------------

**Yokai Batch** is batch processing job library written with PHP, with zero dependencies.

Key Features
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

* 👀 Keep an eye on the execution of your jobs.
* 🚀 Provide key components to handle batch processing jobs.
* 🧱 Have decoupled reusable components to compose your jobs.
* 🖼️ Have bridges with popular libraries and frameworks.

Quick Start Guide
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

| **Ready to dive in?**
| Check out our :doc:`/getting-started/standalone-library` to get up and running in no time!

| **Is your application using Symfony?**
| Check out our :doc:`/getting-started/with-symfony` instead.

🔖 Core Concepts
------------------------------------------------------------

Get familiar with the core concepts:

* :doc:`/core-concepts/job`: Contains the business logic of your tasks.
* :doc:`/core-concepts/job-launcher`: Your application entrypoint to execute a job.
* :doc:`/core-concepts/item-job`: A type a job that is optimized for batch processing.

📖 Tutorials and Examples
------------------------------------------------------------

Build your first Job
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

| Learn how to create and manage your first batch job in **Yokai Batch**.
| Follow our step-by-step :doc:`/tutorials/first-job`.

Real world examples
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Explore some of the things that could be built with **Yokai Batch**:

* :doc:`/tutorials/example-before-after`: Before/After **Yokai Batch** showcase.
* :doc:`/tutorials/example-star-wars`: Import StarWars related data through Doctrine ORM.

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Getting Started

   getting-started/standalone-library
   getting-started/with-symfony

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Core Concepts

   core-concepts/job
   core-concepts/job-launcher
   core-concepts/item-job
   core-concepts/job-execution
   core-concepts/job-execution-storage
   core-concepts/job-with-children
   core-concepts/job-parameter-accessor
   core-concepts/aware-interfaces

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Tutorials and Examples

   tutorials/first-job
   tutorials/example-before-after
   tutorials/example-star-wars

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Best Practices

   best-practices/naming-jobs
   best-practices/performance

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Bridges with Other Libraries

   What are bridges? </bridges>
   Doctrine DBAL </bridges/doctrine-dbal>
   Doctrine ORM </bridges/doctrine-orm>
   Doctrine Persistence </bridges/doctrine-persistence>
   Flysystem </bridges/league-flysystem>
   OpenSpout </bridges/openspout>
   Symfony Console </bridges/symfony-console>
   Symfony Framework </bridges/symfony-framework>
   Symfony Messenger </bridges/symfony-messenger>
   Symfony Serializer </bridges/symfony-serializer>
   Symfony Validator </bridges/symfony-validator>

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Reference

   reference/faq
