<?php

use Formation\Incrementor\Incrementor;

include('Incrementor.php');

$incrementor=(new Incrementor(__DIR__,'archives',false))->run();
