<?php

/**
 * The ExaBGP process plugin script to RTBH a local and the voipbl.org blacklist
 *
 * The ExaBGP process plugin script advertises the prefixes from a local and the
 * voipbl.org blacklist to ExaBGP via unicast or FlowSpec BGP.
 *
 * PHP version 7.0
 *
 * @category  GeertHauwaerts
 * @package   ExaBGP\VoIPBL
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */

use ExaBGP\VoIPBL\Loader;

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$cfg = 'voipbl.conf';

if (isset($argv[1])) {
    $cfg = $argv[1];
}

$exabgp = new Loader(__DIR__, $cfg);
$exabgp->start();
