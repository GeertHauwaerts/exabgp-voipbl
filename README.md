## About

The ExaBGP process plugin script advertises the prefixes from a local and the
voipbl.org blacklist to ExaBGP via unicast or FlowSpec BGP.


## Installation


The installation is very easy and straightforward:

  * Copy the `src/` directory to the location you would like to install it to.
  * Rename the `voipbl.conf.example` file to `voipbl.conf`.
  * Edit the configiration settings in `voipbl.conf`.
  * Test the installation by performing a dry-run via `php voipbl.php --dry-run`.
  * Inject the script into ExaBGP.


## Files

  * The `docs/` directory:
    * `exabgp.conf.example`         - An example configuration file for
                                      unicast and FlowSpec on ExaBGP.
    * `junos.flowspec.conf.example` - An example configuration file for the
                                      FlowSpec method on JunOS.
    * `junos.unicast.conf.example`  - An example configuration file for the
                                      unicast method on JunOS.
  * The `src/` directory:
    * `localbl.db.example`          - An example local blacklist.
    * `voipbl.conf.example`         - An example configuration file.
    * `voipbl.php`                  - The secret sauce.
  * `LICENSE`                       - The license of the application.
  * `README.md`                     - The file you are reading at this very
                                      moment.

## Collaboration

The GitHub repository is used to keep track of all the bugs and feature
requests; I prefer to work uniquely via GitHib, IRC and Twitter.

If you have a patch to contribute:

  * Fork this repository on GitHub.
  * Create a feature branch for your set of patches.
  * Commit your changes to Git and push them to GitHub.
  * Submit a pull request.

Shout to [https://twitter.com/GeertHauwaerts](@GeertHauwaerts) on Twitter at
any time :)

To contact me on IRC, you can poke me on:

| IRCnet   | Nickname | Hostname                                           |
| -------- | -------- | -------------------------------------------------- |
| Freenode | Geert    | [geert@irssi/staff/geert](geert@irssi/staff/geert) |
| Quakenet | Geert    | [geert@cows.go.moo](geert@cows.go.moo)             |
| EFnet    | Geert    | [geert@cows.go.moo](geert@cows.go.moo)             |
| IRCnet   | Geert    | [geert@staff.irc6.net](geert@staff.irc6.net)       |
| OFTC     | Geert    | [geert@geert.irssi.be](geert@geert.irssi.be)       |

Don't paste any confidential data on IRC without validating the hostname first!