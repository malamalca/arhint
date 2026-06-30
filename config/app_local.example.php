<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    'App' => [
        'defaultLocale' => 'sl_SI',
    ],

    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => true,

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => '__SALT__',
        'cookieKey' => '__COOKIEKEY__',
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'host' => '__DBHOST__',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => '__DBUSER__',
            'password' => '__DBPASS__',

            'database' => '__DATABASE__',
            /**
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /**
             * You can use a DSN string to set the entire configuration
             */
            'url' => null,
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'host' => '127.0.0.1',
            //'port' => 'non_standard_port_number',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            //'schema' => 'myapp',
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => null,
        ],
    ],

    /*
     * Embedding service configuration.
     *
     * - provider (string) "local" or "openai".
     * - url (string)      The embedding API endpoint. For "local", e.g.
     *                     "http://localhost:8001/embed". For "openai", leave empty to
     *                     use "https://api.openai.com/v1/embeddings".
     * - model (string)    Model name (openai only), e.g. "text-embedding-3-small".
     * - api_key (string)  Bearer token (openai only).
     * - timeout (int)     cURL timeout in seconds.
     */
    'Embedding' => [
        'provider' => 'local',
        'url' => '',
        'model' => 'text-embedding-3-small',
        'api_key' => '',
        'timeout' => 30,
    ],

    /*
     * Chroma vector database configuration.
     *
     * - host (string)     Host and port, e.g. "192.168.88.30:8001"
     * - scheme (string)   HTTP or HTTPS.
     * - collection (string) Collection name.
     * - timeout (int)     cURL timeout in seconds.
     */
    'VectorDB' => [
        'host' => '',
        'scheme' => 'http',
        'collection' => 'events',
        'timeout' => 30,
    ],
];
