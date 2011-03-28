<?php

abstract class absModules{

    public function __construct()
    {
        echo("loaded module ". get_class($this)."\n");
    }
}
?>