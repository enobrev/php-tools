<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    use Exception;
    use PHPUnit\Framework\TestCase;
    use stdClass;

    /**
     * Class ReferenceTest
     * @victusfate found that when using references with the value from Exception::getStack you can replace the _actual vars_ in the stack
     * @package Enobrev
     */
    class ReferenceTest extends TestCase {
        public function testReference(): void {
            $b = new stdClass();
            $b->something = 1;
            $b->something_else = 2;
            $a = ['my_object' => $b ];

            function boom(array &$b) { throw new Exception('kablooey'); }

            try {
                boom($a);
            }
            catch(Exception $e) {
                Log::ex('test', $e);
            }

            $this->assertIsObject($a['my_object']);
            $this->assertEquals(1, $a['my_object']->something);
            $this->assertEquals(2, $a['my_object']->something_else);
        }
    }