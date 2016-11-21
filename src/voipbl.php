#!/usr/bin/env php
<?php

/**
 * The ExaBGP process plugin script to RTBH a local and the voipbl.org blacklist
 *
 * The ExaBGP process plugin script advertises the prefixes from a local and the
 * voipbl.org blacklist to ExaBGP via unicast or FlowSpec BGP.
 * 
 * @author       Geert Hauwaerts <geert@hauwaerts.be>
 * 
 * @version      1.0
 * @package      exabgp_voipbl
 * @link         https://github.com/GeertHauwaerts/exabgp-voipbl exabgp-voipbl
 *
 * @copyright    Copyright (c) 2014, Geert Hauwaerts
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License, version 3
 */

class exabgp_voipbl {
	
	
	/**
	 * The voipbl.org blacklist data
	 * 
	 * @var    array
	 */
	
	private $voipbl = [];
	
	
	/**
	 * The local blacklist data
	 *
	 * @var    array
	 */
	
	private $localbl = [];
	
	
	/**
	 * The aggregated blacklist data
	 *
	 *
	 * @var    array
	 */
	
	private $aggrbl = [];
	
	
	/**
	 * The configuration data
	 *
	 * @var    array
	 */
	
	private $configuration = [];
	
	
	/**
	 * The ExaBGP data
	 *
	 * @var    array
	 */
	
	private $exabgp = [];
	
	
	/**
	 * The absolute path to the application directory
	 *
	 * @var    string
	 */
	
	private $basedir;
	
	
	/**
	 * Indicates whether the application is running in dry-run mode
	 *
	 * @var    bool
	 */
	
	private $dryrun;
	
	
	/**
	 * Initializes this class
	 * 
	 * @return    void
	 */
	
	public function __construct() {
		
		
		/*
		 * Make the application parameters available to this class.
		 */
		
		global $argv;
		
		
		/*
		 * Convert the application parameters to lowercase.
		 */
		
		$argv = array_map("strtolower", $argv);
		
		
		/*
		 * Check if the application is in dry-run mode.
		 */
		
		if (in_array("--dry-run", $argv)) {
			
			
			/*
			 * Instruct the application to run in dry-run mode.
			 */
			
			$this->dryrun = TRUE;
		} else {
			
			
			/*
			 * Instruct the application to run in normal mode.
			 */
			
			$this->dryrun = FALSE;
		}
		
		
		/*
		 * Check if the application is running in dry-run mode.
		 */
		
		if (!$this->dryrun) {
			
			
			/*
			 * Ignore user aborts and allow the application to run indefinitely.
			 */
			
			ignore_user_abort(TRUE);
			set_time_limit(0);
			
			
			/*
			 * Catch and redirect the SIGINT signal.
			 */
			
			pcntl_signal(SIGINT, [$this, "sig_sigint"]);
		}
		
		
		/*
		 * Store the absolute path to the application directory.
		 */
		
		$this->basedir = dirname(realpath( __FILE__ )) . DIRECTORY_SEPARATOR;
		
		
		/*
		 * Load the configuration.
		 */
		
		$this->load_configuration();
	}
	
	
	/**
	 * Raises a critical error
	 *
	 * @param     string    $message    the error message.
	 * @return    void
	 */
	
	private function error($message) {
		
		
		/*
		 * Write the message to STDERR.
		 */
		
		fwrite(STDERR, "Error: " . $message . "\n");
		
		
		/*
		 * Exit unsuccessfully.
		 */
		
		exit(1);
	}
	
	
	/**
	 * Catches and processes the SIGINT signal
	 * 
	 * @return    bool    always returns TRUE.
	 */
	
	private function sig_sigint() {
		
		
		/*
		 * The SIGINT signal may not be used to terminate the application.
		 *
		 * ExaBGP will send the SIGTERM signal if the application must
		 * be terminated.
		 */
		
		return TRUE;
	}
	
	
	/**
	 * Loads the configuration
	 *
	 * @return    bool    returns TRUE on success or FALSE on failure.
	 */
	
