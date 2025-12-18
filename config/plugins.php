<?php
 /*
  * List of plugins to load in the form `PluginName` => `[configuration options]`.
  *
  * Available options:
  * - onlyDebug: Load the plugin only in debug mode. Default false.
  * - onlyCli: Load the plugin only in CLI mode. Default false.
  * - optional: Do not throw an exception if the plugin is not found. Default false.
  */
return [
    'DebugKit' => ['onlyDebug' => true],
    'Bake' => ['onlyCli' => true, 'optional' => true],
    'Migrations' => ['onlyCli' => true],
    'Lil',
    'Crm',
    'Expenses',
    'Documents',
    'Projects',
    'Tasks',
    'Calendar',
];
