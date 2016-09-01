<?php
    namespace Enobrev;

    /**
     * @param string $word
     * @return string
     */
    function depluralize(string $word){
        $rules = array(
            'ss'  => false,
            'os'  => 'o',
            'ies' => 'y',
            'xes' => 'x',
            'oes' => 'o',
            'ves' => 'f',
            's'   => ''
        );

        foreach(array_keys($rules) as $key){
            if(substr($word, (strlen($key) * -1)) != $key)
                continue;
            if($key === false)
                return $word;
            return substr($word, 0, strlen($word) - strlen($key)) . $rules[$key];
        }

        return $word;
    }