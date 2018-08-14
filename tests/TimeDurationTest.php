<?php
namespace KS;

class TimeDurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider millisToSecondsData
     */
    public function testMillisToSeconds($millis, $expectedSeconds)
    {
        $duration = new TimeDuration();
        $duration->setMillis($millis);
        $this->assertSame($millis, $duration->getMillis());
        $this->assertSame($expectedSeconds, $duration->getSeconds());
    }

    public function millisToSecondsData()
    {
        return [
            [1000, 1],
            [2500, 2],
            [35123, 35]
        ];
    }
}