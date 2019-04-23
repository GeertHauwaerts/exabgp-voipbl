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

use ExaBGP\VoIPBL\Validator;
use PHPUnit\Framework\TestCase;

/**
 * The ValidatorTest class.
 *
 * @package   PHPUnit\Framework\TestCase\ValidatorTest
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class ValidatorTest extends TestCase
{
    /**
     * Test the isIP() function.
     *
     * @return void
     */
    public function testValidatorIsIP()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isIP('10.0.0.0'));
        $this->assertTrue($validator->isIP('192.168.1.1'));

        $this->assertFalse($validator->isIP('10.0.0.0/32'));
        $this->assertFalse($validator->isIP('192.168.1.1/32'));

        $this->assertFalse($validator->isIP('string'));
        $this->assertFalse($validator->isIP(true));
    }

    /**
     * Test the isCIDR() function.
     *
     * @return void
     */
    public function testValidatorIsCIDR()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isCIDR('10.0.0.0/24'));
        $this->assertTrue($validator->isCIDR('192.168.1.1/32'));

        $this->assertFalse($validator->isCIDR('10.0.0.0'));
        $this->assertFalse($validator->isCIDR('192.168.1.1'));

        $this->assertFalse($validator->isCIDR('string'));
        $this->assertFalse($validator->isCIDR(true));
    }

    /**
     * Test the isPrivateIP() function.
     *
     * @return void
     */
    public function testValidatorIsPrivateIP()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isPrivateIP('10.0.0.0/24'));
        $this->assertTrue($validator->isPrivateIP('192.168.1.1/32'));

        $this->assertTrue($validator->isPrivateIP('10.0.0.0'));
        $this->assertTrue($validator->isPrivateIP('192.168.1.1'));

        $this->assertFalse($validator->isPrivateIP('8.8.8.8'));
        $this->assertFalse($validator->isPrivateIP('8.8.4.4'));

        $this->assertFalse($validator->isPrivateIP('8.8.8.0/24'));
        $this->assertFalse($validator->isPrivateIP('8.8.4.4/32'));

        $this->assertFalse($validator->isPrivateIP('string'));
        $this->assertFalse($validator->isPrivateIP(true));
    }

    /**
     * Test the isReservedIP() function.
     *
     * @return void
     */
    public function testValidatorIsReservedIP()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isReservedIP('0.0.23.0/24'));
        $this->assertTrue($validator->isReservedIP('242.123.135.161/32'));
        $this->assertTrue($validator->isReservedIP('255.255.255.0/24'));
        $this->assertTrue($validator->isReservedIP('255.255.255.255/32'));

        $this->assertTrue($validator->isReservedIP('0.0.23.0'));
        $this->assertTrue($validator->isReservedIP('242.123.135.161'));
        $this->assertTrue($validator->isReservedIP('255.255.255.0'));
        $this->assertTrue($validator->isReservedIP('255.255.255.255'));

        $this->assertFalse($validator->isReservedIP('8.8.8.8'));
        $this->assertFalse($validator->isReservedIP('8.8.4.4'));
        $this->assertFalse($validator->isReservedIP('10.0.0.0'));
        $this->assertFalse($validator->isReservedIP('192.168.1.1'));

        $this->assertFalse($validator->isReservedIP('8.8.8.0/24'));
        $this->assertFalse($validator->isReservedIP('8.8.4.4/32'));
        $this->assertFalse($validator->isReservedIP('10.0.0.0/24'));
        $this->assertFalse($validator->isReservedIP('192.168.1.1/32'));

        $this->assertFalse($validator->isReservedIP('string'));
        $this->assertFalse($validator->isReservedIP(true));
    }

    /**
     * Test the isNotPrivateIP() function.
     *
     * @return void
     */
    public function testValidatorIsNotPrivateIP()
    {
        $validator = new Validator();

        $this->assertFalse($validator->isNotPrivateIP('10.0.0.0/24'));
        $this->assertFalse($validator->isNotPrivateIP('192.168.1.1/32'));

        $this->assertFalse($validator->isNotPrivateIP('10.0.0.0'));
        $this->assertFalse($validator->isNotPrivateIP('192.168.1.1'));

        $this->assertTrue($validator->isNotPrivateIP('8.8.8.8'));
        $this->assertTrue($validator->isNotPrivateIP('8.8.4.4'));

        $this->assertTrue($validator->isNotPrivateIP('8.8.8.0/24'));
        $this->assertTrue($validator->isNotPrivateIP('8.8.4.4/32'));

        $this->assertTrue($validator->isNotPrivateIP('string'));
        $this->assertTrue($validator->isNotPrivateIP(true));
    }

    /**
     * Test the isNotReservedIP() function.
     *
     * @return void
     */
    public function testValidatorIsNotReservedIP()
    {
        $validator = new Validator();

        $this->assertFalse($validator->isNotReservedIP('0.0.23.0/24'));
        $this->assertFalse($validator->isNotReservedIP('242.123.135.161/32'));
        $this->assertFalse($validator->isNotReservedIP('255.255.255.0/24'));
        $this->assertFalse($validator->isNotReservedIP('255.255.255.255/32'));

        $this->assertFalse($validator->isNotReservedIP('0.0.23.0'));
        $this->assertFalse($validator->isNotReservedIP('242.123.135.161'));
        $this->assertFalse($validator->isNotReservedIP('255.255.255.0'));
        $this->assertFalse($validator->isNotReservedIP('255.255.255.255'));

        $this->assertTrue($validator->isNotReservedIP('8.8.8.8'));
        $this->assertTrue($validator->isNotReservedIP('8.8.4.4'));
        $this->assertTrue($validator->isNotReservedIP('10.0.0.0'));
        $this->assertTrue($validator->isNotReservedIP('192.168.1.1'));

        $this->assertTrue($validator->isNotReservedIP('8.8.8.0/24'));
        $this->assertTrue($validator->isNotReservedIP('8.8.4.4/32'));
        $this->assertTrue($validator->isNotReservedIP('10.0.0.0/24'));
        $this->assertTrue($validator->isNotReservedIP('192.168.1.1/32'));

        $this->assertTrue($validator->isNotReservedIP('string'));
        $this->assertTrue($validator->isNotReservedIP(true));
    }

    /**
     * Test the isURL() function.
     *
     * @return void
     */
    public function testValidatorIsURL()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isURL('https://www.google.com/'));
        $this->assertTrue($validator->isURL('http://www.example.com/'));

        $this->assertFalse($validator->isURL('https://www.google.com'));
        $this->assertFalse($validator->isURL('http://www.example.com'));

        $this->assertFalse($validator->isURL('string'));
        $this->assertFalse($validator->isURL(true));
    }

    /**
     * Test the isRegularExpression() function.
     *
     * @return void
     */
    public function testValidatorIsRegularExpression()
    {
        $validator = new Validator();

        $this->assertTrue($validator->isRegularExpression('/test/'));
        $this->assertTrue($validator->isRegularExpression('/\d+(.*)/'));

        $this->assertFalse($validator->isRegularExpression('/test'));
        $this->assertFalse($validator->isRegularExpression('test/'));

        $this->assertFalse($validator->isRegularExpression('string'));
        $this->assertFalse($validator->isRegularExpression(true));
    }

    /**
     * Test the makeCIDR() function.
     *
     * @return void
     */
    public function testValidatorMakeCIDR()
    {
        $validator = new Validator();

        $this->assertEquals($validator->makeCIDR('10.0.0.1'), '10.0.0.1/32');
        $this->assertEquals($validator->makeCIDR('10.0.0.1/32'), '10.0.0.1/32');

        $this->assertEquals($validator->makeCIDR('string'), 'string');
        $this->assertEquals($validator->makeCIDR(true), true);
    }
}
