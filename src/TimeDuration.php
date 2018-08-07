<?php
namespace KS;

class TimeDuration
{
    private $durationNano=0;

    public const NANOS=1;
    public const MICROS=self::NANOS*1000;
    public const MILLIS=self::MICROS*1000;
    public const SECONDS=self::MILLIS*1000;
    public const MINUTES=self::SECONDS*60;
    public const HOURS=self::MINUTES*60;
    public const DAYS=self::HOURS*24;
    public const WEEKS=self::DAYS*7;

    public function __construct($type=self::NANOS, $duration=0)
    {
        $this->setDuration($type, $duration);
    }

    public function hasDuration()
    {
        return $this->durationNano>0; // There is actually an amount of time in here besides 0.
    }

    public function copy()
    {
        return new TimeDuration(self::NANOS, $this->durationNano);
    }

    public function getDuration($type)
    {
        return \intdiv($this->durationNano,$type);
    }

    public function setDuration($type, $duration)
    {
        $this->durationNano = $duration*$type;
    }

    public function getNanos()
    {
        return $this->getDuration(self::NANOS);
    }
    
    public function setNanos($duration)
    {
        $this->setDuration(self::NANOS, $duration);
    }

    public function getMicros()
    {
        return $this->getDuration(self::MICROS);
    }
    
    public function setMicros($duration)
    {
        $this->setDuration(self::MICROS, $duration);
    }

    public function getMillis()
    {
        return $this->getDuration(self::MILLIS);
    }
    
    public function setMillis($duration)
    {
        $this->setDuration(self::MILLIS, $duration);
    }

    public function getSeconds()
    {
        return $this->getDuration(self::SECONDS);
    }
    
    public function setSeconds($duration)
    {
        $this->setDuration(self::SECONDS, $duration);
    }

    public function getMinutes()
    {
        return $this->getDuration(self::MINUTES);
    }
    
    public function setMinutes($duration)
    {
        $this->setDuration(self::MINUTES, $duration);
    }

    public function getHours()
    {
        return $this->getDuration(self::HOURS);
    }
    
    public function setHours($duration)
    {
        $this->setDuration(self::HOURS, $duration);
    }

    public function getDays()
    {
        return $this->getDuration(self::DAYS);
    }
    
    public function setDays($duration)
    {
        $this->setDuration(self::DAYS, $duration);
    }

    public function getWeeks()
    {
        return $this->getDuration(self::WEEKS);
    }

    public function setWeeks()
    {
        $this->setDuration(self::WEEKS);
    }
    
};