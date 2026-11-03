<?php

// Photos: catalog crops carry no credit; internet photos carry author, license and source.
$seal = [
    'path' => 'img/products/estopera-referencial.webp',
    'reference' => true,
    'author' => 'Juandev',
    'license' => 'CC BY-SA 3.0',
    'url' => 'https://commons.wikimedia.org/wiki/File:Gufero_s_prachovkou_(001).jpg',
];

$photo = fn (string $file, bool $reference = false) => ['path' => "img/products/{$file}.webp", 'reference' => $reference];

return [
    'site' => [],
    'products' => [
        '30204 SKF' => $photo('30204-skf'),
        '32207 SKF' => $photo('32207-skf'),
        '32210 SKF' => $photo('32210-skf'),
        'SET 105 SKF' => $photo('set-105-skf'),
        'Kit punta de eje de rotativa' => $photo('set-105-skf', true),
        '6202 2RS SKF' => $photo('6202-2rs-skf'),
        '6204 2RS SKF' => $photo('6204-2rs-skf'),
        '6205 2RS SKF' => $photo('6204-2rs-skf', true),
        '6206 2RS SKF' => $photo('6206-2rs-skf'),
        '6207 2RS SKF' => $photo('6207-2rs-skf'),
        '6306 2RS SKF' => $photo('6207-2rs-skf', true),
        'UC205 SKF' => $photo('uc205-skf'),
        'UC206 SKF' => $photo('uc206-skf'),
        'UC207 SKF' => $photo('uc207-skf'),
        '207KRRB17 SKF' => $photo('207krrb17-skf'),
        'CRUCETA 330' => $photo('cruceta-330'),
        'CRUCETA 369' => $photo('cruceta-369'),
        'ESTOPERA 60X90X10' => $seal,
    ],
];
