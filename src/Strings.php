<?php
    namespace Enobrev;

    use Doctrine\Inflector\InflectorFactory;
    use Doctrine\Inflector\Rules\Pattern;
    use Doctrine\Inflector\Rules\Patterns;
    use Doctrine\Inflector\Rules\Ruleset;
    use Doctrine\Inflector\Rules\Substitution;
    use Doctrine\Inflector\Rules\Substitutions;
    use Doctrine\Inflector\Rules\Transformation;
    use Doctrine\Inflector\Rules\Transformations;
    use Doctrine\Inflector\Rules\Word;


    /**
     * @param string $sWord
     * @return string
     */
    function depluralize(string $sWord) : string {
        // https://www.doctrine-project.org/projects/doctrine-inflector/en/2.0/index.html
        $oInflector = InflectorFactory::create()
                         ->withSingularRules(
                             new Ruleset(
                                 new Transformations(),
                                 new Patterns(
                                     new Pattern('beta')
                                 ),
                                 new Substitutions(
                                     new Substitution(new Word('data'), new Word('datum'))
                                 )
                             )
                         )
                         ->build();
        return $oInflector->singularize($sWord);
    }

    /**
     * @param string $sWord
     * @return string
     */
    function pluralize(string $sWord): string {
        // https://www.doctrine-project.org/projects/doctrine-inflector/en/2.0/index.html
        $oInflector = InflectorFactory::create()
                          ->withPluralRules(
                              new Ruleset(
                                  new Transformations(),
                                  new Patterns(
                                      new Pattern('geese')
                                  ),
                                  new Substitutions()
                              )
                          )
                          ->build();
        return $oInflector->pluralize($sWord);
    }