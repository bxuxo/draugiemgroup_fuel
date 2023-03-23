<?php

function ok($args) {
    extract($args);

    echo $a;
    echo $b;
    echo $c;
}

$arguments = [
    "a" => 1,
    "b" => 2,
    "c" => 3
];

ok($arguments);

?>