<?php

/**
 * The ExaBGP process plugin script to RTBH a local and the voipbl.org blacklist
 *
 * The ExaBGP process plugin script advertises the prefixes from a local and the
 * voipbl.org blacklist to ExaBGP via unicast or FlowSpec BGP.
 *
 * PHP version 7.0
 *
 * @package   ExaBGP\VoIPBL
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */

namespace ExaBGP\VoIPBL;

use ExaBGP\VoIPBL\Controller;
use PHPUnit\Framework\TestCase;

/**
 * The ControllerTest class.
 *
 * @package   PHPUnit\Framework\TestCase\ControllerTest
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class ControllerTest extends TestCase
{
    /**
     * Test the sendCommand() function.
     *
     * @return void
     */
    public function testControllerSendCommand()
    {
        $this->expectOutputString("test\n");

        $controller = new Controller();

        $this->assertTrue($controller->sendCommand('test'));
    }
}
