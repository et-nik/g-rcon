GRcon is a PHP library for manage game servers and services using RCON protocol.

## Supported protocols

* Source (CS Global Offensive, Team Fortress 2, Black Mesa)
* GoldSource (CS 1.6, Half-Life game servers, etc.)
* Minecraft
* TeamSpeak 3
* SAMP (GTA San-Andreas Multiplayer)
* Rust

### Comming soon

* Arma

## Installation

```bash
composer require knik/g-rcon --no-dev
```

## Examples

### EasyGRcon

EasyGRcon is more simpler then GRcon

```php
include "../vendor/autoload.php";

use Knik\GRcon\EasyGRcon;

$rcon = new EasyGRcon('source', [
    'host' => '127.0.0.1',
    'port' => 27015,
    'password' => 'rC0nPaS$word'
]);

$rcon->execute('changelevel de_dust2');
$rcon->execute('kick player');
```

### GRcon

GRcon is more flexible and configurable

```php
use Knik\GRcon\GRcon;
use Knik\GRcon\Protocols\SourceAdapter;

$adapter = new SourceAdapter([
    'host' => '127.0.0.1',
    'port' => 27015,
    'password' => 'rC0nPaS$word',
]);

$rcon = new GRcon($adapter);

$rcon->execute('changelevel de_dust2');
$rcon->execute('kick player');
```

### Players manage

```php
$rcon->kick('playername');
$rcon->ban('player');
```

### Chat

```php
$rcon->sendMessage('Hello players!');
```
