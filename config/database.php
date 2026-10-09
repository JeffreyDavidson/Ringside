<?php

use Pdo\Mysql;

return [

    'connections' => [

        /*
         * Local development and the test suite run on SQLite. The framework's SQLite connection, tuned for the web
         * server, queue and scheduler writing at the same time: WAL lets reads continue during a write, the busy timeout
         * waits up to five seconds instead of failing with "database is locked", NORMAL sync is safe under WAL, and
         * IMMEDIATE transactions take the write lock at BEGIN, so a transaction that reads first never fails when it
         * later writes. The in-memory test database ignores WAL.
         */
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'prefix_indexes' => null,
            'mask_bindings_in_exception_messages' => env('DB_MASK_BINDINGS', false),
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'synchronous' => 'normal',
            'transaction_mode' => 'IMMEDIATE',
            'pragmas' => [],
        ],

        /*
         * Production runs MySQL 8. The framework's MySQL connection, plus READ COMMITTED isolation: the scheduling and
         * lifecycle locks are designed for PostgreSQL's default READ COMMITTED, where every statement sees the latest
         * committed rows once a lock has been granted. Under MySQL's default REPEATABLE READ a transaction that read
         * anything before waiting for a lock would keep checking conflicts against its older snapshot. The session time
         * zone is UTC so TIMESTAMP columns store dates exactly as written, like PostgreSQL's timestamp without time zone,
         * whatever the server's own zone is.
         */
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'mask_bindings_in_exception_messages' => env('DB_MASK_BINDINGS', false),
            'strict' => true,
            'engine' => null,
            'isolation_level' => 'READ COMMITTED',
            'timezone' => '+00:00',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => false, // disable to preserve original behavior for existing applications
    ],

];
