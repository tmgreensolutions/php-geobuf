<?php

namespace MBolli\PhpGeobuf;

use MBolli\PhpGeobuf\Data\Feature;
use MBolli\PhpGeobuf\Data\FeatureCollection;
use MBolli\PhpGeobuf\Data\Geometry;
use MBolli\PhpGeobuf\Data\Value;
use MBolli\PhpGeobuf\Interfaces\IHasAnyProperties;
use MBolli\PhpGeobuf\Interfaces\IHasCustomProperties;
use MBolli\PhpGeobuf\Interfaces\IHasProperties;
use JsonException;

class Encoder {
    private const GEOMETRY_TYPES = [
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
     * encodes the JSON file `$jsonFile` to Geobuf and stores it in the file `$geobufFile`.
     * returns the stored file size on success, or false on failure.
     *
     * @param string $jsonFile
     * @param string $geobufFile
     * @param int $precision
     * @param int $dim
     * @return false|int
     * @throws GeobufException
     */
    public static function encodeFileToBufFile(string $jsonFile, string $geobufFile, int $precision = 6, int $dim = 2) {
        return file_put_contents($geobufFile, static::encodeFileToBuf($jsonFile, $precision, $dim));
    }

    /**
     * encodes the JSON file `$fileName` and returns the geobuf-encoded string.
     *
     * @param string $fileName
     * @param int $precision
     * @param int $dim
     * @return string
     * @throws GeobufException
     */
    public static function encodeFileToBuf(string $fileName, int $precision = 6, int $dim = 2): string {
        return static::encode(file_get_contents($fileName), $precision, $dim);
    }


    /**
     * encodes a json string `$dataJson` to Geobuf and stores it in the file `$filePath`.
     * returns the stored file size on success, or false on failure.
     *
     * @param string $filePath
     * @param string $dataJson
     * @param int $precision
     * @param int $dim
     * @return false|int
     * @throws GeobufException
     */
    public static function encodeToFile(string $filePath, string $dataJson, int $precision = 6, int $dim = 2) {
        return file_put_contents($filePath, static::encode($dataJson));
    }

    /**
     * encodes a json string `$dataJson` to Geobuf and returns the resulting string.
     *
     * @param string $dataJson
     * @param int $precision
     * @param int $dim
     * @return string
     * @throws GeobufException
     */
    public static function encode(string $dataJson, int $precision = 6, int $dim = 2): string {
        try {
            $geoJson = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new GeobufException('Error while decoding GeoJSON: ' . $e->getMessage(), 0, $e);
        }

        static::$json = $geoJson;
        static::$data = new Data();
        static::$dim = $dim;
        static::$e = 10** $precision; # multiplier for converting coordinates into integers

        $dataType = static::$json['type'];
        if ('FeatureCollection' == $dataType) {
            static::$data->setFeatureCollection(static::encodeFeatureCollection());
        } elseif ('Feature' == $dataType) {
            static::$data->setFeature(static::encodeFeature(static::$json));
        } else {
            static::$data->setGeometry(static::encodeGeometry(static::$json));
        }

        return static::$data->serializeToString();
    }

    /**
     * @return FeatureCollection
     */
    private static function encodeFeatureCollection(): FeatureCollection {
        $featureCollection = new FeatureCollection();
        static::encodeCustomProperties($featureCollection, static::$json, ['type', 'features']);
        $features = [];
        foreach (static::$json['features'] as $featureJson) {
            $features[] = static::encodeFeature($featureJson);
        }
        $featureCollection->setFeatures($features);
        return $featureCollection;
    }

    /**
     * @param array $featureJson
     * @return Feature
     */
    private static function encodeFeature(array $featureJson): Feature {
        $feature = new Feature();
        static::encodeId($feature, $featureJson['id'] ?? null);
        static::encodeProperties($feature, $featureJson['properties'] ?? []);
        static::encodeCustomProperties($feature, $featureJson, ['type', 'id', 'properties', 'geometry']);
        if (array_key_exists('geometry', $featureJson) && null !== $featureJson['geometry']) {
            $feature->setGeometry(static::encodeGeometry($featureJson['geometry']));
        } else {
            $feature->setGeometry(null);
        }
        return $feature;
    }

    /**
     * @param array $geometryJson
     * @return Geometry
     */
    private static function encodeGeometry(array $geometryJson): Geometry {
        $geometry = new Geometry();
        $gt = $geometryJson['type'];
        $coords = $geometryJson['coordinates'];

        $geometry->setType(static::GEOMETRY_TYPES[$gt]);

        static::encodeCustomProperties(
            $geometry,
            $geometryJson,
            ['type', 'id', 'coordinates', 'arcs', 'geometries', 'properties']
        );

        switch ($gt) {
            case 'GeometryCollection':
                $geometries = [];
                foreach ($geometryJson['geometries'] as $geom) {
                    $geometries[] = static::encodeGeometry($geom);
                }
                $geometry->setGeometries($geometries);
                break;
            case 'Point':
                $geometry->setCoords(static::addPoint($coords));
                break;
            case 'LineString':
            case 'MultiPoint':
                $geometry->setCoords(static::addLine($coords));
                break;
            case 'MultiLineString':
                static::addMultiLine($geometry, $coords);
                break;
            case 'Polygon':
                static::addMultiLine($geometry, $coords, true);
                break;
            case 'MultiPolygon':
                static::addMultiPolygon($geometry, $coords);
                break;
        }

        return $geometry;
    }

    /**
     * @param IHasProperties $obj
     * @param array $propsJson
     */
    private static function encodeProperties(IHasProperties $obj, array $propsJson): void {
        foreach ($propsJson as $key => $val) {
            static::encodeProperty($key, $val, $obj);
        }
    }

    /**
     * @param IHasCustomProperties $obj
     * @param array $objJson
     * @param array $exclude
     */
    private static function encodeCustomProperties(IHasCustomProperties $obj, array $objJson, array $exclude): void {
        foreach ($objJson as $key => $val) {
            if (!in_array($key, $exclude)) {
                static::encodeProperty($key, $val, $obj, true);
            }
        }
    }

    /**
     * @param string $key
     * @param $val
     * @param IHasCustomProperties|IHasProperties $obj
     * @return Value
     */
    private static function encodeProperty(string $key, $val, $obj, bool $custom = false): void {
        $keyIndex = array_search($key, static::$keys, true);

        if (false === $keyIndex) {
            static::$keys[$key] = true;
            static::$data->addKey($key);
            $keyIndex = count(static::$data->getKeys()) - 1;
        }

        $value = new Value();

        if (is_array($val)) {
            $value->setJsonValue(json_encode($val));
        } elseif (is_string($val)) {
            $value->setStringValue($val);
        } elseif (is_float($val)) {
            $value->setDoubleValue($val);
        } elseif (is_int($val)) {
            static::encodeInt($value, $val);
        } elseif (is_bool($val)) {
            $value->setBoolValue($val);
        } elseif (is_numeric($val)) {
            static::encodeInt($value, (int)$val);
        }

        $obj->addValue($value);

        $valuesIndex = count($obj->getValues())-1;
        if (true === $custom) {
            $obj->addCustomProperty($keyIndex);
            $obj->addCustomProperty($valuesIndex);
        } else {
            $obj->addProperty($keyIndex);
            $obj->addProperty($valuesIndex);
        }
    }

    /**
     * @param Value $value
     * @param int $val
     */
    private static function encodeInt(Value $value, int $val): void {
        if ($val >= 0) {
            $value->setPosIntValue($val);
        } else {
            $value->setNegIntValue(-$val);
        }
    }

    /**
     * @param Feature $feature
     * @param $id
     */
    private static function encodeId(Feature $feature, $id): void {
        if (null === $id) {
            return;
        }
        if (is_int($id)) {
            $feature->setIntId($id);
        } else {
            $feature->setId($id);
        }
    }

    /**
     * @param array $coords
     * @param $coord
     */
    private static function addCoord(array &$coords, $coord): void {
        $coords[] = (int)round($coord * static::$e);
    }

    /**
     * @param $point
     * @return array
     */
    private static function addPoint($point): array {
        $coords = [];
        foreach ($point as $x) {
            static::addCoord($coords, $x);
        }
        return $coords;
    }

    /**
     * @param array $points
     * @param bool $isClosed
     * @return array
     */
    private static function addLine(array $points, bool $isClosed = false): array {
        $coords = [];
        $sum = array_fill(0, static::$dim, 0);
        for ($i = 0; $i < count($points) - (int)$isClosed; $i++) {
            for ($j = 0; $j < static::$dim; $j++) {
                $n = (int)(round($points[$i][$j] * static::$e) - $sum[$j]);
                $coords[] = $n;
                $sum[$j] += $n;
            }
        }

        return $coords;
    }

    /**
     * @param Geometry $geometry
     * @param array $lines
     * @param bool $isClosed
     */
    private static function addMultiLine(Geometry $geometry, array $lines, bool $isClosed = false): void {
        if (1 !== count($lines)) {
            foreach ($lines as $points) {
                $geometry->addLength(count($points) - (int)$isClosed);
            }
        }

        $coords = [];
        foreach ($lines as $points) {
            $coords = array_merge($coords, static::addLine($points, $isClosed));
        }
        $geometry->setCoords($coords);
    }

    /**
     * @param Geometry $geometry
     * @param array $polygons
     */
    private static function addMultiPolygon(Geometry $geometry, array $polygons): void {
        if (1 !== count($polygons) || 1 !== count($polygons[0])) {
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
                $coords = array_merge($coords, static::addLine($points, true));
            }
        }
        $geometry->setCoords($coords);
    }
}
