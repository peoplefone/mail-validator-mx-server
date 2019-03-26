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
$class->setContact("noreply@hotmail.com");
$class->setContact("noreply@gmail.com");
$class->setContact("noreply@yahoo.com");

print str_pad("BEFORE UNSET => ", 15) . implode(", ", $class->getContacts()) . PHP_EOL;

$class->unsetContact("noreply@yahoo.com");

print str_pad("AFTER UNSET => ", 15) . implode(", ", $class->getContacts()) . PHP_EOL;

/**
 * ##############################
 * VALIDATE AND PRINT RESULT
 * ##############################
 */

$result = $class->validate(true);
print_r($result);