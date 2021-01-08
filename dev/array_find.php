<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    dbg(array_find([1,2,3,4,5], fn ($i) => $i === 3));
    dbg(array_find_value([1,2,3,4,5], fn ($i) => $i === 3 ? 'yes' : null));