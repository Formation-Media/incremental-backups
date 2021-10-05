# Incremental Backups 

Incremental Backups is a package for making .zip incremental back ups

## Installation 

```bash
composer install formation-media/incremental-backups
```

## Usage 

```php
<?php

use Formation\Incrementor\Incrementor;

$incrementor=(new Incrementor(__DIR__,'archives',false))->run();
```
Where __DIR__ is the directory being scanned, 
'archives' is the name of the directory where the backups will be deposited,
and false is whether the back up is incremental or full. 

