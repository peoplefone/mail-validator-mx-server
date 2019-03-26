<?php

require_once __DIR__ . '/../vendor/autoload.php';

use peoplefone\mailValidatorMXServer;

$class = new mailValidatorMXServer("domain.com", "postmaster@domain.com");

/**
 * ##############################
 * CONFIGURATION
 * ##############################
 */
$class->setConnectionPort("25");
$class->setConnectionTimeOut("30");
$class->setStreamTimeOut("15");

/**
 * ##############################
 * SET / UNSET CONTACTS
 * ##############################
 */
$class->setContact("username@test.com");
$class->setContact("username@domain.com");

print str_pad("BEFORE UNSET => ", 30) . implode(", ", $class->getContacts()) . PHP_EOL;

$class->unsetContact("username@test.com");

print str_pad("AFTER UNSET => ", 30) . implode(", ", $class->getContacts()) . PHP_EOL;

/**
 * ##############################
 * VALIDATE AND PRINT RESULT
 * ##############################
 */

$result = $class->validate();
print_r($result);