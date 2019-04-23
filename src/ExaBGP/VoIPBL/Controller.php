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

use ExaBGP\VoIPBL\Exception\ErrorResponseException;
use ExaBGP\VoIPBL\Exception\InvalidResponseException;

/**
 * The Controller class.
 *
 * @package   ExaBGP\VoIPBL\Controller
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class Controller
{
    /**
     * Indicate whether the application is an ExaBGP process.
     *
     * @var bool
     */
    public $process = false;

    /**
     * The ExaBGP version.
     *
     * @var string
     */
    private $version;

    /**
     * Indicate whether to wait for ExaBGP acknowledgements.
     *
     * @var string
     */
    private $waitAcknowledgements = true;

    /**
     * Create a new Controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (function_exists('posix_ttyname') && !posix_ttyname(STDOUT)) {
            ignore_user_abort(true);
            set_time_limit(0);
            pcntl_signal(SIGINT, [$this, 'sigSIGINT']);

            $this->process = true;
        }
    }

    /**
     * Catch and process the SIGINT signal.
     *
     * @return bool
     */
    private function sigSIGINT()
    {
        return true;
    }

    /**
     * Initialize an ExaBGP connection.
     *
     * @return void
     */
    public function init()
    {
        if (!$this->process) {
            return;
        }

        $this->loadVersion();
    }

    /**
     * Load the ExaBGP version.
     *
     * @throws \VoIPBL\Exception\InvalidResponseException
     *
     * @return void
     */
    public function loadVersion()
    {
        $version = $this->sendCommand('version', true);

        if (!preg_match('/^exabgp (.*)$/', $version, $matches)) {
            throw new InvalidResponseException(
                sprintf(
                    'Invalid version identifier %s.',
                    $version
                )
            );
        }

        $this->version = $matches[1];

        if (is_numeric($this->version[0]) && ($this->version[0] < 4)) {
            $this->waitAcknowledgements = false;
        }
    }

    /**
     * Send an API command to ExaBGP.
     *
     * @param string $cmd      The command to execute.
     * @param bool   $response Indicate whether to return the API response.
     *
     * @throws \VoIPBL\Exception\ErrorResponseException
     * @throws \VoIPBL\Exception\InvalidResponseException
     *
     * @return bool|string
     */
    public function sendCommand($cmd, $response = false)
    {
        echo $cmd . "\n";

        if (!$this->process|| (!$response && !$this->waitAcknowledgements)) {
            return true;
        }

        $line = trim(fgets(STDIN));

        if (!empty($line) && (substr($line, 0, 1) === '{')) {
            $json = json_decode($line);

            if ($line && !$json) {
                throw new InvalidResponseException(
                    sprintf(
                        'Received invalid JSON response %s.',
                        $line
                    )
                );
            }

            $line = $json;
        }

        if ($response) {
            return $line;
        }

        if (is_object($line) && ($line->type === 'notification') && ($line->notification === 'shutdown')) {
            exit;
        }

        switch ($line) {
            case 'done':
                return true;
                break;

            case 'error':
                throw new ErrorResponseException(
                    sprintf('Received an API error.')
                );
                break;

            case 'shutdown':
                exit;
                break;

            default:
                throw new InvalidResponseException(
                    sprintf(
                        'Received invalid API response %s.',
                        $line
                    )
                );
                break;
        }
    }
}
