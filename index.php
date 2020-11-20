<?php
try {
    require 'microcms/core/bootstrap.php';

    $init = new \Microcms\Init();
    echo $init->dispatch();

} catch (\Exception $e) {
    \Microcms\Error::catchException($e);
}