	private function load_configuration() {
		
		
		/*
		 * Define the location of the configuration file.
		 */
		
		$configfile = $this->basedir . "voipbl.conf";
		
		
		/*
		 * Define the required configuration settings.
		 */
		
		$required = [
			"voipbl"  => ["remote", "database", "frequency"],
			"localbl" => ["database", "frequency"],
			"exabgp"  => ["method", "communities"],
		];
		
		
		/*
		 * Read the configuration file.
		 */
		
		$this->configuration = @parse_ini_file($configfile, TRUE);
		
		
		/*
		 * Validate the configuration.
		 */
		
		if (!is_array($this->configuration)) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("Unable to parse the configuration file '" . $configfile ."'.");
		} else {
			
			
			/*
			 * Loop through the required configuration sections.
			 */
			
			foreach ($required as $section => $settings) {
				
				
				/*
				 * Check if the required section is present.
				 */
				
				if (empty($this->configuration[$section]) || !is_array($this->configuration[$section])) {
					
					
					/*
					 * Raise a critical error.
					 */
					
					$this->error("Unable to parse the '" . $section . "' section in the configuration file.");
				} else {
					
					
					/*
					 * Validate the configuration settings for this section.
					 */
					
					foreach ($settings as $setting) {
						
						
						/*
						 * Check if the setting exists.
						 */
						
						if (empty($this->configuration[$section][$setting]) || !is_string($this->configuration[$section][$setting])) {
							
							
							/*
							 * Raise a critical error.
							 */
							
							$this->error("The setting '" . $section . "::" . $setting . "' is missing or invalid.");
						}
					}
				}
			}
		}
		
		
		/*
		 * Update the absolute path of the voipbl database.
		 */
		
		$this->configuration["voipbl"]["database"] = $this->basedir . $this->configuration["voipbl"]["database"];
		
		
		/*
		 * Update the absolute path of the localbl database.
		 */
		
		$this->configuration["localbl"]["database"] = $this->basedir . $this->configuration["localbl"]["database"];
		
		
		/*
		 * Validate the voipbl::remote setting.
		 */
		
