<?php


echo "this is test.php";
function tester()
{
    echo "in tester function";
    xdebug_break();
    $var = "this is a variable";
    var_dump("xdebug break just before this line and this has a breakpoint");
    var_dump("this line is after the xdebug breakpoints");
}

tester();