<?php

class TimeDuration
{
    private $durationNano=0;

    public const NANOS=1;
    public const MICROS=NANOs*1000;
    public const MILLIS=MICROS*1000;
    public const SECONDS=MILLIS*1000;
    public const MINUTES=SECONDS*60;
    public const HOURS=MINUTES*60;
    public const DAYS=HOURS*24;

    public function __construct($type=NANOS, $duration=0)
    {
        $this->setDuration($type, $duration);
    }

    public function hasDuration()
    {
        return $durationNano>0; // There is actually an amount of time in here besides 0.
    }

    public function copy()
    {
        return new TimeDuration(NANOS, $durationNano);
    }

    public function getDuration($type)
    {
        return $durationNano/$type;
    }

    public function setDuration($type, $duration)
    {
        $this->duration_nano = $duration*$type;
    }

    public function getNanos()
    {
        return $this->getDuration(NANOS);
    }
    
    public function setNanos($duration)
    {
        $this->setDuration(NANOS, $duration);
    }

    public function getMicros()
    {
        return $this->getDuration(MICROS);
    }
    
    public function setMicros($duration)
    {
        $this->setDuration(MICROS, $duration);
    }

    public function getMillis()
    {
        return $this->getDuration(MILLIS);
    }
    
    public function setMillis($duration)
    {
        $this->setDuration(MILLIS, $duration);
    }

    public function getSeconds()
    {
        return $this->getDuration(SECONDS);
    }
    
    public function setSeconds($duration)
    {
        $this->setDuration(SECONDS, $duration);
    }

    public function getMinutes()
    {
        return $this->getDuration(MINUTES);
    }
    
    public function setMinutes($duration)
    {
        $this->setDuration(MINUTES, $duration);
    }

    public function getHours()
    {
        return $this->getDuration(HOURS);
    }
    
    public function setHours($duration)
    {
        $this->setDuration(HOURS, $duration);
    }

    public function getDays()
    {
        return $this->getDuration(DAYS);
    }
    
    public function setDays($duration)
    {
        $this->setDuration(DAYS, $duration);
    }
    
};