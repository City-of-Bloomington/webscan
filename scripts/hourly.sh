#!/bin/bash
cd /srv/sites/webscan/scripts
SITE_HOME=/srv/data/webscan php wave_scan.php
SITE_HOME=/srv/data/webscan php grackle_scan.php
