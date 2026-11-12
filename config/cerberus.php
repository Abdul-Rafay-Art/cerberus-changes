<?php

return [
    // Provisional values based on the supplied UI references; confirm with the client.
    'rating_scheme' => [
        ['code' => 'good', 'label' => 'Good', 'color' => '#27865e', 'score' => 4],
        ['code' => 'adequate', 'label' => 'Adequate', 'color' => '#b67a2d', 'score' => 3],
        ['code' => 'warning', 'label' => 'Warning', 'color' => '#e1c93f', 'score' => 2],
        ['code' => 'poor', 'label' => 'Poor', 'color' => '#b4363a', 'score' => 1],
        ['code' => 'not_inspected', 'label' => 'Not inspected', 'color' => '#69757a', 'score' => null],
        ['code' => 'not_applicable', 'label' => 'Not applicable', 'color' => '#879195', 'score' => null],
    ],
];
