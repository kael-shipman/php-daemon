PHP Executables
==================================================================================

>
> **ABANDONED**
>
> This was sorta fun while it lasted, but there's absolutely no reason to do executables in PHP vs Go or Typescript. If anyone's still interested in this, feel free to take it over, but I've moved on.
>

There seem to be a handful of attempts at making daemonizing easy in PHP. I haven't looked deeply into these, so I'm not sure I'm really contributing much to the community by adding yet another one, but I have a selfish interest in trying it anyway.

This library contains classes for creating general executables. I define an executable as an application that can be called on the command line, usually with command-line arguments. That application may be a daemon, which runs in the background until it is killed, or it may be a single-run worker application that does a job and exits.

The main features of any of these are the following:

1. Easy handling of command-line arguments
2. A standard way of handling multi-channel logging
3. A standard and easy way to parallel process

None of these are particularly difficult tasks. Because of that, it's not clear exactly how much is to be gained by using a framework like this. At the same time, if you build a lot of applications, it makes sense to build them all in the same way, and thus it makes sense to use a (small) framework that fulfills these requirements in way you're happy with.


## Basic Assumptions

The primary basic assumption here is that your runtime application code should be separate from your functional library. Thus, any extensions of the classes in these libraries should implement little more than the code necessary to initialize functional dependencies and distribute the command to them. They should also maintain communication with the user and logs via interaction through the standard channels defined in this library.


## Usage

To create an executable, you'll need to include this library as a composer dependency in your project, then create at least 2 files.

The first is the actual entry point -- a bootstrap file, the equivalent of an `index.php` in the web world. That will handle all your configuration logic, including commandline arguments, etc., which you can do however you wish (though I would recommend [commando](https://github.com/nategood/commando)).

Next, you'll need to create a class that extends one of the abstract classes in this library. That class will be your actual runtime application, whose `run` method should be called by your bootstrap file. It should all look something like this:

**Bootstrap file:**

```php
<?php
namespace KS;

// src/bootstrap-cli.php

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

    // See notes on configuration below
    $config = require $configFile;

    $exec = new MyExec();
    $exec->run($config);

} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage());
} catch (\Throwable $e) {
    fwrite(STDERR, "Uh oh... Looks like an unrecoverable error :(. ".$e->getMessage()."\n\n");
}

```


**Executable File:**

```php
<?php
namespace KS;

// src/MyExec.php

class MyExec extends AbstractExecutable
{
    public function run(array $config = [])
    {
        $this->checkConfig($config);

        do {
            // Run application
        } while (true)
    }

    protected function checkConfig(array $config)
    {
        // Do configuration checks here
    }
}

```


## Configuration

Configuration seems to be one of those things that everyone solves differently. For web applications, my tendency is to use a Configuration class that provides a defined interface for configuration options. These classes are usually constructed by providing a version-controlled "defaults" configuration file `config.php`, and a non-version-controlled, instance-specific `config.local.php` that overrides the defaults. Both of these files return arrays of key-value configuration pairs. This works well for web environments because the webserver is a static "user" of the application.

In more interactive environments, though, you would probably have at least 3 sources of config:

1. A default config definition;
2. One or more user-specific configuration files (e.g., a `config.d/` directory); and
3. Optional command-line overrides.

That doesn't suggest a fundamentally different approach, though: You'll create a well-defined configuration interface (e.g., `$config->getRunDir()`, `$config->getLogIdentifier()`, `$config->getLogLevel()`), then you'll populate it by passing a configuration array which is simply a merge of all of the given configuration sources. Theoretically you would check the integrity of the configuration on instantiate.

In practice, this might look like so:

```php

// Static, default config
$config = require __DIR__.'/default-config.php';

// Merge in the global config file
$globalConfig = '/etc/my-exec/config';
if (!file_exists($globalConfig)) {
    throw new \RuntimeException("You must provide a global configuration file at `$globalConfig`");
}
$config = array_replace_recursive($config, require $globalConfig);

// Merge in user-defined config files
$userConfig = [];
if (is_dir("$globalConfig.d")) {
    $d = dir("$globalConfig.d");
    while (($f = $d->read()) !== false) {
        if ($f[0] === '.') {
            continue;
        }
        $userConfig = "$globalConfig.d/$f";
    }
}
sort($userConfig);
foreach($userConfig as $c) {
    $config = array_replace_recursive($config, require $c);
}

// Merge in command-line arguments (we're ignore how to get those for now)
$config = array_replace_recursive($config, $commandLineArgs);

$config = new MyConfig($config);
$config->checkConfiguration();

```

