<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    use DateTime;
    use PHPUnit\Framework\TestCase;
 
    class MinuteTest extends TestCase {
        public function testMinutes(): void {
            $oNow       = new DateTime();
            $oThen      = new DateTime('-10 minutes');
            $oLater     = new DateTime('+10 minutes');
            $oThen1     = new DateTime('-2 minutes');
            $oLater1    = new DateTime('+1 minutes');

            $this->assertEquals(0,      minutes($oNow,     $oNow));
            $this->assertEquals(-9,     minutes($oNow,     $oThen));
            $this->assertEquals(10,     minutes($oNow,     $oLater));
            $this->assertEquals(9,      minutes($oThen,    $oNow));
            $this->assertEquals(-10,    minutes($oLater,   $oNow));
            $this->assertEquals(-1,     minutes($oNow,     $oThen1));
            $this->assertEquals(1,      minutes($oNow,     $oLater1));
        }

        public function testMinutesAgo(): void {
            $oNow       = new DateTime();
            $oThen      = new DateTime('-10 minutes');
            $oLater     = new DateTime('+10 minutes');
            $oThen1     = new DateTime('-2 minutes');
            $oLater1    = new DateTime('+1 minutes');

            $this->assertEquals('now',              minutes_ago($oNow,     $oNow));
            $this->assertEquals('9 minutes ago',    minutes_ago($oNow,     $oThen));
            $this->assertEquals('in 10 minutes',    minutes_ago($oNow,     $oLater));
            $this->assertEquals('in 9 minutes',     minutes_ago($oThen,    $oNow));
            $this->assertEquals('10 minutes ago',   minutes_ago($oLater,   $oNow));
            $this->assertEquals('1 minute ago',     minutes_ago($oNow,     $oThen1));
            $this->assertEquals('in 1 minute',      minutes_ago($oNow,     $oLater1));
        }
    }