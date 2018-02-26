<?php
namespace KS;
declare(ticks = 1);


abstract class AbstractExecutable
{
    protected $config;
    protected $logIdentifier;
    private $activeThreads = [];

    public function __construct(ExecutableConfigInterface $config)
    {
        $this->config = $config;
        $this->logIdentifier = $this->config->getLogIdentifier();
        set_time_limit(0);
        openlog($this->logIdentifier, LOG_PID, LOG_DAEMON);
        $this->setUpSignalHandling();
    }

    public function __destruct()
    {
        closelog();
    }

    /**
     * An overridable function for signal handling
     *
     * See http://php.net/manual/en/function.pcntl-signal.php for information about signal handling
     *
     * @param int $signo The int representation of the signal received
     * @param mixed $siginfo An optional (and usually absent) info packet associated with the signal
     * @return void
     */
    protected function handleSignal(int $signo, $siginfo) : void
    {
        $this->log("Signal received: $signo", LOG_DEBUG, "syslog");
        if ($signo === SIGTERM || $signo === SIGINT || $signo === SIGQUIT) {
            $this->log("Termination signal received. Shutting down.", LOG_INFO, [ "syslog", STDOUT ], true);
            $this->shutdown();
        } elseif ($signo === SIGHUP) {
            $this->config->reload();
        }
    }

    protected function setUpSignalHandling()
    {
        foreach ([SIGTERM, SIGHUP, SIGUSR1, SIGINT, SIGQUIT] as $sig) {
            $this->log("Registering signal $sig", LOG_DEBUG);
            pcntl_signal($sig, [ $this, 'handleSignal' ]);
        }
    }

    /**
     * Outputs text to one or more destinations, if the configured verbosity permits.
     * Note that `$verbosity` corresponds to php's LOG_* constants (http://php.net/manual/en/network.constants.php)
     *
     * @param string $str The string to output
     * @param int $verbosity The minimum level at which this should be output (lower is "quieter")
     * @param mixed $destinations One or more destinations to write to, according to the following rules:
     * 
     *   * If string, writes to log with string as message prefix
     *   * If resource, writes to resource (via fork)
     *
     * @param boolean $force Force a write, regardless of verbosity. (This is mainly to make sure certain text
     * gets into syslog.)
     * @return void
     */
    protected function log(string $str, int $verbosity = 3, $destinations = null, $force = false) : void
    {
        if ($this->config->getLogLevel() < $verbosity && !$force) {
            return;
        }

        if (!$destinations) {
            $destinations = ["syslog"];
        }
        if (!is_array($destinations)) {
            $destinations = [$destinations];
        }
        foreach($destinations as $d) {
            // If destination is syslog, use that
            if ($d === 'syslog') {
                $level = ["EMERGENCY", "ALERT", "CRITICAL", "ERROR", "WARNING", "NOTICE", "INFO", "DEBUG"][$verbosity];
                $level = "[$level]";
                while (strlen($level) < 11) {
                    $level .= " ";
                }
                syslog($verbosity, "$level $str");

            // If resource, write directly to it
            } elseif (gettype($d) === 'resource') {
                $this->thread(function() use ($d, $str) {
                    fwrite($d, "$str\n");
                });

            // Otherwise, don't know how to handle it.
            } else {
                new \RuntimeException("Don't know how to handle destinations of type ".gettype($d)." for text output.");
            }
        }
    }

    protected function thread(\Closure $p)
    {
        // TODO: Implement threading
        return $p();
    }

    protected function getActiveThreads(): array
    {
        return $this->activeThreads;
    }

    public function shutdown(): void
    {
        throw new Exception\Shutdown("Shutdown requested");
    }
}


