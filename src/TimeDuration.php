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

    public function __construct(int $type=self::NANOS, int $duration=0)
    {
        $this->setDuration($type, $duration);
    }

    public function hasDuration() : bool
    {
        return $this->durationNano>0; // There is actually an amount of time in here besides 0.
    }

    public function copy() : TimeDuration
    {
        return new TimeDuration(self::NANOS, $this->durationNano);
    }

    public function getDuration($type) : int
    {
        return \intdiv($this->durationNano,$type);
    }

    public function setDuration(int $type, int $duration) : void
    {
        $this->durationNano = $duration*$type;
    }

    public function getNanos() : int
    {
        return $this->getDuration(self::NANOS);
    }
    
    public function setNanos(int $duration)
    {
        $this->setDuration(self::NANOS, $duration);
    }

    public function getMicros() : int
    {
        return $this->getDuration(self::MICROS);
    }
    
    public function setMicros(int $duration)
    {
        $this->setDuration(self::MICROS, $duration);
    }

    public function getMillis() : int
    {
        return $this->getDuration(self::MILLIS);
    }
    
    public function setMillis(int $duration)
    {
        $this->setDuration(self::MILLIS, $duration);
    }

    public function getSeconds() : int
    {
        return $this->getDuration(self::SECONDS);
    }
    
    public function setSeconds(int $duration)
    {
        $this->setDuration(self::SECONDS, $duration);
    }

    public function getMinutes() : int
    {
        return $this->getDuration(self::MINUTES);
    }
    
    public function setMinutes(int $duration)
    {
        $this->setDuration(self::MINUTES, $duration);
    }

    public function getHours() : int
    {
        return $this->getDuration(self::HOURS);
    }
    
    public function setHours(int $duration)
    {
        $this->setDuration(self::HOURS, $duration);
    }

    public function getDays() : int
    {
        return $this->getDuration(self::DAYS);
    }
    
    public function setDays(int $duration)
    {
        $this->setDuration(self::DAYS, $duration);
    }

    public function getWeeks() : int
    {
        return $this->getDuration(self::WEEKS);
    }

    public function setWeeks(int $duration)
    {
        $this->setDuration(self::WEEKS, $duration);
    }
    
};