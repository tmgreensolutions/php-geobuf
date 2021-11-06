<?php

namespace MBolli\PhpGeobuf;

use MBolli\PhpGeobuf\Data\Feature;
use MBolli\PhpGeobuf\Data\FeatureCollection;
use MBolli\PhpGeobuf\Data\Geometry;
use MBolli\PhpGeobuf\Data\Value;
use MBolli\PhpGeobuf\Interfaces\IHasAnyProperties;
use MBolli\PhpGeobuf\Interfaces\IHasCustomProperties;
use MBolli\PhpGeobuf\Interfaces\IHasProperties;

class Encoder {

    private const geometry_types = [
        'Point' => 0,
        'MultiPoint' => 1,
        'LineString' => 2,
        'MultiLineString' => 3,
        'Polygon' => 4,
        'MultiPolygon' => 5,
        'GeometryCollection' => 6,
    ];

    private static $json;
    /** @var Data */
    private static $data;
    private static $dim;
    private static $e;
    private static $keys = [];

    /**
     * encodes a json string `$dataJson` to Geobuf and stores it in the file `$filePath`.
     * returns the stored file size on success, or false on failure.
     *
     * @param string $filePath
     * @param string $dataJson
     * @param int $precision
     * @param int $dim
     * @return int|false
     * @throws GeobufException
     */
    public static function encodeToFile(string $filePath, string $dataJson, int $precision = 6, int $dim = 2) {
        return file_put_contents($filePath, static::encode($dataJson));
    }

