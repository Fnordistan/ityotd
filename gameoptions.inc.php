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
                'name' => totranslate('Great Wall expansion'),
                'values' => array(
                    1 => array( 'name' => totranslate('None'), 'description' => totranslate('Do not use Great Wall')),
                    2 => array( 'name' => totranslate('Great Wall'), 'description' => totranslate('Use Great Wall expansion'), 'alpha' => true, 'nobeginner' => true ),
                ),
                'default' => 1
            ),

    102 => array(
        'name' => totranslate('Super Events expansion'),
        'values' => array(
            1 => array( 'name' => totranslate('None'), 'description' => totranslate('Do not use Super Events')),
            2 => array( 'name' => totranslate('Random'), 'description' => totranslate('Use random Super Event'), 'alpha' => true, 'nobeginner' => true ),
            3 => array( 'name' => totranslate('Lanternfest'), 'description' => totranslate('Use Lanternfest Super Event'), 'alpha' => true, 'nobeginner' => true ),
            4 => array( 'name' => totranslate('Buddha'), 'description' => totranslate('Use Buddha Super Event'), 'alpha' => true, 'nobeginner' => true ),
            5 => array( 'name' => totranslate('Earthquake'), 'description' => totranslate('Use Earthquake Super Event'), 'alpha' => true, 'nobeginner' => true ),
            6 => array( 'name' => totranslate('Flood'), 'description' => totranslate('Use Flood Super Event'), 'alpha' => true, 'nobeginner' => true ),
            7 => array( 'name' => totranslate('Solar Eclipse'), 'description' => totranslate('Use Solar Eclipse Super Event'), 'alpha' => true, 'nobeginner' => true ),
            8 => array( 'name' => totranslate('Volcanic Eruption'), 'description' => totranslate('Use Volcanic Eruption Super Event'), 'alpha' => true, 'nobeginner' => true ),
            9 => array( 'name' => totranslate('Tornado'), 'description' => totranslate('Use Tornado Super Event'), 'alpha' => true, 'nobeginner' => true ),
            10 => array( 'name' => totranslate('Sunrise'), 'description' => totranslate('Use Sunrise Super Event'), 'alpha' => true, 'nobeginner' => true ),
            11 => array( 'name' => totranslate('Assassination Attempt'), 'description' => totranslate('Use Assassination Attempt Super Event'), 'alpha' => true, 'nobeginner' => true ),
            12 => array( 'name' => totranslate('Charter'), 'description' => totranslate('Use Charter Super Event'), 'alpha' => true, 'nobeginner' => true ),
            13 => array( 'name' => totranslate('HARD MODE!'), 'description' => totranslate('Use random hidden Super Event, revealed on Turn 7'), 'alpha' => true, 'nobeginner' => true ),
        ),
        'default' => 1
    )
);