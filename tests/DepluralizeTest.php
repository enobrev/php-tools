<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    use PHPUnit_Framework_TestCase as TestCase;
 
    class DepluralizeTest extends TestCase {
        private $aWords;cd 
        
        public function setUp() {
            $this->aWords = [
                'addresses'   => 'address',
                'cities'      => 'city',
                'data'        => 'datum',
                'fish'        => 'fish',
                'geese'       => 'goose',
                'languages'   => 'language',
                'media'       => 'medium',
                'mooses'      => 'moose',
                'prices'      => 'price',
                'processings' => 'processing',
                'states'      => 'state',
                'statuses'    => 'status'
            ];
        }

        public function testDepluralize() {
            foreach($this->aWords as $sPlural => $sSingular) {
                $this->assertEquals($sSingular, depluralize($sPlural));
            }
        }

        public function testPluralize() {
            foreach ($this->aWords as $sPlural => $sSingular) {
                $this->assertEquals($sPlural, pluralize($sSingular));
            }
        }

    }
?>