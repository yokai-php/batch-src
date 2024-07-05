Example: Before/After using Yokai Batch
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

We have a ``jsonl`` file, containing data that we want to import in our database.

.. code-block:: jsonl

    {"code":"camcorders","attributes":["description","image_stabilizer","name","optical_zoom","picture","power_requirements","price","release_date","sensor_type","sku","total_megapixels","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Camcorders","fr_FR":"Cam\u00e9scopes num\u00e9riques","de_DE":"Digitale Videokameras"}}
    {"code":"digital_cameras","attributes":["auto_exposure","auto_focus_assist_beam","auto_focus_lock","auto_focus_modes","auto_focus_points","camera_brand","camera_model_name","camera_type","description","focus","focus_adjustement","image_resolutions","iso_sensitivity","iso_sensitivity_max","iso_sensitivity_min","lens_mount_interface","light_exposure_corrections","light_exposure_modes","light_metering","max_image_resolution","name","optical_zoom","picture","power_requirements","price","release_date","sensor_type","short_description","sku","supported_aspect_ratios","supported_image_format","total_megapixels","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Digital cameras","fr_FR":"Cam\u00e9ras digitales","de_DE":"Digitale Kameras"}}
    {"code":"headphones","attributes":["description","headphone_connectivity","name","picture","power_requirements","price","release_date","sku","snr","thd","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Headphones","fr_FR":"Casques audio","de_DE":"Kopfh\u00f6rer"}}

.. note::
   | todo

Before: Without Yokai Batch
------------------------------------------------------------

.. literalinclude:: before-after/before.php
   :language: php

.. warning::
   | There are many little things you have to think about when doing batch processing.
   | And there are chances that you have these little things shattered in your application.
   | As your team grow, it will become more important to avoid duplicating things like this.
   | Because it is likely that someone will forget one of those little things, code will start acting funny.

After: With Yokai Batch
------------------------------------------------------------

.. literalinclude:: before-after/after.php
   :language: php

.. note::
   | todo

.. hint::
   | todo parler du fait que c'est pas obligé d'avoir un storage, etc...
