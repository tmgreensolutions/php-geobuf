<?php

namespace MBolli\PhpGeobuf;

use ArrayAccess;
use Exception;
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
     * @return string
     * @throws GeobufException
     */
    public static function decodeFileToArray(string $fileName): string {
        return static::decodeToArray(file_get_contents($fileName));
    }

    /**
     * Decode the geobuf input `$encodedInput` and return the array.
     * @param string $encodedInput
     * @return string
     * @throws GeobufException
     */
    public static function decodeToArray(string $encodedInput): array {
        static::$data = new Data();
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
     * @param FeatureCollection $feature_collection
     * @return array
     */
    private static function decodeFeatureCollection(FeatureCollection $feature_collection): array {
        $obj = ['type' => 'FeatureCollection', 'features' => []];

        static::decodeProperties($feature_collection->getCustomProperties(), $feature_collection->getValues(), $obj);

        foreach ($feature_collection->getFeatures() as $feature) {
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
     * @param ArrayAccess $props
     * @param ArrayAccess|Value[] $values
     * @param null|array $dest
     * @return array
     */
    private static function decodeProperties(ArrayAccess $props, ArrayAccess $values, ?array &$dest = null): array {
        $dest ??= [];
        $keys = static::$data->getKeys();

        foreach ($props as $i => $prop) {
            $val = $values[$props[$i+1]];
            $key = $keys[$prop];
            $value_type = $val->getValueType();

            if ('string_value' == $value_type) {
                $dest[$key] = $val->getStringValue();
            } elseif ('double_value' == $value_type) {
                $dest[$key] = $val->getDoubleValue();
            } elseif ('pos_int_value' == $value_type) {
                $dest[$key] = $val->getPosIntValue();
            } elseif ('neg_int_value' == $value_type) {
                $dest[$key] = -$val->getNegIntValue();
            } elseif ('bool_value' == $value_type) {
                $dest[$key] = $val->getBoolValue();
            } elseif ('json_value' == $value_type) {
                $dest[$key] = json_decode($val->getJsonValue());
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
     * @param Geometry $geometry
     * @return array
     */
    private static function decodeGeometry(Geometry $geometry): array {
        $gt = static::GEOMETRY_TYPES[$geometry->getType()];
        $obj = ['type' => $gt];

        static::decodeProperties($geometry->getCustomProperties(), $geometry->getValues(), $obj);

        switch ($gt) {
            case 'GeometryCollection':
                $obj['geometries'] = array_map(
                    fn ($g) => static::decodeGeometry($g),
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
     * @param array|ArrayAccess $coords
     * @return array
     */
    private static function decodePoint($coords): array {
        return array_map(
            fn ($c) => static::decodeCoord((float)$c),
            $coords
        );
    }

    /**
     * @param array|ArrayAccess $coords
     * @param null|bool $isClosed
     * @return array
     */
    private static function decodeLine($coords, ?bool $isClosed = false): array {
        $obj = [];
        $r = range(0, static::$dim);
        $r2 = range(0, count($coords), static::$dim);
        $p0 = array_fill(0, static::$dim, 0);
        foreach ($r2 as $i) {
            $p = array_map(
                fn ($j) => ($p0[$j] ?? 0) + ($coords[$i + $j] ?? 0),
                $r
            );
            $obj[] = static::decodePoint($p);
            $p0 = $p;
        }

        if (true === $isClosed) {
            $p = array_map(fn ($j) => $coords[$j], $r);
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
        $num_polygons = $lengths[0];

        foreach (range(0, $num_polygons) as $n) {
            $num_rings = $lengths[$j];
            $j++;
            $rings = [];

            foreach (array_slice($coords, $j, $j + $num_rings) as $l) {
                $rings[] = static::decodeLine(array_slice($coords, $i, $i + $l * static::$dim), true);
                $j++;
                $i += $l * static::$dim;
            }

            $obj[] = $rings;
        }

        return $obj;
    }
}