		if (!$this->is_valid_url($this->configuration["voipbl"]["remote"])) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("The setting 'voipbl::remote' is invalid.");
		}
		
		
		/*
		 * Validate the voipbl::frequency setting.
		 */
		
		if (!is_numeric($this->configuration["voipbl"]["frequency"])) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("The setting 'voipbl::frequency' is invalid.");
		}
		
		
		/*
		 * Validate the localbl::frequency setting.
		 */
		
		if (!is_numeric($this->configuration["localbl"]["frequency"])) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("The setting 'voipbl::frequency' is invalid.");
		}
		
		
		/*
		 * Validate the exabgp::method setting.
		 */
		
		if (!in_array($this->configuration["exabgp"]["method"], ["unicast", "flowspec"])) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("The setting 'exabgp::method' is invalid.");
		}
		
		
		/*
		 * Validations for the unicast method.
		 */
		
		if ($this->configuration["exabgp"]["method"] == "unicast") {
			
			
			/*
			 * Validate the exabgp::uc_next_hop setting.
			 */
			
			if (!$this->is_valid_ip($this->configuration["exabgp"]["uc_next_hop"])) {
				
				
				/*
				 * Raise a critical error.
				 */
				
				$this->error("The setting 'exabgp::uc_next_hop' is invalid.");
			}
		}
		
		
		/*
		 * Validations for the FlowSpec method.
		 */
		
		if ($this->configuration["exabgp"]["method"] == "flowspec") {
			
			
			/*
			 * Validate the exabgp::fs_dst_port setting.
			 */
			
			if (!empty($this->configuration["exabgp"]["fs_dst_port"])) {
				
				
				/*
				 * Check if the ports are valid.
				 */
				
				if (!preg_match("/^[0-9=<>& ]+$/", $this->configuration["exabgp"]["fs_dst_port"])) {
					
					
					/*
					 * Raise a critical error.
					 */
					
					$this->error("The setting 'exabgp::fs_dst_port' is invalid.");
				}
				
				
				/*
				 * Match the specified ports.
				 */
				
				$this->configuration["exabgp"]["fs_dst_port"] = "destination-port " . $this->configuration["exabgp"]["fs_dst_port"] . ";\\n ";
			} else {
				
				
				/*
				 * Match all of the ports.
				 */
				
				$this->configuration["exabgp"]["fs_dst_port"] = "";
			}
			
			
			/*
			 * Validate the exabgp::fs_protocol setting.
			 */
			
			if (!in_array($this->configuration["exabgp"]["fs_protocol"], ["tcp", "udp", "any"])) {
				
				
				/*
				 * Raise a critical error.
				 */
				
				$this->error("The setting 'exabgp::fs_protocol' is invalid.");
			} else {
				
				
				/*
				 * Check if all protocols must be matched.
				 */
				
				if ($this->configuration["exabgp"]["fs_protocol"] == "any") {
					
					
					/*
					 * Match all of the protocols.
					 */
					
					$this->configuration["exabgp"]["fs_protocol"] = "";
				} else {
					
					
					/*
					 * Match the specified protocols.
					 */
					
					$this->configuration["exabgp"]["fs_protocol"] = "protocol [" . $this->configuration["exabgp"]["fs_protocol"] . "];\\n ";
				}
			}
		}
		
		
		/*
		 * Validate the exabgp::communities setting.
		 */
		
		if (!preg_match("/^[(\d+:\d+) ]+$/", $this->configuration["exabgp"]["communities"])) {
			
			
			/*
			 * Raise a critical error.
			 */
			
			$this->error("The setting 'exabgp::communities' is invalid.");
		}
		
		
		/*
		 * Successfully loaded the configuration file.
		 */
		
		return TRUE;
	}
	
	
	/**
	 * Validates an IP address
	 *
	 * @param     string    $ip    a reference to an IP address.
	 * @return    bool             returns TRUE on success or FALSE on failure.
	 */
	
	private function is_valid_ip(&$ip) {
		
		
		/*
		 * Check if the given IP address is valid.
		 */
		
		if (preg_match("/\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/", $ip)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	/**
	 * Validates an IP/CIDR address
	 *
	 * @param     string    $cidr    a reference to an IP/CIDR address.
	 * @return    bool               returns TRUE on success or FALSE on failure.
	 */
	
	private function is_valid_cidr(&$ipcidr) {
		
		
		/*
		 * Check the syntax format of the IP/CIDR.
		 */
		
		if (!preg_match("#^[0-9\.]+/\d+$#", $ipcidr)) {
			/*
			 * The given IP/CIDR does not match the required syntax.
			 */
			$ipcidr = $ipcidr . "/32";
			if (!preg_match("#^[0-9\.]+/\d+$#", $ipcidr)) {
				return FALSE;
			} else {
				$ip   = @explode("/", $ipcidr, 2)[0];
				$cidr = @explode("/", $ipcidr, 2)[1];
			}
		} else {
			/*
			 * Retrieve the IP and CIDR parameters from the IP/CIDR.
			 */
			
			$ip   = @explode("/", $ipcidr, 2)[0];
			$cidr = @explode("/", $ipcidr, 2)[1];
		}
		
		
		/*
		 * Check if the retrieved IP address is correct.
		 */
		
		if (!$this->is_valid_ip($ip)) {
			
			
			/*
			 * The given IP portion of the IP/CIDR is invalid.
			 */
			
			return FALSE;
		}
		
		
		/*
		 * Check if the CIDR is valid.
		 */
		
		if (($cidr <= 0) || ($cidr > 32)) {
			
			
			/*
			 * The given CIDR portion of the IP/CIDR is invalid.
			 */
			
			return FALSE;
		}
		
		
		/*
		 * The validation has succeeded.
		 */
		
		return TRUE;
	}
	
	
	/**
	 * Validates an URL
	 *
	 * @param     string    $url    a reference to a URL.
	 * @return    bool              returns TRUE on success or FALSE on failure.
	 */
	
	private function is_valid_url(&$url) {
		
		
		/*
		 * Check if the given URL is valid.
		 */
		
		if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	
	/**
	 * Processes the messages sent to ExaBGP
	 *
	 * This function performs an infinite while loop and continiously
	 * communicates with ExaBGP to distribute information.
	 *
	 * @return    void
	 */
	
	public function start() {
		
		
		/*
		 * Sleep for 10 seconds to allow ExaBGP to propagate the BGP session.
		 */
		
		sleep(10);
		
		
		/*
		 * Perform an infinite loop to communicate with ExaBGP.
		 */
		
		while (1) {
			
			
			/*
			 * Process the blacklists.
			 */
			
			$this->update_blacklists();
			
			
			/*
			 * Process the ExaBGP advertisements.
			 */
			
			$this->exabgp_blacklists();
			
			
			/*
			 * Check if the application is running in dry-run mode.
			 */
			
			if ($this->dryrun) {
				
				
				/*
				 * Terminate the application.
				 */
				
				exit;
			}
			
			
			/*
			 * Sleep 10 seconds before the next itteration.
			 */
			
			sleep(10);
		}
	}
	
	
	/**
	 * Updates the blacklists
	 *
	 * @return    void
	 */
	
	private function update_blacklists() {
		
		
		/*
		 * Variable to indicate that the aggregate blacklist must be updated.
		 */
		
		$update_aggrbl = 0;
		
		
		/*
		 * Check if the voipbl blacklist needs to be updated.
		 */
		
		if (!isset($this->voipbl["updated"]) || ($this->voipbl["updated"] < (time() - $this->configuration["voipbl"]["frequency"]))) {
			
			
			/*
			 * Check if the remote voipbl blacklist must be downloaded.
			 */
			
			if (!file_exists($this->configuration["voipbl"]["database"]) || (filemtime($this->configuration["voipbl"]["database"]) < (time() - $this->configuration["voipbl"]["frequency"]))) {
				
				
				/*
				 * Download the remote voipbl blacklist.
				 */
				
				$raw_data = @file_get_contents($this->configuration["voipbl"]["remote"]);
				
				
				/*
				 * Check if the remote voipbl blacklist was received.
				 */
				
				if (!empty($raw_data)) {
					
					
					/*
					 * Save the remote voipbl blacklist locally.
					 */
					
					file_put_contents($this->configuration["voipbl"]["database"], $raw_data);
				} else {
					
					
					/*
					 * Raise a critical error.
					 */
					
					$this->error("Unable to download the remote voipbl blacklist.");
				}
			}
			
			
			/*
			 * Read the local voipbl blacklist.
			 */
			
			$this->voipbl["data"] = @file($this->configuration["voipbl"]["database"]);
			
			
			/*
			 * Check if the local voipbl blacklist was received.
			 */
			
			if (!is_array($this->voipbl["data"])) {
				
				
				/*
				 * Raise a critical error.
				 */
				
				$this->error("Unable to read the local voipbl blacklist.");
			} else {
				
				
				/*
				 * Remove the newlines from the local voipbl blacklist.
				 */
				
				$this->voipbl["data"] = array_map("chop", $this->voipbl["data"]);
				
				
				/*
				 * Remove the invalid entries from the local voipbl blacklist.
				 */
				
				$this->voipbl["data"] = array_filter($this->voipbl["data"], [$this, "is_valid_cidr"]);
				
				
				/*
				 * Sort the data from local voipbl blacklist.
				 */
				
				sort($this->voipbl["data"], SORT_NUMERIC);
				
				
				/*
				 * Remove duplicate entries from the local voipbl blacklist.
				 */
				
				array_unique($this->voipbl["data"]);
				
				
				/*
				 * Update the local voipbl blacklist update timer.
				 */
				
				$this->voipbl["updated"] = time();
				
				
				/*
				 * Indicate that the aggregate blacklist must be updated.
				 */
				
				$update_aggrbl = 1;
			}
		}
		
		
		/*
		 * Check if the localbl blacklist needs to be updated.
		 */
		
		if (file_exists($this->configuration["localbl"]["database"]) && (!isset($this->localbl["updated"]) || ($this->localbl["updated"] < (time() - $this->configuration["localbl"]["frequency"])))) {
			
			
			/*
			 * Check if the file has been modified tine the last update frequency.
			 */
			
			if (!isset($this->localbl["updated"]) || (filemtime($this->configuration["localbl"]["database"]) > $this->localbl["updated"])) {
				
				
				/*
				 * Read the localbl blacklist.
				 */
				
				$this->localbl["data"] = @file($this->configuration["localbl"]["database"]);
				
				
				/*
				 * Check if the localbl blacklist was received.
				 */
				
				if (!is_array($this->localbl["data"])) {
					
					
					/*
					 * Raise a critical error.
					 */
					
					$this->error("Unable to read the localbl blacklist.");
				} else {
					
					
					/*
					 * Remove the newlines from the localbl blacklist.
					 */
					
					$this->localbl["data"] = array_map("chop", $this->localbl["data"]);
					
					
					/*
					 * Remove the invalid entries from the localbl blacklist.
					 */
					
					$this->localbl["data"] = array_filter($this->localbl["data"], [$this, "is_valid_cidr"]);
					
					
					/*
					 * Sort the data from localbl blacklist.
					 */
					
					sort($this->localbl["data"], SORT_NUMERIC);
					
					
					/*
					 * Remove duplicate entries from the localbl blacklist.
					 */
					
					array_unique($this->localbl["data"]);
					
					
					/*
					 * Indicate that the aggregate blacklist must be updated.
					 */
					
					$update_aggrbl = 1;
				}
			}
			
			
			/*
			 * Update the localbl blacklist update timer.
			 */
			
			$this->localbl["updated"] = time();
		}
		
		
		/*
		 * Check if the aggregate blacklist must be updated.
		 */
		
		if ($update_aggrbl) {
			
			
			/*
			 * Create the aggrbl blacklist.
			 */
			
			$this->aggrbl = array_merge($this->voipbl["data"], $this->localbl["data"]);
			
			
			/*
			 * Sort the data from the aggrbl blacklist.
			 */
			
			sort($this->aggrbl, SORT_NUMERIC);
			
			
			/*
			 * Remove duplicate entries from the aggrbl blacklist.
			 */
			
			array_unique($this->aggrbl);
		}
		
		
		/*
		 * Clear the local file cache.
		 */
		
		clearstatcache();
	}
	
	/**
	 * Transmits advertisements and withdrawals to ExaBGP
	 *
	 * @return    void
	 */
	
	private function exabgp_blacklists() {
		
		
		/*
		 * Get a list of prefixes to advertise and to withdraw.
		 */
		
		$advertise = array_diff($this->aggrbl, $this->exabgp);
		$withdraw  = array_diff($this->exabgp, $this->aggrbl);
		
		
		/*
		 * Loop through each prefix to advertise.
		 */
		
		foreach ($advertise as $prefix) {
			
			
			/*
			 * Add the prefix into the ExaBGP list.
			 */
			
			$this->exabgp[] = $prefix;
			
			
			/*
			 * Process the unicast method.
			 */
			
			if ($this->configuration["exabgp"]["method"] == "unicast") {
				
				
				/*
				 * Advertise the unicast prefix to ExaBGP.
				 */
				
				echo "announce route " . $prefix . " next-hop " . $this->configuration["exabgp"]["uc_next_hop"] ." community [" . $this->configuration["exabgp"]["communities"] . "]\n";
			}
			
			
			/*
			 * Process the FlowSpec method.
			 */
			
			if ($this->configuration["exabgp"]["method"] == "flowspec") {
				
				
				/*
				 * Advertise the FlowSpec to ExaBGP.
				 */
				
				echo "announce flow route {\\n match {\\n source " . $prefix . ";\\n " . $this->configuration["exabgp"]["fs_dst_port"] . $this->configuration["exabgp"]["fs_protocol"] . "}\\n then {\\n community [" . $this->configuration["exabgp"]["communities"] . "];\\n discard;\\n }\\n }\\n\n";
			}
		}
		
		
		/*
		 * Loop through each prefix to withdraw.
		 */
		
		foreach ($withdraw as $prefix) {
			
			
			/*
			 * Remove the prefix from the ExaBGP list
			 */
			
			$this->exabgp = array_diff($this->exabgp, [$prefix]);
			
			
			/*
			 * Process the unicast method.
			 */
			
			if ($this->configuration["exabgp"]["method"] == "unicast") {
				
				
				/*
				 * Withdraw the prefix from ExaBGP.
				 */
				
				echo "withdraw route " . $prefix . " next-hop " . $this->configuration["exabgp"]["uc_next_hop"] ." community [" . $this->configuration["exabgp"]["communities"] . "]\n";
			}
			
			
			/*
			 * Process the FlowSpec method.
			 */
			
			if ($this->configuration["exabgp"]["method"] == "flowspec") {
				
				
				/*
				 * Advertise the FlowSpec to ExaBGP.
				 */
				
				echo "withdraw flow route {\\n match {\\n source " . $prefix . ";\\n " . $this->configuration["exabgp"]["fs_dst_port"] . $this->configuration["exabgp"]["fs_protocol"] . "}\\n then {\\n community [" . $this->configuration["exabgp"]["communities"] . "];\\n discard;\\n }\\n }\\n\n";
			}
		}
	}
}


/*
 * Load the class.
 */

$exabgp = new exabgp_voipbl();


/*
 * Process the ExaBGP blacklists.
 */

$exabgp->start();
?>
