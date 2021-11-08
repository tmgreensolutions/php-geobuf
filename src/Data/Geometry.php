<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: geobuf.proto

namespace MBolli\PhpGeobuf\Data;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
use GPBMetadata\Geobuf;
use MBolli\PhpGeobuf\Data\Geometry\Type;
use MBolli\PhpGeobuf\Interfaces\IHasCustomProperties;

/**
 * Generated from protobuf message <code>MBolli.PhpGeobuf.Data.Geometry</code>
 */
class Geometry extends Message implements IHasCustomProperties {
    /**
     * Generated from protobuf field <code>.MBolli.PhpGeobuf.Data.Geometry.Type type = 1;</code>
     */
    protected $type = 0;
    /**
     * coordinate structure in lengths
     *
     * Generated from protobuf field <code>repeated uint32 lengths = 2 [packed = true];</code>
     */
    private $lengths;
    /**
     * delta-encoded integer values
     *
     * Generated from protobuf field <code>repeated sint64 coords = 3 [packed = true];</code>
     */
    private $coords;
    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Geometry geometries = 4;</code>
     */
    private $geometries;
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
     *     @type int $type
     *     @type int[]|RepeatedField $lengths
     *           coordinate structure in lengths
     *     @type int[]|RepeatedField|string[] $coords
     *           delta-encoded integer values
     *     @type Geometry[]|RepeatedField $geometries
     *     @type RepeatedField|Value[] $values
     *     @type int[]|RepeatedField $custom_properties
     * }
     */
    public function __construct($data = null) {
        Geobuf::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.MBolli.PhpGeobuf.Data.Geometry.Type type = 1;</code>
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Generated from protobuf field <code>.MBolli.PhpGeobuf.Data.Geometry.Type type = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var) {
        GPBUtil::checkEnum($var, Type::class);
        $this->type = $var;

        return $this;
    }

    /**
     * coordinate structure in lengths
     *
     * Generated from protobuf field <code>repeated uint32 lengths = 2 [packed = true];</code>
     * @return RepeatedField
     */
    public function getLengths() {
        return $this->lengths;
    }

    /**
     * coordinate structure in lengths
     *
     * Generated from protobuf field <code>repeated uint32 lengths = 2 [packed = true];</code>
     * @param int[]|RepeatedField $var
     * @return $this
     */
    public function setLengths($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::UINT32);
        $this->lengths = $arr;

        return $this;
    }

    /**
     * add length to coordinate structure
     *
     * @param $var
     * @return $this
     */
    public function addLength($var) {
        $this->lengths[] = $var;

        return $this;
    }

    /**
     * delta-encoded integer values
     *
     * Generated from protobuf field <code>repeated sint64 coords = 3 [packed = true];</code>
     * @return RepeatedField
     */
    public function getCoords() {
        return $this->coords;
    }

    /**
     * delta-encoded integer values
     *
     * Generated from protobuf field <code>repeated sint64 coords = 3 [packed = true];</code>
     * @param int[]|RepeatedField|string[] $var
     * @return $this
     */
    public function setCoords($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::SINT64);
        $this->coords = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Geometry geometries = 4;</code>
     * @return RepeatedField
     */
    public function getGeometries() {
        return $this->geometries;
    }

    /**
     * Generated from protobuf field <code>repeated .MBolli.PhpGeobuf.Data.Geometry geometries = 4;</code>
     * @param Geometry[]|RepeatedField $var
     * @return $this
     */
    public function setGeometries($var) {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::MESSAGE, Geometry::class);
        $this->geometries = $arr;

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
     * @param $var
     * @return $this
     */
    public function addValue($var) {
        $this->values[] = $var;

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
     * Add custom property
     *
     * @param $var
     * @return $this
     */
    public function addCustomProperty($var) {
        $this->custom_properties[] = $var;

        return $this;
    }
}
