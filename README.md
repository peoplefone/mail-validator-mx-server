# Mail Validator by MX

This package allows you to set e-mail addresses and let them check through a connection to the remote MX server.

Avoid spamming so your server does not get blacklisted.

The validation returns an array of objects containing the:
* E-mail address
* MX domain(s)
* MX response code
* MX response message

## Installation

```bash
composer require peoplefone/mail-validator-mx-server
```

## How it works

That is the real communication with the remote MX Server.

```bash
telnet mx.domain.com 25
Trying 66.96.140.73...
Connected to mx.domain.com.
Escape character is '^]'.
220 bosimpinc12 bizsmtp ESMTP server ready
```

```bash
helo mydomain.com          
250 bosimpinc12 hello [95.128.82.70], pleased to meet you
```

```bash
MAIL FROM: <test@mydomain.com>
250 <test@mydomain.com> sender ok
```

```bash
RCPT TO: <username@domain.com>
250 <username@domain.com> recipient ok
```

```bash
quit
221 bosimpinc12 bizsmtp closing connection
```

## Example

### Basic Usage

```php
require("vendor/autoload.php");

use peoplefone\mailValidatorMXServer;

$class = new mailValidatorMXServer("domain.com", "postmaster@domain.com");

$class->setContact("username@test.com");
$class->setContact("username@domain.com");

$result = $class->validate();
print_r($result);
```

### Result

The returned codes correspond to the RFC5321.

https://tools.ietf.org/html/rfc5321

For default use, you can assume that the e-mail address is valid when you receive the code 250.

```
Array
(
    [0] => stdClass Object
        (
            [mail] => username@domain.com
            [host] => Array
                (
                    [0] => mx.domain.com
                )

            [code] => 250
            [text] => Requested mail action okay, completed
        )

    [1] => stdClass Object
        (
            [mail] => username@test.com
            [host] => Array
                (
                    [0] => mx.spamexperts.com
                    [1] => fallbackmx.spamexperts.eu
                    [2] => lastmx.spamexperts.net
                )

            [code] => 550
            [text] => Requested action not taken: mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)
        )

)
```

### Settings Functions

```php
/**
 * Port of the remove MX Server.
 */
$class->setConnectionPort("25");

/**
 * Timeout of each connection.
 */
$class->setConnectionTimeOut("30");

/**
 * Timeout of the single protocol communication.
 * Please note that some MX servers are configured to wait before responding. A dash (-) is added to the MX response code. Maybe you need to increase the timeout.
 */
$class->setStreamTimeOut("15");

/**
 * Print the debug log passing the boolean TRUE.
 */
$class->validate(true);
```

### Contact Functions

```php
$class->setContact("username@domain.com");

$class->unsetContact("username@domain.com");

$class->getContacts()
```