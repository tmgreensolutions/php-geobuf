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
'Polygon', 'MultiPolygon', 'GeometryCollection'];

    /** @var Data */
    private static $data;
    private static $e;
    private static $dim;

    /**
     * @param string $encodedInput
     * @return string
     * @throws GeobufException
     */
    public static function decodeToJson(string $encodedInput): string {
        return json_encode(static::decodeToArray($encodedInput));
    }

    /**
     * @param string $encodedInput
     * @return string
     * @throws GeobufException
     */
    public static function decodeToArray(string $encodedInput): array {
        static::$data = new Data();
        static::$e = pow(10, static::$data->getPrecision());
        static::$dim = static::$data->getDimensions();

        try {
            static::$data->mergeFromString($encodedInput);
        } catch (Exception $e) {
            throw new GeobufException('Error while decoding Geobuf: ' . $e->getMessage(), 0, $e);
        }

        switch (static::$data->getDataType()) {
            case 'feature_collection':
                return static::decode_feature_collection(static::$data->getFeatureCollection());
            case 'feature':
                return static::decode_feature(static::$data->getFeature());
            case 'geometry':
                return static::decode_geometry(static::$data->getGeometry());
        }

        throw new GeobufException('Unknown data type ' . static::$data->getDataType());
    }

    private static function decode_feature_collection(FeatureCollection $feature_collection): array {
        $obj = ['type' => 'FeatureCollection', 'features' => []];

        static::decode_properties($feature_collection->getCustomProperties(), $feature_collection->getValues(), $obj);

        foreach ($feature_collection->getFeatures() as $feature) {
            $obj['features'][] = static::decode_feature($feature);
        }

        return $obj;
    }

    private static function decode_feature(Feature $feature): array {
        $obj = ['type' => 'Feature'];

        static::decode_properties($feature->getCustomProperties(), $feature->getValues(), $obj);
        static::decode_id($feature, $obj);

        $obj['geometry'] = static::decode_geometry($feature->getGeometry());

        if (count($feature->getProperties()) > 0) {
            $obj['properties'] = static::decode_properties($feature->getProperties(), $feature->getValues());
        }

        return $obj;
    }

    /**
     * @param ArrayAccess $props
     * @param Value[]|ArrayAccess $values
     * @param array|null $dest
     * @return array
     */
    private static function decode_properties(ArrayAccess $props, ArrayAccess $values, ?array &$dest = null): array {
        $dest ??= [];
        $keys = static::$data->getKeys();

        foreach ($props as $i => $prop) {
            $val = $values[$props[$i+1]];
            $key = $keys[$prop];
            $value_type = $val->getValueType();

            if ($value_type == 'string_value') {
                $dest[$key] = $val->getStringValue();
            } elseif ($value_type == 'double_value') {
                $dest[$key] = $val->getDoubleValue();
            } elseif ($value_type == 'pos_int_value') {
                $dest[$key] = $val->getPosIntValue();
            } elseif ($value_type == 'neg_int_value') {
                $dest[$key] = -$val->getNegIntValue();
            } elseif ($value_type == 'bool_value') {
                $dest[$key] = $val->getBoolValue();
            } elseif ($value_type == 'json_value') {
                $dest[$key] = json_decode($val->getJsonValue());
            }
        }

        return $dest;
    }

    /**
     * @param Feature $feature
     * @param array $objJson
     */
    private static function decode_id(Feature $feature, array &$objJson): void {
        $idType = $feature->getIdType();
        if ($idType == 'id') {
            $objJson['id'] = $feature->getId();
        } elseif ($idType == 'int_id') {
            $objJson['id'] = $feature->getIntId();
        }
    }

    private static function decode_geometry(Geometry $geometry): array {
        $gt = static::GEOMETRY_TYPES[$geometry->getType()];
        $obj = ['type' => $gt];

        static::decode_properties($geometry->getCustomProperties(), $geometry->getValues(), $obj);

        switch ($gt) {
            case 'GeometryCollection':
                $obj['geometries'] = array_map(
                    fn($g) => static::decode_geometry($g),
                    $geometry->getGeometries()
                );
                break;
            case 'Point':
                $obj['coordinates'] = static::decode_point($geometry->getCoords());
                break;
            case 'LineString':
            case 'MultiPoint':
                $obj['coordinates'] = static::decode_line($geometry->getCoords());
                break;
            case 'MultiLineString':
            case 'Polygon':
                $obj['coordinates'] = static::decode_multi_line($geometry, $gt === 'Polygon');
                break;
            case 'MultiPolygon':
                $obj['coordinates'] = static::decode_multi_polygon($geometry);
        }

        return $obj;
    }

    private static function decode_coord(float $coord): float {
        return $coord/static::$e;
    }

    /**
     * @param ArrayAccess|array $coords
     * @return array
     */
    private static function decode_point($coords): array {
        return array_map(
            fn ($c) => static::decode_coord((float)$c),
            $coords
        );
    }

    /**
     * @param array|ArrayAccess $coords
     * @param bool|null $isClosed
     * @return array
     */
    private static function decode_line($coords, ?bool $isClosed = false): array {
        $obj = [];
        $r = range(0, static::$dim);
        $r2 = range(0, count($coords), static::$dim);
        $p0 = array_fill(0, static::$dim, 0);
        foreach ($r2 as $i) {
            $p = array_map(
                fn ($j) => ($p0[$j] ?? 0) + ($coords[$i + $j] ?? 0),
                $r
            );
            $obj[] = static::decode_point($p);
            $p0 = $p;
        }

        if ($isClosed === true) {
            $p = array_map(fn ($j) => $coords[$j], $r);
            $obj[] = static::decode_point($p);
        }

        return $obj;
    }

    private static function decode_multi_line(Geometry $geometry, ?bool $is_closed = false): array {
        $coords = $geometry->getCoords();
        if (count($geometry->getLengths()) === 0) {
            return [static::decode_line($coords, $is_closed)];
        }

        $obj = [];
        $i = 0;

        foreach ($geometry->getLengths() as $length) {
            $obj[] = static::decode_line(array_slice($coords, $i, $i + $length * static::$dim), $is_closed);
            $i += $length * static::$dim;
        }

        return $obj;
    }

    private static function decode_multi_polygon(Geometry $geometry): array {
        $lengths = $geometry->getLengths();
        $coords = $geometry->getCoords();
        if (count($lengths) === 0) {
            return [[static::decode_line($coords, true)]];
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
                $rings[] = static::decode_line(array_slice($coords, $i, $i + $l * static::$dim), true);
                $j++;
                $i += $l * static::$dim;
            }

            $obj[] = $rings;
        }

        return $obj;
    }
}
