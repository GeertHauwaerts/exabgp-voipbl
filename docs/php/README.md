# ExaBGP\VoIPBL Library Documentation

## Table of Contents

* [Controller](#controller)
    * [__construct](#__construct)
    * [init](#init)
    * [loadVersion](#loadversion)
    * [sendCommand](#sendcommand)
* [Loader](#loader)
    * [__construct](#__construct-1)
    * [start](#start)
* [Validator](#validator)
    * [isIP](#isip)
    * [isCIDR](#iscidr)
    * [isPrivateIP](#isprivateip)
    * [isReservedIP](#isreservedip)
    * [isNotPrivateIP](#isnotprivateip)
    * [isNotReservedIP](#isnotreservedip)
    * [isURL](#isurl)
    * [isRegularExpression](#isregularexpression)
    * [makeCIDR](#makecidr)
    * [ensureFileIsReadable](#ensurefileisreadable)
    * [ensureFileIsWritable](#ensurefileiswritable)
    * [ensureRequiredCfg](#ensurerequiredcfg)

## Controller

The Controller class.



* Full name: \ExaBGP\VoIPBL\Controller

**See Also:**

* https://github.com/GeertHauwaerts/exabgp-voipbl - exabgp-voipbl

### __construct

Create a new Controller instance.

```php
Controller::__construct(  ): void
```







---

### init

Initialize an ExaBGP connection.

```php
Controller::init(  ): void
```







---

### loadVersion

Load the ExaBGP version.

```php
Controller::loadVersion(  ): void
```







---

### sendCommand

Send an API command to ExaBGP.

```php
Controller::sendCommand( string $cmd, boolean $response = false ): boolean|string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cmd` | **string** | The command to execute. |
| `$response` | **boolean** | Indicate whether to return the API response. |




---

## Loader

The Loader class.



* Full name: \ExaBGP\VoIPBL\Loader

**See Also:**

* https://github.com/GeertHauwaerts/exabgp-voipbl - exabgp-voipbl

### __construct

Create a new Loader instance.

```php
Loader::__construct( string $path = __DIR__, string $cfg = 'voipbl.conf' ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **string** | The data path. |
| `$cfg` | **string** | The configuration file name. |




---

### start

Handover the process to ExaBGP.

```php
Loader::start(  ): void
```







---

## Validator

The Validator class.



* Full name: \ExaBGP\VoIPBL\Validator

**See Also:**

* https://github.com/GeertHauwaerts/exabgp-voipbl - exabgp-voipbl

### isIP

Validate an IP address.

```php
Validator::isIP( string $ip ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ip` | **string** | An IP address. |




---

### isCIDR

Validate an IP/CIDR address.

```php
Validator::isCIDR( string $ipcidr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ipcidr` | **string** | An IP/CIDR address. |




---

### isPrivateIP

Validate an RFC1918 IP or IP/CIDR address.

```php
Validator::isPrivateIP( string $ipcidr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ipcidr` | **string** | An IP or IP/CIDR address. |




---

### isReservedIP

Validate an RFC1700 IP or IP/CIDR address.

```php
Validator::isReservedIP( string $ipcidr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ipcidr` | **string** | An IP or IP/CIDR address. |




---

### isNotPrivateIP

Validate an RFC1918 IP or IP/CIDR address. (negates)

```php
Validator::isNotPrivateIP( string $ipcidr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ipcidr` | **string** | An IP or IP/CIDR address. |




---

### isNotReservedIP

Validate an RFC1700 IP or IP/CIDR address. (negates)

```php
Validator::isNotReservedIP( string $ipcidr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ipcidr` | **string** | An IP or IP/CIDR address. |




---

### isURL

Validate a URL.

```php
Validator::isURL( string $url ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | **string** | A URL. |




---

### isRegularExpression

Validate a regular expression.

```php
Validator::isRegularExpression( string $expr ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$expr` | **string** | A regular expression. |




---

### makeCIDR

Change an IP into an IP/CIDR.

```php
Validator::makeCIDR( string $ip ): boolean
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ip` | **string** | An IP address. |




---

### ensureFileIsReadable

Ensure the given file is readable.

```php
Validator::ensureFileIsReadable( string $file ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string** | The file to read. |




---

### ensureFileIsWritable

Ensure the given file is writable.

```php
Validator::ensureFileIsWritable( string $file ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string** | The file to write to. |




---

### ensureRequiredCfg

Ensure the required configuration parameters are present.

```php
Validator::ensureRequiredCfg( string $cfg ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cfg` | **string** | The configuration parameters. |




---



--------
> This document was automatically generated from source code comments on 2019-04-24 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
