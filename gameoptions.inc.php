<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * InTheYearOfTheDragonExp implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * InTheYearOfTheDragonExp game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in intheyearofthedragonexp.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
        'name' => totranslate('Large privilege cost'),
        'values' => array(
            1 => array( 'name' => totranslate('New edition: 7')),
            2 => array( 'name' => totranslate('Old edition: 6')),
        ),
        'default' => 1
    ),

    101 => array(
                'name' => totranslate('Expansions'),
                'values' => array(
                    1 => array( 'name' => totranslate('None'), 'description' => totranslate('No expansions)')),
                    2 => array( 'name' => totranslate('Great Wall'), 'description' => totranslate('Use Great Wall expansion'), 'alpha' => true, 'nobeginner' => true ),
                    3 => array( 'name' => totranslate('Super-Events'), 'description' => totranslate('Use Super-Events expansion'), 'alpha' => true, 'nobeginner' => true ),
                    4 => array( 'name' => totranslate('Great Wall and Super-Events'), 'description' => totranslate('Use BOTH Great Wall AND Super-Events expansions'), 'alpha' => true, 'nobeginner' => true )
                ),
                'default' => 1
            ),
);