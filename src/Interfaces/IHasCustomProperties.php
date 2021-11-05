<?php

namespace MBolli\PhpGeobuf\Interfaces;

interface IHasCustomProperties extends IHasAnyProperties {
    public function getCustomProperties();

    public function setCustomProperties($var);

    public function addCustomProperty($var);
}
