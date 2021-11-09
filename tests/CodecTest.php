<?php

use MBolli\PhpGeobuf\Decoder;
use MBolli\PhpGeobuf\Encoder;

const GEOJSON_DIR = './tests/geojson';

// test all geojson files
foreach (scandir(GEOJSON_DIR) as $i => $file) {
    if (is_dir($file)) continue;
    if (!preg_match("/^(.*)\.(geo|g)?json$/", $file)) continue;

    test('codec: ' . $file, function () use ($file) {
        $precision = 6;
        $dim = $file === '3dim.geojson' ? 3 : 2;
        $jsonString = file_get_contents(GEOJSON_DIR . DIRECTORY_SEPARATOR . $file);
        $jsonOrig = json_decode($jsonString, true);

        $buf = Encoder::encode($jsonString, $precision, $dim);
        expect($buf)->toBeString();

        $json = Decoder::decodeToArray($buf);
        expect($json)->toBeArray();

        /**echo $file . "\n";
        echo json_encode($jsonOrig) . "\n";
        echo json_encode($json) . "\n\n";*/

        expect($json)->toEqualCanonicalizing($jsonOrig);
    });
}

test('codec: valid closed polygon with high-precision coordinates', function () {
    $jsonString = file_get_contents(GEOJSON_DIR . DIRECTORY_SEPARATOR . 'geobuf-precision.json');

    $roundTripped = Decoder::decodeToArray(Encoder::encode($jsonString));

    $ring = $roundTripped['features'][0]['geometry']['coordinates'][0];
    expect($ring[0])->toEqual($ring[4]);
});

test('codec: a line with potential accumulating error', function () {
    // Generate a line of 40 points. Each point's x coordinate, x[n] is at x[n - 1] + 1 + d, where
    // d is a floating point number that just rounds to 0 at 6 decimal places, i.e. 0.00000049.
    // Therefore a delta compression method that only computes x[n] - x[n - 1] and rounds to 6 d.p.
    // will get a constant delta of 1.000000. The result will be an accumulated error along the
    // line of 0.00000049 * 40 = 0.0000196 over the full length.
    $feature = [
        'type' => 'MultiPolygon',
        'coordinates' => [[[]]]
    ];
    $points = 40;
    // X coordinates [0, 1.00000049,  2.00000098,  3.00000147,  4.00000196, ...,
    //                  37.00001813, 38.00001862, 39.00001911, 40.00001960, 0]
    for ($i = 0; $i <= $points; $i++) {
        $feature['coordinates'][0][0][] = [$i * 1.00000049, 0];
    }
    $feature['coordinates'][0][0][] = [0, 0];
    $roundTripped = Decoder::decodeToArray(Encoder::encode(json_encode($feature)));
    $roundX = fn($z) => round(($z[0] * 1000000) / 1000000.0);

    $xsOrig = array_map($roundX, $feature['coordinates'][0][0]);
    $xsRoundTripped = array_map($roundX, $roundTripped['coordinates'][0][0]);

    expect($xsRoundTripped)->toEqual($xsOrig);
});

test('codec: a circle with potential accumulating error', function () {
    // Generate an approximate circle with 16 points around.
    $feature = [
        'type' => 'MultiPolygon',
        'coordinates' => [[[]]]
    ];
    $points = 16;
    for ($i = 0; $i <= $points; $i++) {
        $feature['coordinates'][0][0][] = [
            cos(pi() * 2.0 * $i / $points),
            sin(pi() * 2.0 * $i / $points)
        ];
    }
    $roundTripped = Decoder::decodeToArray(Encoder::encode(json_encode($feature)));

    $roundCoord = function ($z) {
        $x = round($z[0] * 1000000);
        $y = round($z[1] * 1000000);
        // handle negative zero case (tape issue)
        if ($x === 0) $x = 0;
        if ($y === 0) $y = 0;
        return [$x, $y];
    };
    $ringOrig = array_map($roundCoord, $feature['coordinates'][0][0]);
    $ringRoundTripped = array_map($roundCoord, $roundTripped['coordinates'][0][0]);
    expect($ringRoundTripped)->toEqual($ringOrig);
});
