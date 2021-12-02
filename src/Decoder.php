<?php

namespace MBolli\PhpGeobuf;

use Exception;
use Google\Protobuf\Internal\RepeatedField;
use MBolli\PhpGeobuf\Data\Feature;
use MBolli\PhpGeobuf\Data\FeatureCollection;
use MBolli\PhpGeobuf\Data\Geometry;
use MBolli\PhpGeobuf\Data\Value;

class Decoder {
    private const GEOMETRY_TYPES = ['Point', 'MultiPoint', 'LineString', 'MultiLineString',
'Polygon', 'MultiPolygon', 'GeometryCollection', ];

    /** @var Data */
    private static $data;
    private static $e;
    private static $dim;

    /**
     * Decode the geobuf file `$fileName` and save the resulting json as `$jsonFile`.
     * Returns the resulting file size if successful, or false.
     * @param string $geobufFile
     * @param string $jsonFile
     * @return false|int
     * @throws GeobufException
     */
    public static function decodeFileToJsonFile(string $geobufFile, string $jsonFile) {
        return file_put_contents($jsonFile, static::decodeFileToJson($geobufFile));
    }

    /**
     * Decode the geobuf file `$fileName` to a json string which is returned
     * @param string $fileName
     * @return string
     * @throws GeobufException
     */
    public static function decodeFileToJson(string $fileName): string {
        return static::decodeToJson(file_get_contents($fileName));
    }

    /**
     * Decode the geobuf input `$encodedInput` to a json string which is returned.
     * @param string $encodedInput
     * @return string
     * @throws GeobufException
     */
    public static function decodeToJson(string $encodedInput): string {
        return json_encode(static::decodeToArray($encodedInput));
    }

    /**
     * Decode the geobuf file `$fileName` and return the array.
     * @param string $fileName
     * @return array
     * @throws GeobufException
     */
    public static function decodeFileToArray(string $fileName): array {
        return static::decodeToArray(file_get_contents($fileName));
    }

    /**
     * Decode the geobuf input `$encodedInput` and return the array.
     * @param string $encodedInput
     * @return array
     * @throws GeobufException
     */
    public static function decodeToArray(string $encodedInput): array {
        static::$data = new Data();
        static::$data->mergeFromString($encodedInput);
        static::$e = 10**(static::$data->getPrecision());
        static::$dim = static::$data->getDimensions();

        try {
            static::$data->mergeFromString($encodedInput);
        } catch (Exception $e) {
            throw new GeobufException('Error while decoding Geobuf: ' . $e->getMessage(), 0, $e);
        }

        switch (static::$data->getDataType()) {
            case 'feature_collection':
                return static::decodeFeatureCollection(static::$data->getFeatureCollection());
            case 'feature':
                return static::decodeFeature(static::$data->getFeature());
            case 'geometry':
                return static::decodeGeometry(static::$data->getGeometry());
        }

        throw new GeobufException('Unknown data type ' . static::$data->getDataType());
    }

    /**
     * @param FeatureCollection $featureCollection
     * @return array
     */
    private static function decodeFeatureCollection(FeatureCollection $featureCollection): array {
        $obj = ['type' => 'FeatureCollection', 'features' => []];

        static::decodeProperties($featureCollection->getCustomProperties(), $featureCollection->getValues(), $obj);

        foreach ($featureCollection->getFeatures() as $feature) {
            $obj['features'][] = static::decodeFeature($feature);
        }

        return $obj;
    }

    /**
     * @param Feature $feature
     * @return array
     */
    private static function decodeFeature(Feature $feature): array {
        $obj = ['type' => 'Feature'];

        static::decodeProperties($feature->getCustomProperties(), $feature->getValues(), $obj);
        static::decodeId($feature, $obj);

        $obj['geometry'] = static::decodeGeometry($feature->getGeometry());

        if (count($feature->getProperties()) > 0) {
            $obj['properties'] = static::decodeProperties($feature->getProperties(), $feature->getValues());
        }

        return $obj;
    }

    /**
     * @param array|RepeatedField $props
     * @param RepeatedField|Value[] $values
     * @param null|array $dest
     * @return array
     */
    private static function decodeProperties($props, $values, ?array &$dest = null): array {
        $dest = $dest ?? [];
        $numProps = count($props);
        if (0 === $numProps) {
            return $dest;
        }

        $keys = static::$data->getKeys();
        $r = $numProps > 2 ? range(0, $numProps-1, 2) : [0];

        foreach ($r as $i) {
            $key = (string)$keys[$props[$i]];
            $val = $values[$props[$i+1]];
            $valueType = $val->getValueType();

            if ('string_value' == $valueType) {
                $dest[$key] = $val->getStringValue();
            } elseif ('double_value' == $valueType) {
                $dest[$key] = $val->getDoubleValue();
            } elseif ('pos_int_value' == $valueType) {
                $dest[$key] = $val->getPosIntValue();
            } elseif ('neg_int_value' == $valueType) {
                $dest[$key] = -$val->getNegIntValue();
            } elseif ('bool_value' == $valueType) {
                $dest[$key] = $val->getBoolValue();
            } elseif ('json_value' == $valueType) {
                $dest[$key] = json_decode($val->getJsonValue(), true);
            }
        }

        return $dest;
    }

