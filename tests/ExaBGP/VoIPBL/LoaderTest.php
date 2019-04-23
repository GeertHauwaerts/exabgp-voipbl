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

use ExaBGP\VoIPBL\Loader;
use PHPUnit\Framework\TestCase;

/**
 * The LoaderTest class.
 *
 * @package   PHPUnit\Framework\TestCase\LoaderTest
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class LoaderTest extends TestCase
{
    /**
     * The location of the test config files.
     *
     * @var string
     */
    private $fixturesFolder;

    /**
     * Setup the PHPUnit framework.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fixturesFolder = dirname(__DIR__) . '/../fixtures';
    }

    /**
     * Test if InvalidPathException is thrown.
     *
     * @return void
     */
    public function testLoaderThrowsExceptionIfUnableToReadFile()
    {
        $this->expectException('ExaBGP\VoIPBL\Exception\InvalidPathException');
        $this->expectExceptionMessage('Unable to read the file at');

        $exabgp = new Loader(__DIR__);
        $exabgp->start();
    }

    /**
     * Test if InvalidPathException is thrown.
     *
     * @depends testLoaderThrowsExceptionIfUnableToReadFile
     *
     * @return void
     */
    public function testLoaderThrowsExceptionIfUnableToParseFile()
    {
        $this->expectException('ExaBGP\VoIPBL\Exception\InvalidPathException');
        $this->expectExceptionMessage('Unable to parse the file at');

        $exabgp = new Loader($this->fixturesFolder, 'voipbl-invalid.conf');
        $exabgp->start();
    }

    /**
     * Test if ValidationException is thrown.
     *
     * @depends testLoaderThrowsExceptionIfUnableToReadFile
     * @depends testLoaderThrowsExceptionIfUnableToParseFile
     *
     * @return void
     */
    public function testLoaderThrowsExceptionIfMissingConfigurationSection()
    {
        $this->expectException('ExaBGP\VoIPBL\Exception\ValidationException');
        $this->expectExceptionMessage('Missing the voipbl configuration section.');

        $exabgp = new Loader($this->fixturesFolder, 'voipbl-missing-section.conf');
        $exabgp->start();
    }

    /**
     * Test if ValidationException is thrown.
     *
     * @depends testLoaderThrowsExceptionIfUnableToReadFile
     * @depends testLoaderThrowsExceptionIfUnableToParseFile
     *
     * @return void
     */
    public function testLoaderThrowsExceptionIfMissingConfigurationSetting()
    {
        $this->expectException('ExaBGP\VoIPBL\Exception\ValidationException');
        $this->expectExceptionMessage('Missing the voipbl::remote setting.');

        $exabgp = new Loader($this->fixturesFolder, 'voipbl-missing-setting.conf');
        $exabgp->start();
    }

    /**
     * Test if ValidationException is thrown.
     *
     * @depends testLoaderThrowsExceptionIfUnableToReadFile
     * @depends testLoaderThrowsExceptionIfUnableToParseFile
     *
     * @return void
     */
    public function testLoaderThrowsExceptionIfInvalidConfigurationSetting()
    {
        $this->expectException('ExaBGP\VoIPBL\Exception\ValidationException');
        $this->expectExceptionMessage('Invalid value for voipbl::remote, expected a URL.');

        $exabgp = new Loader($this->fixturesFolder, 'voipbl-invalid-value.conf');
        $exabgp->start();
    }
}
