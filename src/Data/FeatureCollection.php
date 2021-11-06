<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: geobuf.proto

namespace MBolli\PhpGeobuf\Data;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
use GPBMetadata\Geobuf;
use MBolli\PhpGeobuf\Interfaces\IHasCustomProperties;

/**
 * Generated from protobuf message <code>MBolli.PhpGeobuf.Data.FeatureCollection</code>
 */
class FeatureCollection extends Message implements IHasCustomProperties {
    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Feature features = 1;</code>
     */
    private $features;
    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Value values = 13;</code>
     */
    private $values;
    /**
     * Generated from protobuf field <code>repeated uint32 custom_properties = 15 [packed = true];</code>
     */
    private $custom_properties;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type Feature[]|RepeatedField $features
     *     @type RepeatedField|Value[] $values
     *     @type int[]|RepeatedField $custom_properties
     * }
     */
    public function __construct($data = null) {
        Geobuf::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Feature features = 1;</code>
     * @return RepeatedField
     */
    public function getFeatures() {
        return $this->features;
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Feature features = 1;</code>
     * @param Feature[]|RepeatedField $var
     * @return $this
     */
    public function setFeatures($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::MESSAGE, Feature::class);
        $this->features = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Value values = 13;</code>
     * @return RepeatedField
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Value values = 13;</code>
     * @param RepeatedField|Value[] $var
     * @return $this
     */
    public function setValues($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::MESSAGE, Value::class);
        $this->values = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated uint32 custom_properties = 15 [packed = true];</code>
     * @return RepeatedField
     */
    public function getCustomProperties() {
        return $this->custom_properties;
    }

    /**
     * Generated from protobuf field <code>repeated uint32 custom_properties = 15 [packed = true];</code>
     * @param int[]|RepeatedField $var
     * @return $this
     */
    public function setCustomProperties($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::UINT32);
        $this->custom_properties = $arr;

        return $this;
    }

    /**
     * Add a custom property
     *
     * @param $var
     * @return $this
     */
    public function addCustomProperty($var) {
        $this->custom_properties[] = $var;

        return $this;
    }
}
