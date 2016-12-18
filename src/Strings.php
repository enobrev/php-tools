<?php
    namespace Enobrev;

    /**
     * @param string $sWord
     * @return string
     */
    function depluralize(string $sWord) : string {
        return Inflect::singularize($sWord);
    }

    /**
     * @param string $sWord
     * @return string
     */
    function pluralize(string $sWord): string {
        return Inflect::pluralize($sWord);
    }