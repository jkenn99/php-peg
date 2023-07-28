<?php

namespace hafriedlander\Peg;

class Compiler {

    /** @var Compiler\RuleSet[] */
    private static array $parsers = [];

    public static bool $debug = \false;

    private static function createParser(array $match) {
        $name = $match['name'] ?? 'Anonymous Parser';
        // We allow indenting of the whole rule block, but only to the level
        // of the comment start's indent */
        $indent = $match['indent'];

        // Handle pragmas.
        if ($match['pragmas'] ?? \false) {
            foreach (\explode('!', $match['pragmas']) as $pragma) {

                $pragma = \trim($pragma);
                if ($pragma === '') {
                    continue;
                }

                switch ($pragma) {
                    case 'debug':
                        self::$debug = \true;
                        break;
                    case 'insert_autogen_warning':
                        return $indent . implode(PHP_EOL.$indent, array(
                            '/*',
                            'WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.',
                            '*/'
                        )) .\PHP_EOL;
                    default:
                        throw new \RuntimeException("Unknown pragma '$pragma' encountered");
                }

            }
        }

        self::$parsers[$name] ??= new Compiler\RuleSet;

        return self::$parsers[$name]->compile($indent, $match['grammar']);

    }

    public static function compile(string $string): string {

        static $rx = '@
            # Optional indent and marker of grammar definition start.
            ^(?<indent>\h*)/\*!\*

            # Optional pragmas and optional name.
            \h*(?<pragmas>(!\w+)+\h+)?(?<name>(\w)+)?\h*\r?\n?

            # Any character
            (?<grammar>(?:[^*]|\*[^/])+)?

            # Grammar definition end.
            \*/
        @smx';

        return preg_replace_callback(
            $rx,
            [self::class, 'createParser'],
            $string
        );

    }

    static function cli( $args ) {
        if ( count( $args ) == 1 ) {
            print "Parser Compiler: A compiler for PEG parsers in PHP \n" ;
            print "(C) 2009 SilverStripe. See COPYING for redistribution rights. \n" ;
            print "\n" ;
            print "Usage: {$args[0]} infile [ outfile ]\n" ;
            print "\n" ;
        }
        else {
            $fname = ( $args[1] == '-' ? 'php://stdin' : $args[1] ) ;
            $string = file_get_contents( $fname ) ;
            $string = self::compile( $string ) ;

            if ( !empty( $args[2] ) && $args[2] != '-' ) {
                file_put_contents( $args[2], $string ) ;
            }
            else {
                print $string ;
            }
        }
    }

}
