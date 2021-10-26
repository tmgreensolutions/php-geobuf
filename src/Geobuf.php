<?php
namespace MBolli\PhpGeobuf;
# source: geobuf.proto

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
use GPBMetadata\Geobuf as MetaGeobuf;

/**
 * Generated from protobuf message <code>Data</code>
 */
class Geobuf extends Message
{
    /**
     * global arrays of unique keys
     *
     * Generated from protobuf field <code>repeated string keys = 1;</code>
     */
    private array $keys;
    /**
     *[default = 2]; // max coordinate dimensions
     *
     * Generated from protobuf field <code>optional uint32 dimensions = 2;</code>
     */
    protected int $dimensions;
    /**
     *[default = 6]; // number of digits after decimal point for coordinates
     *
     * Generated from protobuf field <code>optional uint32 precision = 3;</code>
     */
    protected int $precision = 6;
    protected string $data_type;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string[]|RepeatedField $keys
     *           global arrays of unique keys
     *     @type int $dimensions
     *          [default = 2]; // max coordinate dimensions
     *     @type int $precision
     *          [default = 6]; // number of digits after decimal point for coordinates
     *     @type FeatureCollection $feature_collection
     *     @type Feature $feature
     *     @type Geometry $geometry
     * }
     */
    public function __construct($data = NULL) {
        MetaGeobuf::initOnce();
        parent::__construct($data);
    }

    /**
     * global arrays of unique keys
     *
     * Generated from protobuf field <code>repeated string keys = 1;</code>
     * @return string[]|RepeatedField
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * global arrays of unique keys
     *
     * Generated from protobuf field <code>repeated string keys = 1;</code>
     * @param string[]|RepeatedField $var
     * @return Geobuf
     */
    public function setKeys($var): Geobuf
    {
        $arr = GPBUtil::checkRepeatedField($var, GPBType::STRING);
        $this->keys = $arr;

        return $this;
    }

    /**
     *[default = 2]; // max coordinate dimensions
     *
     * Generated from protobuf field <code>optional uint32 dimensions = 2;</code>
     * @return int
     */
    public function getDimensions(): int
    {
        return $this->dimensions ?? 0;
    }

    public function hasDimensions(): bool
    {
        return isset($this->dimensions);
    }

    public function clearDimensions(): void
    {
        unset($this->dimensions);
    }

    /**
     *[default = 2]; // max coordinate dimensions
     *
     * Generated from protobuf field <code>optional uint32 dimensions = 2;</code>
     * @param int $var
     * @return Geobuf
     */
    public function setDimensions(int $var): Geobuf
    {
        GPBUtil::checkUint32($var);
        $this->dimensions = $var;

        return $this;
    }

    /**
     *[default = 6]; // number of digits after decimal point for coordinates
     *
     * Generated from protobuf field <code>optional uint32 precision = 3;</code>
     * @return int
     */
    public function getPrecision(): int
    {
        return $this->precision ?? 0;
    }

    public function hasPrecision(): bool
    {
        return isset($this->precision);
    }

    public function clearPrecision(): void
    {
        unset($this->precision);
    }

    /**
     *[default = 6]; // number of digits after decimal point for coordinates
     *
     * Generated from protobuf field <code>optional uint32 precision = 3;</code>
     * @param int $var
     * @return Geobuf
     */
    public function setPrecision(int $var): Geobuf
    {
        GPBUtil::checkUint32($var);
        $this->precision = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Data.FeatureCollection feature_collection = 4;</code>
     * @return FeatureCollection|null
     */
    public function getFeatureCollection(): ?FeatureCollection
    {
        return $this->readOneof(4);
    }

    public function hasFeatureCollection(): bool
    {
        return $this->hasOneof(4);
    }

    /**
     * Generated from protobuf field <code>.Data.FeatureCollection feature_collection = 4;</code>
     * @param FeatureCollection $var
     * @return Geobuf
     */
    public function setFeatureCollection(FeatureCollection $var): Geobuf
    {
        GPBUtil::checkMessage($var, FeatureCollection::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Data.Feature feature = 5;</code>
     * @return Feature|null
     */
    public function getFeature(): ?Feature
    {
        return $this->readOneof(5);
    }

    public function hasFeature(): bool
    {
        return $this->hasOneof(5);
    }

    /**
     * Generated from protobuf field <code>.Data.Feature feature = 5;</code>
     * @param Feature $var
     * @return $this
     */
    public function setFeature(Feature $var): Geobuf
    {
        GPBUtil::checkMessage($var, Feature::class);
        $this->writeOneof(5, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Data.Geometry geometry = 6;</code>
     * @return Geometry|null
     */
    public function getGeometry(): ?Geometry
    {
        return $this->readOneof(6);
    }

    public function hasGeometry(): bool
    {
        return $this->hasOneof(6);
    }

    /**
     * Generated from protobuf field <code>.Data.Geometry geometry = 6;</code>
     * @param Geometry $var
     * @return Geobuf
     */
    public function setGeometry(Geometry $var): Geobuf
    {
        GPBUtil::checkMessage($var, Geometry::class);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return $this->whichOneof("data_type");
    }

}

