<?php
    namespace Enobrev;

    use ICanBoogie\Inflector;

    /**
     * @param string $sWord
     * @return string
     */
    function depluralize(string $sWord) : string {
        return Inflector::get()->singularize($sWord);
    }

    /**
     * @param string $sWord
     * @return string
     */
    function pluralize(string $sWord): string {
        return Inflector::get()->pluralize($sWord);
    }