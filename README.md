nnstats
=======

Install:  DON'T!

WARNING!  ONLY TESTED WITH THE DEV BRANCH
If you are not running on dev, be warned!

When released, clone this github into your testing directory.

cd $NNBASE/misc/testing
git clone https://github.com/Tybio/nnstats.git

To use:

create a DB on the same server as your newznab db called 'nnstats'

	CREATE DATABASE nnstats;

give the user you use for newznab full permissions
  (Replace NNUSER with your mysql user name, this gives access from anwhere, for just localhost replace % with localhost)

	GRANT ALL ON nnstats.* TO `$NNUSER`@`%`;

Import the schema

	mysql -uNNUSER -p nnstats < lib/nnstats_schema.sql

Now, from here you can use stats.php to do the following:

getAllStats()   : Returns an array with all of the stats, complex array, more docs coming soon
getStatsTable() : Returns an array with a formatted table (text)
saveStats()	: Updates the DB with the current stats

For an example, look at scripts/example.php

