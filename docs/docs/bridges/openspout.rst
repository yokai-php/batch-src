Bridge with ``openspout/openspout``
============================================================

Refer to the `official documentation <https://github.com/openspout/openspout>`__ on GitHub.

This bridge provides ways to read/write from/to CSV/ODS/XLSX files.


Item reader
------------------------------------------------------------

The `FlatFileReader <https://github.com/yokai-php/batch-openspout/blob/0.x/src/Reader/FlatFileReader.php>`__ is a reader
that will read from CSV/ODS/XLSX file and return each line as an array.

.. literalinclude:: openspout/flat-file-reader-xlsx.php
   :language: php

.. literalinclude:: openspout/flat-file-reader-csv.php
   :language: php

.. literalinclude:: openspout/flat-file-reader-ods.php
   :language: php

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`


Item writer
------------------------------------------------------------

The `FlatFileWriter <https://github.com/yokai-php/batch-openspout/blob/0.x/src/Writer/FlatFileWriter.php>`__ is a writer
that will write to CSV/ODS/XLSX file and each item will written its own line.

.. literalinclude:: openspout/flat-file-writer-xlsx.php
   :language: php

.. literalinclude:: openspout/flat-file-writer-csv.php
   :language: php

.. literalinclude:: openspout/flat-file-writer-ods.php
   :language: php


.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`
