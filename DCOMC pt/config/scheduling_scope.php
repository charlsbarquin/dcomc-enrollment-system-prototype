<?php

return [
    /*
    | department_scope values for professors and rooms.
    | Used for visibility: Dean Education sees education + all; Dean Entrepreneurship sees entrepreneurship + all.
    */
    'scopes' => ['all', 'education', 'entrepreneurship'],

    'scope_all' => 'all',
    'scope_education' => 'education',
    'scope_entrepreneurship' => 'entrepreneurship',

    /*
    | Scopes a Registrar can assign when creating professor/room.
    */
    'registrar_allowed_scopes' => ['all', 'education', 'entrepreneurship'],

    /*
    | Scopes Dean Education can assign (cannot create entrepreneurship-only).
    */
    'dean_education_allowed_scopes' => ['all', 'education'],

    /*
    | Scopes Dean Entrepreneurship can assign (cannot create education-only).
    */
    'dean_entrepreneurship_allowed_scopes' => ['all', 'entrepreneurship'],
];
