# Mail Validator by MX

This package allows you to define e-mail addresses to verify them via the corresponding remote MX server.

The  validation returns an array of objects containing:
* E-mail address
* MX domain(s)
* MX response code
* MX response message

## Installation

```bash
composer require peoplefone/mail-validator-mx-server
```

## How it works

These are the requests and responses with the remote MX server.

```bash
telnet mx.domain.com 25
Trying 66.96.140.73...
Connected to mx.domain.com.
Escape character is '^]'.
220 bosimpinc12 bizsmtp ESMTP server ready
```

```bash
helo mydomain.com
250 bosimpinc12 hello [95.128.x.x], pleased to meet you
```

```bash
MAIL FROM: <me@mydomain.com>
250 <me@mydomain.com> sender ok
```

```bash
RCPT TO: <test@domain.com>
250 <test@domain.com> recipient ok
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

$class = new mailValidatorMXServer("mydomain.com", "me@mydomain.com");

$class->setContact("noreply@hotmail.com");
$class->setContact("noreply@gmail.com");
$class->setContact("noreply@yahoo.com");

$result = $class->validate();
print_r($result);
```

### Result

The returned codes correspond to the RFC5321.

https://tools.ietf.org/html/rfc5321

For ordinary use, it can be assumed that the e-mail address is valid when code 250 is returned.

```
Array
(
    [0] => stdClass Object
        (
            [mail] => noreply@gmail.com
            [host] => Array
                (
                    [0] => gmail-smtp-in.l.google.com
                    [1] => alt1.gmail-smtp-in.l.google.com
                    [2] => alt2.gmail-smtp-in.l.google.com
                    [3] => alt3.gmail-smtp-in.l.google.com
                    [4] => alt4.gmail-smtp-in.l.google.com
                )

            [code] => 550
            [text] => Requested action not taken: mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)
        )

    [1] => stdClass Object
        (
            [mail] => noreply@hotmail.com
            [host] => Array
                (
                    [0] => hotmail-com.olc.protection.outlook.com
                )

            [code] => 250
            [text] => Requested mail action okay, completed
        )

    [2] => stdClass Object
        (
            [mail] => noreply@yahoo.com
            [host] => Array
                (
                    [0] => mta7.am0.yahoodns.net
                    [1] => mta6.am0.yahoodns.net
                    [2] => mta5.am0.yahoodns.net
                )

            [code] => 250
            [text] => Requested mail action okay, completed
        )

)

```

### Settings Functions

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
 * Timeout of each communication.
 * Note that some MX servers are configured to wait before responding or respond with multiple lines.
 * Perhaps you need to increase the stream timeout to get successful validation.
 */
$class->setStreamTimeOut("15");

/**
 * Pass the Boolean value TRUE to print the debug log.
 */
$class->validate(true);
```

### Contact Functions

```php
$class->setContact("noreply@gmail.com");

$class->unsetContact("noreply@gmail.com");

$class->getContacts()
```
