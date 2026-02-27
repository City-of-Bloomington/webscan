#!/bin/bash
cd /srv/sites/wave/scripts
SITE_HOME=/srv/data/wave php wave.php
SITE_HOME=/srv/data/wave php refresh_site_cache.php
