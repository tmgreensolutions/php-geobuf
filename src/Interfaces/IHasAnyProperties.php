<?php

namespace MBolli\PhpGeobuf\Interfaces;

interface IHasAnyProperties {
    public function getValues();
    public function setValues($var);
    public function addValue($var);
}
