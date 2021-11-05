<?php

namespace MBolli\PhpGeobuf\Interfaces;

interface IHasProperties extends IHasAnyProperties {
    public function getProperties();

    public function setProperties($var);

    public function addProperty($var);
}
