<?php
namespace KS;
declare(ticks = 1);

/**
 * * Should daemonize, i.e., not return until killed
 * * Should output useful info to stdout
 * * Should output errors to stderr
 * * Should fork logging processes
 * * Should accept command line arguments
 * * Should accept an optional config file
 */

abstract class AbstractDaemon
{
    protected $cnf;
    private $children = [];

    public function __construct(DaemonConfigInterface $cnf)
    {
        $this->cnf = $cnf;
        error_reporting($this->cnf->getErrorReporting());
        ini_set('display_errors', (int)$this->cnf->getDisplayErrors());
        set_time_limit(0);
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
        if ($signo === SIGTERM) {
            $this->shutdown();
        } elseif ($signo === SIGHUP) {
            $this->cnf->reload();
        }
    }

    abstract public function run();

    protected function say(string $str, int $verbosity = 1, $destinations = []) : void
    {
        if (!is_array($destinations)) {
            $destinations = [$destinations];
        }
        foreach($destinations as $d) {
            if (is_string($d)) {
                $this->log($str, $d[0], $verbosity);
            } elseif (gettype($d) === 'resource') {
                if ($this->cnf->getVerbosity() >= $verbosity) {
                    $this->fork(function() use ($d, $str) {
                        fwrite($d, $str);
                    });
                }
            } else {
                new \RuntimeException("Don't know how to handle destinations of type ".gettype($d)." for text output.");
            }
        }
    }

    protected function log(string $str, string $logname, int $level)
    {
        // Use syslog
    }

    protected function fork(\Closure $p)
    {
        // TODO: Implememt forking
        $p();
    }

    protected function getChildren()
    {
        return $this->children;
    }

    abstract protected function shutdown();
}


