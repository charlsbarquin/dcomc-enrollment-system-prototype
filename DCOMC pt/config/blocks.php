<?php

return [
    /*
    | When true: blocks are considered "full" at 50 students (strict rule).
    | When false: blocks use their own capacity (dynamic); e.g. 2 students in a block is OK.
    | Set to false for testing so blocks don't need to be filled to 50.
    */
    'strict_50_per_block' => env('BLOCKS_STRICT_50', true),

    /*
    | Default capacity for newly auto-created blocks (when assignment creates a new block).
    | When strict_50 is true this is 50; when false you can use a lower default for testing.
    */
    'default_capacity' => (int) env('BLOCKS_DEFAULT_CAPACITY', 50),
];