    /**
     * @param string $data_json
     * @param int $precision
     * @param int $dim
     * @return string
     * @throws GeobufException
     */
    public static function encode(string $data_json, int $precision = 6, int $dim = 2) {
        try {
            $geoJson = json_decode($data_json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GeobufException('Error while decoding GeoJSON: ' . $e->getMessage(), 0, $e);
        }

        static::$json = $geoJson;
        static::$data = new Data();
        static::$dim = $dim;
        static::$e = pow(10, $precision); # multiplier for converting coordinates into integers

        $data_type = static::$json['type'];
        if ($data_type == 'FeatureCollection')
            static::$data->setFeatureCollection(static::encode_feature_collection());
        elseif ($data_type == 'Feature')
            static::$data->setFeature(static::encode_feature(static::$json));
        else
            static::$data->setGeometry(static::encode_geometry(static::$json));

        return static::$data->serializeToString();
    }

    private static function encode_feature_collection(): FeatureCollection {
        $feature_collection = new FeatureCollection();
        static::encode_custom_properties($feature_collection, static::$json, ['type', 'features']);
        $features = [];
        foreach (static::$json['features'] as $feature_json) {
            $features[] = static::encode_feature($feature_json);
        }
        $feature_collection->setFeatures($features);
        return $feature_collection;
    }

    private static function encode_feature(array $feature_json): Feature {
        $feature = new Feature();
        static::encode_id($feature, $feature_json['id'] ?? null);
        static::encode_properties($feature, $feature_json['properties']);
        static::encode_custom_properties($feature, $feature_json, ['type', 'id', 'properties', 'geometry']);
        $feature->setGeometry(static::encode_geometry($feature_json['geometry']));
        return $feature;
    }

    private static function encode_geometry(array $geometry_json): Geometry {
        $geometry = new Geometry();
        $gt = $geometry_json['type'];
        $coords = $geometry_json['coordinates'];

        $geometry->setType(static::geometry_types[$gt]);

        static::encode_custom_properties($geometry, $geometry_json,
            ['type', 'id', 'coordinates', 'arcs', 'geometries', 'properties']);

        switch ($gt) {
            case 'GeometryCollection':
                $geometries = [];
                foreach ($geometry_json['geometries'] as $geom)
                    $geometries[] = static::encode_geometry($geom);
                $geometry->setGeometries($geometries);
                break;
            case 'Point':
                $geometry->setCoords(static::add_point($coords));
                break;
            case 'LineString':
            case 'MultiPoint':
                $geometry->setCoords(static::add_line($coords));
                break;
            case 'MultiLineString':
                static::add_multi_line($geometry, $coords);
                break;
            case 'Polygon':
                static::add_multi_line($geometry, $coords, true);
                break;
            case 'MultiPolygon':
                static::add_multi_polygon($geometry, $coords);
                break;
        }

        return $geometry;
    }

    private static function encode_properties(IHasProperties $obj, array $props_json) {
        foreach ($props_json as $key => $val) {
            $obj->addProperty(static::encode_property($key, $val, $obj));
        }
    }

    private static function encode_custom_properties(IHasCustomProperties $obj, array $obj_json, array $exclude) {
        foreach ($obj_json as $key => $val) {
            if (!in_array($key, $exclude))
                $obj->addCustomProperty(static::encode_property($key, $val, $obj));
        }
    }

    private static function encode_property(string $key, $val, IHasSomeProperties $obj): Value {

        $key_index = array_search($key, static::$keys, true);

        if ($key_index === false) {
            static::$keys[$key] = true;
            static::$data->addKey($key);
            $key_index = count(static::$data->getKeys()) - 1;
        }
        $value = new Value();

        if (is_array($val))
            $value->setJsonValue(json_encode($val));
        elseif (is_string($val))
            $value->setStringValue($val);
        elseif (is_float($val))
            $value->setDoubleValue($val);
        elseif (is_int($val))
            static::encode_int($value, $val);
        elseif (is_bool($val))
            $value->setBoolValue($val);
        elseif (is_numeric($val))
            static::encode_int($value, (int)$val);

        if (method_exists($obj, 'addProperty')) {
            $obj->addProperty($key_index);
            $obj->addProperty(count($obj->getValues())-1);
        }

        return $value;
}

    private static function encode_int(Value $value, int $val) {
        if ($val >= 0)
            $value->setPosIntValue($val);
        else
            $value->setNegIntValue(-$val);
    }

    private static function encode_id(Feature $feature, $id) {
        if ($id === null) return;
        if (is_int($id)) $feature->setIntId($id);
        else $feature->setId($id);
    }

    private static function add_coord(array &$coords, $coord) {
        $coords[] = (int)round($coord * static::$e);
    }

    private static function add_point($point): array {
        $coords = [];
        foreach($point as $x)
            static::add_coord($coords, $x);
        return $coords;
    }

    private static function add_line(array $points, bool $is_closed = false): array {
        $coords = [];
        $sum = array_fill(0, static::$dim, 0);
        for ($i = 0; $i < count($points) - (int)$is_closed; $i++) {
            for ($j = 0; $j < static::$dim; $j++) {
                $n = (int)round($points[$i][$j] * static::$e) - $sum[$j];
                $coords[] = $n;
                $sum[$j] += $n;
            }
        }
        return $coords;
    }

    private static function add_multi_line(Geometry $geometry, array $lines, bool $is_closed = false) {
        if (count($lines) !== 1) {
            foreach ($lines as $points) {
                $geometry->addLength(count($points) - (int)$is_closed);
            }
        }

        $coords = [];
        foreach ($lines as $points) {
            $coords = array_merge($coords, static::add_line($points, $is_closed));
        }
        $geometry->setCoords($coords);

    }

    private static function add_multi_polygon(Geometry $geometry, array $polygons) {
        if (count($polygons) !== 1 || count($polygons[0]) !== 1) {
            $geometry->addLength(count($polygons));
            foreach ($polygons as $rings) {
                $geometry->addLength(count($rings));

                foreach ($rings as $points) {
                    $geometry->addLength(count($points)-1);
                }
            }
        }

        $coords = [];
        foreach ($polygons as $rings) {
            foreach ($rings as $points) {
                $coords = array_merge($coords, static::add_line($points, true));
            }
        }
        $geometry->setCoords($coords);
    }
}
