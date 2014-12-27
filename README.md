Snottwitterd
============

Stupid Notification Twitter Daemon.

This simple script will poll Twitter every 120 seconds (by default) and send
a notification to the desktop using `notify-send`.

To use it you will need to create an application on your twitter account and
copy the `config.dist.php` file to `config.php` and fill in the details that
you will find at https://apps.twitter.com.

Then install the dependencies using [composer](https://getcomposer.org):

````
$ composer install
````

Then run the daemon in whichever manner you see fit.

````
$ php snottwitterd.php
````

You will receive desktop notifications for new Twits.
