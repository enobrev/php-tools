<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    use PHPUnit\Framework\TestCase;
 
    class DepluralizeTest extends TestCase {
        private $aWords;
        
        public function setUp():void {
            $this->aWords = [
                'addresses'   => 'address',
                'cities'      => 'city',
                'data'        => 'datum',
                'betas'       => 'beta',
                'fish'        => 'fish',
                'geese'       => 'goose',
                'languages'   => 'language',
                'media'       => 'medium',
                'moose'       => 'moose',
                'prices'      => 'price',
                'processings' => 'processing',
                'states'      => 'state',
                'statuses'    => 'status'
            ];
        }

        public function testDepluralize(): void
        {
            foreach($this->aWords as $sPlural => $sSingular) {
                $this->assertEquals($sSingular, depluralize($sPlural));
            }
        }

        public function testPluralize(): void
        {
            foreach ($this->aWords as $sPlural => $sSingular) {
                $this->assertEquals($sPlural, pluralize($sSingular));
            }
        }

    }