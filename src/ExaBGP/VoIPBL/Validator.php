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

use ExaBGP\VoIPBL\Exception\InvalidPathException;
use ExaBGP\VoIPBL\Exception\ValidationException;

/**
 * The Validator class.
 *
 * @package   ExaBGP\VoIPBL\Validator
 * @author    Geert Hauwaerts <geert@hauwaerts.be>
 * @copyright 2014 Geert Hauwaerts
 * @license   BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 */
class Validator
{
    /**
     * Validate an IP address.
     *
     * @param string $ip An IP address.
     *
     * @return bool
     */
    public function isIP($ip)
    {
        $pattern = '/^\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)';
        $pattern .= '\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b$/';

        if (preg_match($pattern, $ip)) {
            return true;
        }

        return false;
    }

    /**
     * Validate an IP/CIDR address.
     *
     * @param string $ipcidr An IP/CIDR address.
     *
     * @return bool
     */
    public function isCIDR($ipcidr)
    {
        if (!preg_match('#^[0-9\.]+/\d+$#', $ipcidr)) {
            return false;
        } else {
            $ip = @explode('/', $ipcidr, 2)[0];
            $cidr = @explode('/', $ipcidr, 2)[1];
        }

        if (!$this->isIP($ip)) {
            return false;
        }

        if (($cidr <= 0) || ($cidr > 32)) {
            return false;
        }

        return true;
    }

    /**
     * Validate an RFC1918 IP or IP/CIDR address.
     *
     * @param string $ipcidr An IP or IP/CIDR address.
     *
     * @return bool
     */
    public function isPrivateIP($ipcidr)
    {
        $pattern = '/(^127\.)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(^192\.168\.)/';

        if (preg_match($pattern, $ipcidr)) {
            return true;
        }

        return false;
    }

    /**
     * Validate an RFC1700 IP or IP/CIDR address.
     *
     * @param string $ipcidr An IP or IP/CIDR address.
     *
     * @return bool
     */
    public function isReservedIP($ipcidr)
    {
        $pattern = '/(^0\.)|(^22[4-9]\.)|(^23[0-9]\.)|(^24[0-9]\.)|(^25[0-5]\.)/';

        if (preg_match($pattern, $ipcidr)) {
            return true;
        }

        return false;
    }

    /**
     * Validate an RFC1918 IP or IP/CIDR address. (negates)
     *
     * @param string $ipcidr An IP or IP/CIDR address.
     *
     * @return bool
     */
    public function isNotPrivateIP($ipcidr)
    {
        return !$this->isPrivateIP($ipcidr);
    }

    /**
     * Validate an RFC1700 IP or IP/CIDR address. (negates)
     *
     * @param string $ipcidr An IP or IP/CIDR address.
     *
     * @return bool
     */
    public function isNotReservedIP($ipcidr)
    {
        return !$this->isReservedIP($ipcidr);
    }

    /**
     * Validate a URL.
     *
     * @param string $url A URL.
     *
     * @return bool
     */
    public function isURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate a regular expression.
     *
     * @param string $expr A regular expression.
     *
     * @return bool
     */
    public function isRegularExpression($expr)
    {
        return @preg_match($expr, '') !== false;
    }

    /**
     * Change an IP into an IP/CIDR.
     *
     * @param string $ip An IP address.
     *
     * @return bool
     */
    public function makeCIDR($ip)
    {
        if ($this->isIP($ip)) {
            return $ip . '/32';
        }

        return $ip;
    }

    /**
     * Ensure the given file is readable.
     *
     * @param string $file The file to read.
     *
     * @throws \VoIPBL\Exception\InvalidPathException
     *
     * @return void
     */
    public function ensureFileIsReadable($file)
    {
        if (!is_readable($file)) {
            throw new InvalidPathException(
                sprintf(
                    'Unable to read the file at %s.',
                    $file
                )
            );
        }
    }

    /**
     * Ensure the given file is writable.
     *
     * @param string $file The file to write to.
     *
     * @throws \VoIPBL\Exception\InvalidPathException
     *
     * @return void
     */
    public function ensureFileIsWritable($file)
    {
        if (!is_writable($file)) {
            throw new InvalidPathException(
                sprintf(
                    'Unable to write to the file at %s.',
                    $file
                )
            );
        }
    }

    /**
     * Ensure the required configuration parameters are present.
     *
     * @param string $cfg The configuration parameters.
     *
     * @throws \VoIPBL\Exception\ValidationException
     *
     * @return void
     */
    public function ensureRequiredCfg($cfg)
    {
        $required = [
            'voipbl' => [
                'remote' => 'isURL',
                'database' => 'is_string',
                'frequency' => 'is_numeric',
                'filter_rfc1918' => 'is_bool',
            ],
            'exabgp' => [
                'method' => '/^(unicast|flowspec)$/',
                'communities' => '/^[(\d+:\d+) ]+$/',
            ],
            'localbl' => [
                'database' => 'is_string',
                'frequency' => 'is_numeric',
                'filter_rfc1918' => 'is_bool',
            ],
        ];

        foreach ($required as $section => $settings) {
            if (empty($cfg[$section]) || !is_array($cfg[$section])) {
                throw new ValidationException(
                    sprintf(
                        'Missing the %s configuration section.',
                        $section
                    )
                );
            } else {
                foreach ($settings as $setting => $type) {
                    if (!isset($cfg[$section][$setting])) {
                        throw new ValidationException(
                            sprintf(
                                'Missing the %s::%s setting.',
                                $section,
                                $setting,
                                $type
                            )
                        );
                    }

                    if (method_exists($this, $type)) {
                        if (!$this->$type($cfg[$section][$setting])) {
                            throw new ValidationException(
                                sprintf(
                                    'Invalid value for %s::%s, expected a ' . str_replace('is', '', $type) . '.',
                                    $section,
                                    $setting
                                )
                            );
                        }
                    }

                    if (function_exists($type)) {
                        if (!$type($cfg[$section][$setting])) {
                            throw new ValidationException(
                                sprintf(
                                    'Invalid value for %s::%s, expected a ' . str_replace('is_', '', $type) . '.',
                                    $section,
                                    $setting
                                )
                            );
                        }
                    }

                    if ($this->isRegularExpression($type)) {
                        if (!preg_match($type, $cfg[$section][$setting])) {
                            throw new ValidationException(
                                sprintf(
                                    'Invalid value for %s::%s, expected %s.',
                                    $section,
                                    $setting,
                                    $type
                                )
                            );
                        }
                    }
                }
            }
        }
    }
}
