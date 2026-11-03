<?php

// Public catalog data. Costs and stock come from the Excel import and never live here.
$conicos = 'Cónicos y kits';
$rigidos = 'Rígidos 2RS';
$agricolas = 'Agrícolas e industriales';
$transmision = 'Transmisión y sellado';

return [
    'categories' => [$conicos, $rigidos, $agricolas, $transmision],
    'products' => [
        ['code' => '30204 SKF', 'excel' => '30204 SKF', 'category' => $conicos, 'measures' => '20 x 47 x 15.25 mm', 'description' => 'Rodamiento de rodillos cónicos', 'brand' => 'SKF'],
        ['code' => '32207 SKF', 'excel' => '32207 SKF', 'category' => $conicos, 'measures' => '35 x 72 x 24.25 mm', 'description' => 'Rodamiento de rodillos cónicos', 'brand' => 'SKF'],
        ['code' => '32210 SKF', 'excel' => '32210 SKF', 'category' => $conicos, 'measures' => '50 x 90 x 24.75 mm', 'description' => 'Rodamiento de rodillos cónicos', 'brand' => 'SKF', 'featured' => true],
        ['code' => 'SET 105 SKF', 'excel' => 'SET 105 SKF', 'category' => $conicos, 'measures' => '50 x 82 x 21.5 mm', 'description' => 'Kit de rodamiento de rodillos cónicos', 'brand' => 'SKF', 'featured' => true],
        ['code' => 'Kit punta de eje de rotativa', 'excel' => null, 'category' => $conicos, 'measures' => null, 'description' => 'Kit de rolineras SKF y estopera para punta de eje de rotativa', 'brand' => 'SKF', 'featured' => true, 'track_stock' => false],
        ['code' => '6202 2RS SKF', 'excel' => '6202 2RS SKF', 'category' => $rigidos, 'measures' => '15 x 35 x 11 mm', 'description' => 'Rodamiento rígido de bolas con sello de goma', 'brand' => 'SKF'],
        ['code' => '6204 2RS SKF', 'excel' => '6204 2RS SKF', 'category' => $rigidos, 'measures' => '20 x 47 x 14 mm', 'description' => 'Rodamiento rígido de bolas con sello de goma', 'brand' => 'SKF', 'featured' => true],
        ['code' => '6205 2RS SKF', 'excel' => '6205 2RS SKF', 'category' => $rigidos, 'measures' => '25 x 52 x 15 mm', 'description' => 'Rodamiento rígido de bolas de una hilera con doble sello de goma', 'brand' => 'SKF'],
        ['code' => '6206 2RS SKF', 'excel' => '6206 2RS SKF', 'category' => $rigidos, 'measures' => '30 x 62 x 16 mm', 'description' => 'Rodamiento rígido de bolas con sello de goma', 'brand' => 'SKF'],
        ['code' => '6207 2RS SKF', 'excel' => '6207 2RS SKF', 'category' => $rigidos, 'measures' => '35 x 72 x 17 mm', 'description' => 'Rodamiento rígido de bolas con sello de goma hermético en ambos lados', 'brand' => 'SKF'],
        ['code' => '6306 2RS SKF', 'excel' => '6306 2RS', 'category' => $rigidos, 'measures' => '30 x 72 x 19 mm', 'description' => 'Rodamiento rígido de bolas de serie reforzada con doble sello de goma', 'brand' => 'SKF'],
        ['code' => 'UC205 SKF', 'excel' => 'UC205 SKF', 'category' => $agricolas, 'measures' => '25 x 52 x 34 mm', 'description' => 'Rodamiento de inserción para chumacera', 'brand' => 'SKF'],
        ['code' => 'UC206 SKF', 'excel' => 'UC206 SKF', 'category' => $agricolas, 'measures' => '30 x 62 x 38 mm', 'description' => 'Rodamiento de inserción para chumacera', 'brand' => 'SKF', 'featured' => true],
        ['code' => 'UC207 SKF', 'excel' => 'UC207 SKF', 'category' => $agricolas, 'measures' => '35 x 72 x 42.9 mm', 'description' => 'Rodamiento de inserción para chumacera', 'brand' => 'SKF', 'featured' => true],
        ['code' => '207KRRB17 SKF', 'excel' => '207KRRB17 SKF', 'category' => $agricolas, 'measures' => '35 x 72 x 37.7 mm', 'description' => 'Rodamiento agrícola de bolas con sellos reforzados', 'brand' => 'SKF'],
        ['code' => 'CRUCETA 330', 'excel' => 'CRUCETA 330', 'category' => $transmision, 'measures' => '30 x 106 mm (dado x largo)', 'description' => 'Cruceta para transmisión / junta cardán', 'brand' => null, 'featured' => true],
        ['code' => 'CRUCETA 369', 'excel' => 'CRUCETA 369', 'category' => $transmision, 'measures' => '27 x 82 mm (dado x largo)', 'description' => 'Cruceta para cardán / cosechadora', 'brand' => null],
        ['code' => 'ESTOPERA 60X90X10', 'excel' => '60X90X10', 'category' => $transmision, 'measures' => '60 x 90 x 10 mm', 'description' => 'Estopera / retén de aceite', 'brand' => null],
    ],
];
