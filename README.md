# ARHINT :: a simple ERP and CRM

[![CI](https://github.com/malamalca/arhint/actions/workflows/ci.yml/badge.svg)](https://github.com/malamalca/arhint/actions/workflows/ci.yml)

This is a simple costum ERP/CRM solution for a small company.

The source code can be found here: [malamalca/arhint](https://github.com/malamalca/arhint).

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `composer create-project --prefer-dist --no-dev malamalca/arhint [app_name]`.

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Configuration

Read and edit the environment specific `config/app_local.php` and setup the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.
