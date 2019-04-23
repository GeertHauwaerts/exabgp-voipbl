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
use ExaBGP\VoIPBL\Exception\InvalidBlacklistException;
use ExaBGP\VoIPBL\Exception\InvalidPathException;
use ExaBGP\VoIPBL\Exception\ValidationException;

/**
 * The Loader class.
 *
 * @package   ExaBGP\VoIPBL\Loader
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class Loader
{
    /**
     * The Validator instance.
     *
     * @var \VoIPBL\Validator
     */
    private $validator;

    /**
     * The Controller instance.
     *
     * @var \VoIPBL\Controller
     */
    private $controller;

    /**
     * The data path.
     *
     * @var string
     */
    private $dataPath;

    /**
     * The configuration file name.
     *
     * @var string
     */
    private $cfgFile;

    /**
     * The voipbl.org blacklist data.
     *
     * @var array
     */
    private $voipbl = [];


    /**
     * The local blacklist data.
     *
     * @var array
     */
    private $localbl = [
        'data' => [],
    ];

    /**
     * The aggregated blacklist data.
     *
     * @var array
     */
    private $aggrbl = [];

    /**
     * The configuration data.
     *
     * @var array
     */
    private $cfg = [];

    /**
     * The ExaBGP data.
     *
     * @var array
     */
    private $exabgp = [];

    /**
     * Create a new Loader instance.
     *
     * @param string $path The data path.
     * @param string $cfg  The configuration file name.
     *
     * @return void
     */
    public function __construct($path = __DIR__, $cfg = 'voipbl.conf')
    {
        $this->dataPath = realpath($path) . DIRECTORY_SEPARATOR;
        $this->cfgFile = $cfg;

        $this->validator = new Validator();
        $this->controller = new Controller($this);

        $this->loadConfiguration();
    }

    /**
     * Load the configuration.
     *
     * @throws \VoIPBL\Exception\InvalidPathException
     * @throws \VoIPBL\Exception\ValidationException
     *
     * @return void
     */
    private function loadConfiguration()
    {
        $configfile = $this->dataPath . $this->cfgFile;

        $this->validator->ensureFileIsReadable($configfile);
        $this->cfg = @parse_ini_file($configfile, true, INI_SCANNER_TYPED);

        if (!is_array($this->cfg) || empty($this->cfg)) {
            throw new InvalidPathException(
                sprintf(
                    'Unable to parse the file at %s.',
                    $configfile
                )
            );
        }

        $this->validator->ensureRequiredCfg($this->cfg);

        $this->cfg['voipbl']['database'] = $this->dataPath . $this->cfg['voipbl']['database'];
        $this->cfg['localbl']['database'] = $this->dataPath . $this->cfg['localbl']['database'];

        $this->validator->ensureFileIsWritable(dirname($this->cfg['voipbl']['database']));

        if ($this->cfg['exabgp']['method'] === 'unicast') {
            if (!$this->validator->isIP($this->cfg['exabgp']['uc_next_hop'])) {
                throw new ValidationException(
                    sprintf('Invalid value for exabgp::uc_next_hop, expected an IP address.')
                );
            }
        }

        if ($this->cfg['exabgp']['method'] === 'flowspec') {
            if (!empty($this->cfg['exabgp']['fs_dst_port'])) {
                $regex = '/^[0-9=<>& ]+$/';

                if (!preg_match($regex, $this->cfg['exabgp']['fs_dst_port'])) {
                    throw new ValidationException(
                        sprintf('Invalid value for exabgp::fs_dst_port, expected ' . $regex . '.')
                    );
                }

                $this->cfg['exabgp']['fs_dst_port'] = 'destination-port ' . $this->cfg['exabgp']['fs_dst_port'] . '; ';
            } else {
                $this->cfg['exabgp']['fs_dst_port'] = '';
            }

            if (!in_array($this->cfg['exabgp']['fs_protocol'], ['tcp', 'udp', 'any'])) {
                throw new ValidationException(
                    sprintf('Invalid value for exabgp::fs_protocol, expected /(tcp|udp|any)/.')
                );
            } else {
                if ($this->cfg['exabgp']['fs_protocol'] === 'any') {
                    $this->cfg['exabgp']['fs_protocol'] = '';
                } else {
                    $this->cfg['exabgp']['fs_protocol'] = 'protocol ' . $this->cfg['exabgp']['fs_protocol'] . '; ';
                }
            }
        }
    }

    /**
     * Handover the process to ExaBGP.
     *
     * @return void
     */
    public function start()
    {
        $this->controller->init();

        while (1) {
            $this->updateBlacklists();
            $this->exabgpBlacklists();

            if (!$this->controller->process) {
                exit;
            }

            sleep(10);
        }
    }


    /**
     * Update the blacklists.
     *
     * @return void
     */
    private function updateBlacklists()
    {
        $voipbl = $this->updateBlacklistVoIPBL();
        $localbl = $this->updateBlacklistLocal();

        if ($voipbl || $localbl) {
            $this->aggrbl = array_merge($this->voipbl['data'], $this->localbl['data']);

            sort($this->aggrbl, SORT_NUMERIC);
            array_unique($this->aggrbl);
        }

        clearstatcache();
    }

    /**
     * Update the voipbl.org blacklist.
     *
     * @throws \VoIPBL\Exception\InvalidBlacklistException
     *
     * @return bool
     */
    private function updateBlacklistVoIPBL()
    {
        if (isset($this->voipbl['updated']) &&
            ($this->voipbl['updated'] > (time() - $this->cfg['voipbl']['frequency']))
        ) {
            return false;
        }

        if (!file_exists($this->cfg['voipbl']['database']) ||
            (filemtime($this->cfg['voipbl']['database']) < (time() - $this->cfg['voipbl']['frequency']))
        ) {
            $raw_data = @file_get_contents($this->cfg['voipbl']['remote']);

            if (empty($raw_data)) {
                throw new InvalidBlacklistException(
                    sprintf(
                        'Unable to download the blacklist from %s.',
                        $this->cfg['voipbl']['remote']
                    )
                );
            }

            file_put_contents($this->cfg['voipbl']['database'], $raw_data);
        }

        $this->voipbl['data'] = @file($this->cfg['voipbl']['database']);

        if (!is_array($this->voipbl['data'])) {
            throw new InvalidBlacklistException(
                sprintf(
                    'Unable to read the blacklist entries from %s.',
                    $this->cfg['voipbl']['database']
                )
            );
        }

        $this->voipbl['data'] = array_map('chop', $this->voipbl['data']);
        $this->voipbl['data'] = array_filter($this->voipbl['data'], [$this->validator, 'isCIDR']);
        $this->voipbl['data'] = array_filter($this->voipbl['data'], [$this->validator, 'isNotReservedIP']);

        if ($this->cfg['voipbl']['filter_rfc1918']) {
            $this->voipbl['data'] = array_filter($this->voipbl['data'], [$this->validator, 'isNotPrivateIP']);
        }

        sort($this->voipbl['data'], SORT_NUMERIC);
        array_unique($this->voipbl['data']);

        $this->voipbl['updated'] = time();
        return true;
    }

    /**
     * Update the local blacklist.
     *
     * @throws \VoIPBL\Exception\InvalidBlacklistException
     *
     * @return bool
     */
    private function updateBlacklistLocal()
    {
        if (!file_exists($this->cfg['localbl']['database']) ||
            (
                isset($this->localbl['updated']) &&
                ($this->localbl['updated'] > (time() - $this->cfg['localbl']['frequency']))
            )
        ) {
            return false;
        }

        if (!isset($this->localbl['updated']) ||
            (filemtime($this->cfg['localbl']['database']) > $this->localbl['updated'])
        ) {
            $this->localbl['data'] = @file($this->cfg['localbl']['database']);

            if (!is_array($this->localbl['data'])) {
                throw new InvalidBlacklistException(
                    sprintf(
                        'Unable to read the blacklist entries from %s.',
                        $this->cfg['localbl']['database']
                    )
                );
            }

            $this->localbl['data'] = array_map('chop', $this->localbl['data']);
            $this->localbl['data'] = array_map([$this->validator, 'makeCIDR'], $this->localbl['data']);

            $this->localbl['data'] = array_filter($this->localbl['data'], [$this->validator, 'isCIDR']);
            $this->localbl['data'] = array_filter($this->localbl['data'], [$this->validator, 'isNotReservedIP']);

            if ($this->cfg['localbl']['filter_rfc1918']) {
                $this->localbl['data'] = array_filter($this->localbl['data'], [$this->validator, 'isNotPrivateIP']);
            }

            sort($this->localbl['data'], SORT_NUMERIC);
            array_unique($this->localbl['data']);
        }

        $this->localbl['updated'] = time();
        return true;
    }

    /**
     * Transmit advertisements and withdrawals to ExaBGP.
     *
     * @return void
     */
    private function exabgpBlacklists()
    {
        $advertise = array_diff($this->aggrbl, $this->exabgp);
        $withdraw = array_diff($this->exabgp, $this->aggrbl);

        $cmd = '##TYPE## route ##PREFIX## next-hop ' . $this->cfg['exabgp']['uc_next_hop'] .' community [';
        $cmd .= $this->cfg['exabgp']['communities'] . ']';

        if ($this->cfg['exabgp']['method'] === 'flowspec') {
            $cmd = '##TYPE## flow route { match { source ##PREFIX##; ' . $this->cfg['exabgp']['fs_dst_port'];
            $cmd .= $this->cfg['exabgp']['fs_protocol'] . '} then { community [';
            $cmd .= $this->cfg['exabgp']['communities'] . ']; discard; } }';
        }

        foreach ($advertise as $prefix) {
            $this->exabgp[] = $prefix;

            $tmpCmd = $cmd;
            $tmpCmd = str_replace('##TYPE##', 'announce', $tmpCmd);
            $tmpCmd = str_replace('##PREFIX##', $prefix, $tmpCmd);

            $this->controller->sendCommand($tmpCmd);
        }

        foreach ($withdraw as $prefix) {
            $this->exabgp = array_diff($this->exabgp, [$prefix]);

            $tmpCmd = $cmd;
            $tmpCmd = str_replace('##TYPE##', 'withdraw', $tmpCmd);
            $tmpCmd = str_replace('##PREFIX##', $prefix, $tmpCmd);

            $this->controller->sendCommand($tmpCmd);
        }
    }
}
