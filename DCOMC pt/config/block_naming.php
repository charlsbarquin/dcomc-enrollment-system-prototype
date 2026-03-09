<?php

return [
    // Block format: PREFIX yearNum - section (e.g. BEED 1 - 1, CAED 1 - A).
    // suffix=number -> section is 1, 2, 3... (educ elementary)
    // suffix=letter -> section is A, B, C... (educ majors, etc.)
    'rules' => [
        [
            'keywords' => ['elementary education', 'beed'],
            'prefix' => 'BEED',
            'suffix' => 'number',
        ],
        [
            'keywords' => ['culture and arts', 'bcaed', 'caed'],
            'prefix' => 'CAED',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['secondary education', 'bsed'],
            'prefix' => 'BSED',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['physical education', 'bped'],
            'prefix' => 'BPED',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['technical-vocational', 'btvted'],
            'prefix' => 'BTVTED',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['information technology', 'bsit', 'it'],
            'prefix' => 'BSIT',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['office administration', 'bsoa'],
            'prefix' => 'BSOA',
            'suffix' => 'letter',
        ],
        [
            'keywords' => ['entrepreneurship', 'bse'],
            'prefix' => 'BSE',
            'suffix' => 'letter',
        ],
    ],
];

