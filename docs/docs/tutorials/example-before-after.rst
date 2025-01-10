Example: Before/After showcase
============================================================

| This documentation will help you understand the difference between a classic code you could write.
| Versus a code that had been written using ``yokai/batch`` library.


What are we trying to do?
------------------------------------------------------------

We have a ``jsonl`` file, containing data that we want, and we must import it in our database, via ``doctrine/orm``.

.. code-block:: jsonl

    {"code":"camcorders","attributes":["description","image_stabilizer","name","optical_zoom","picture","power_requirements","price","release_date","sensor_type","sku","total_megapixels","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Camcorders","fr_FR":"Cam\u00e9scopes num\u00e9riques","de_DE":"Digitale Videokameras"}}
    {"code":"digital_cameras","attributes":["auto_exposure","auto_focus_assist_beam","auto_focus_lock","auto_focus_modes","auto_focus_points","camera_brand","camera_model_name","camera_type","description","focus","focus_adjustement","image_resolutions","iso_sensitivity","iso_sensitivity_max","iso_sensitivity_min","lens_mount_interface","light_exposure_corrections","light_exposure_modes","light_metering","max_image_resolution","name","optical_zoom","picture","power_requirements","price","release_date","sensor_type","short_description","sku","supported_aspect_ratios","supported_image_format","total_megapixels","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Digital cameras","fr_FR":"Cam\u00e9ras digitales","de_DE":"Digitale Kameras"}}
    {"code":"headphones","attributes":["description","headphone_connectivity","name","picture","power_requirements","price","release_date","sku","snr","thd","weight"],"attribute_as_label":"name","attribute_as_image":"picture","labels":{"en_US":"Headphones","fr_FR":"Casques audio","de_DE":"Kopfh\u00f6rer"}}

.. note::
   | This file is obviously much larger than these 3 lines, you might have thousands lines to process.


Before: Without Yokai Batch
------------------------------------------------------------

The easiest way to do this is to create the one script you have already written thousands of times:

.. literalinclude:: before-after/before.php
   :language: php

.. warning::
   | There are many little things you have to think about when doing batch processing.
   | And there are chances that you have these little things shattered in your application.
   | As your team grow, it will become more important to avoid duplicating things like this.
   | Because it is likely that someone will forget one of those little things, code will start acting funny.


After: With Yokai Batch
------------------------------------------------------------

| Now, using ``yokai/batch``, we will be able to factorize most of this code to show only the business part :

.. literalinclude:: before-after/after.php
   :language: php

.. note::
   | Most of the classes of that snippet are from ``Yokai\Batch`` namespace, you will reuse a lot of those along the way.
   | After all, batch processing is almost always the same, the only things that changes are:

     * the data source you are reading from
     * some transformations you are performing on that source
     * the data source you are writing to