    /**
     * @param Feature $feature
     * @param array $objJson
     */
    private static function decodeId(Feature $feature, array &$objJson): void {
        $idType = $feature->getIdType();
        if ('id' == $idType) {
            $objJson['id'] = $feature->getId();
        } elseif ('int_id' == $idType) {
            $objJson['id'] = $feature->getIntId();
        }
    }

    /**
     * @param null|Geometry $geometry
     * @return array
     */
    private static function decodeGeometry(?Geometry $geometry): ?array {
        if (null === $geometry) {
            return null;
        }
        $gt = static::GEOMETRY_TYPES[$geometry->getType()];
        $obj = ['type' => $gt];

        static::decodeProperties($geometry->getCustomProperties(), $geometry->getValues(), $obj);

        switch ($gt) {
            case 'GeometryCollection':
                $obj['geometries'] = array_map(
                    function ($g) { return static::decodeGeometry($g); },
                    $geometry->getGeometries()
                );
                break;
            case 'Point':
                $obj['coordinates'] = static::decodePoint($geometry->getCoords());
                break;
            case 'LineString':
            case 'MultiPoint':
                $obj['coordinates'] = static::decodeLine($geometry->getCoords());
                break;
            case 'MultiLineString':
            case 'Polygon':
                $obj['coordinates'] = static::decodeMultiLine($geometry, 'Polygon' === $gt);
                break;
            case 'MultiPolygon':
                $obj['coordinates'] = static::decodeMultiPolygon($geometry);
        }

        return $obj;
    }

    /**
     * @param float $coord
     * @return float
     */
    private static function decodeCoord(float $coord): float {
        return $coord/static::$e;
    }

    /**
     * @param array|RepeatedField $coords
     * @return array
     */
    private static function decodePoint($coords): array {
        $return = [];
        foreach ($coords as $coord) { // can't use array_map as $coords might be a RepeatedField
            $return[] = static::decodeCoord((float)$coord);
        }
        return $return;
    }

    /**
     * @param array|RepeatedField $coords
     * @param null|bool $isClosed
     * @return array
     */
    private static function decodeLine($coords, ?bool $isClosed = false): array {
        $obj = [];
        $numCoords = count($coords);
        $r = range(0, static::$dim-1);
        $r2 = $numCoords > static::$dim ? range(0, $numCoords-1, static::$dim) : [0];
        $p0 = array_fill(0, static::$dim, 0);

        foreach ($r2 as $i) {
            $p = array_map(
                function ($j) use ($i, $p0, $coords) { return ($p0[$j] ?? 0) + ($coords[$i + $j] ?? 0); },
                $r
            );
            $obj[] = static::decodePoint($p);
            $p0 = $p;
        }

        if (true === $isClosed) {
            $p = array_map(function ($j) use ($coords) { return $coords[$j]; }, $r);
            $obj[] = static::decodePoint($p);
        }

        return $obj;
    }

    /**
     * @param Geometry $geometry
     * @param null|bool $isClosed
     * @return array
     */
    private static function decodeMultiLine(Geometry $geometry, ?bool $isClosed = false): array {
        $coords = $geometry->getCoords();
        if (0 === count($geometry->getLengths())) {
            return [static::decodeLine($coords, $isClosed)];
        }

        $obj = [];
        $i = 0;

        foreach ($geometry->getLengths() as $length) {
            $obj[] = static::decodeLine(array_slice($coords, $i, $i + $length * static::$dim), $isClosed);
            $i += $length * static::$dim;
        }

        return $obj;
    }

    /**
     * @param Geometry $geometry
     * @return array
     */
    private static function decodeMultiPolygon(Geometry $geometry): array {
        $lengths = $geometry->getLengths();
        $coords = $geometry->getCoords();
        if (0 === count($lengths)) {
            return [[static::decodeLine($coords, true)]];
        }

        $obj = [];
        $i = 0;
        $j = 1;
        $numPolygons = $lengths[0];

        foreach (range(0, $numPolygons-1) as $n) {
            $numRings = $lengths[$j];
            $j++;
            $rings = [];

            foreach (array_slice($coords, $j, $j + $numRings) as $l) {
                $rings[] = static::decodeLine(array_slice($coords, $i, $i + $l * static::$dim), true);
                $j++;
                $i += $l * static::$dim;
            }

            $obj[] = $rings;
        }

        return $obj;
    }
}
