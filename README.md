PHP Daemon
==================================================================================

There seem to be a handful of attempts at making daemonizing easy in PHP. This is my go at it.

To create a daemon, you'll need to include this library as a composer dependency in your project, then create at least 2 files.

The first is the actual entry point -- the equivalent of an `index.php` file in the web world. That will handle all your commandline arguments, etc., which you can do however you wish (though I would recommend [commando](https://github.com/nategood/commando)). You'll also need to create an object implementing at least `\KS\MessageDaemonConfigInterface` here to pass into the constructor of your daemon. It should all look something like this:

```php
<?php
namespace KS;

$root = __DIR__;
require_once "$root/vendor/autoload.php";

try {
    $cmd = new \Commando\Command();
    $cmd->option('c')
        ->aka('config-file')
        ->describedAs("The path defining the daemon's config file");

    // Get a config file path, or use default
    if (!$cmd['c']) {
        if (!($home = getenv("HOME"))) {
            $home = "/root";
        }
        $defaultOverride = true;
        $configFile = "$home/.config/ks/exchange-daemon.conf";
    } else {
        $defaultOverride = false;
        $configFile = realpath($cmd['c']);
    }

    $config = new MessageDaemonConfig("$root/src/config.php", $configFile);
    $exchange = new Exchange($config);
    $exchange->run();

} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage());
} catch (\Throwable $e) {
    fwrite(STDERR, "Uh oh... Looks like an unrecoverable error :(. ".$e->getMessage()."\n\n");
}


```

Next, you'll need to create your actual daemon. Most of this, of course, is done for you. You just have to extend the `AbstractMessageDaemon` class and implement the `processMessage` function. The `processMessage` simply accepts a string message and returns a string message. It can also control the parent process by throwing a few special exceptions.

