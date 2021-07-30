<?php

/*
 InTheYearOfDragon material
 Implementation of Great Wall and Super Events expansions: @David Edelstein <davidedelstein@gmail.com>
*/

$this->person_types = array(

    1 => array( 'name' => clienttranslate( 'Craftsmen' ),
                'label' => clienttranslate( 'Craftsman' ),
                'description' => self::_("Build additional palaces floor during the Build action"),
                'subtype' => array(
                    1 => array( 'value' => 2,
                                'items' => 1 )
                )
              ),
    2 => array( 'name' => clienttranslate( 'Court Ladies' ),
                'label' => clienttranslate( 'Court Lady' ),
                'description' => self::_("Win additional points at the end of each turn"),
                'subtype' => array(
                    1 => array( 'value' => 1,
                                'items' => 1 )
                )
              ),
    3 => array( 'name' => clienttranslate( 'Pyrotechnists' ),
                'label' => clienttranslate( 'Pyrotechnist' ),
                'description' => self::_("Take additional fireworks tiles during the Fireworks Display action"),
                'subtype' => array(
                    1 => array( 'value' => 5,
                                'items' => 1 ),
                    2 => array( 'value' => 3,
                                'items' => 2 )
                )
              ),
    4 => array( 'name' => clienttranslate( 'Tax Collectors' ),
                'label' => clienttranslate( 'Tax Collector' ),
                'description' => self::_("Take additional yuans during the Taxes action"),
                'subtype' => array(
                    1 => array( 'value' => 3,
                                'items' => 3 )
                )
              ),
    5 => array( 'name' => clienttranslate( 'Warriors' ),
                'label' => clienttranslate( 'Warrior' ),
                'description' => self::_("Win additional person points during Military Parade action"),
                'subtype' => array(
                    1 => array( 'value' => 5,
                                'items' => 1 ),
                    2 => array( 'value' => 3,
                                'items' => 2 )
                )
              ),
    6 => array( 'name' => clienttranslate( 'Monks' ),
                'label' => clienttranslate( 'Monk' ),
                'description' => self::_("Win additional points at the end of the game: number of Buddhas x number of floor of this palace"),
                'subtype' => array(
                    1 => array( 'value' => 6,
                                'items' => 1 ),
                    2 => array( 'value' => 2,
                                'items' => 2 )
                )
              ),
    7 => array( 'name' => clienttranslate( 'Healers' ),
                'label' => clienttranslate( 'Healer' ),
                'description' => self::_("Release less person during the Contagion event"),
                'subtype' => array(
                    1 => array( 'value' => 4,
                                'items' => 1 ),
                    2 => array( 'value' => 1,
                                'items' => 2 )
                )
              ),
    8 => array( 'name' => clienttranslate( 'Farmers' ),
                'label' => clienttranslate( 'Farmer' ),
                'description' => self::_("Take additional rice tiles during the Harvest action"),
                'subtype' => array(
                    1 => array( 'value' => 4,
                                'items' => 1 ),
                    2 => array( 'value' => 1,
                                'items' => 2 )
                )
              ),
    9 => array( 'name' => clienttranslate( 'Scholars' ),
                'label' => clienttranslate( 'Scholar' ),
                'description' => self::_("Win additional points during the Research action"),
                'subtype' => array(
                    1 => array( 'value' => 4,
                                'items' => 2 ),
                    2 => array( 'value' => 2,
                                'items' => 3 )
                )
              )

);

$this->event_types = array(

    1 => array( 'name' => clienttranslate( 'Peace' ),
                'description' => clienttranslate("Nothing happens") ),
    2 => array( 'name' => clienttranslate( 'Imperial Tribute' ),
                'description' => clienttranslate("Each player must pay 4 yuan in tribute to the emperor. If a player does not have enough money, he must release 1 person for each missing yuan.") ),
    3 => array( 'name' => clienttranslate( 'Drought' ),
                'description' => clienttranslate("Each player must pay 1 rice tile for each palace in which he has at least 1 person. If a player does not have enough rice tiles, he must release 1 person from each palace that he cannot supply.") ),
    4 => array( 'name' => clienttranslate( 'Dragon Festival' ),
                'description' => clienttranslate("The player or players with the most fireworks tiles get 6 victory points, and the players with the second most get 3 victory points. Afterward, the scoring players must return half of their fireworks tiles (rounding up)") ),
    5 => array( 'name' => clienttranslate( 'Mongol Invasion' ),
                'description' => clienttranslate("Each player wins 1 point for each helmets on all warriors in his palaces. Additionally, the player or players with the fewest helmets must each release 1 person.") ),
    6 => array( 'name' => clienttranslate( 'Contagion' ),
                'description' => clienttranslate("Each player must release 3 persons of their choosing. For each mortar pictured on a player's healers, he releases 1 fewer person.") )
);

$this->action_types = array(
     1 => array( 'name' => clienttranslate( 'Tax' ),
                'description' => self::_("Take 2 yuan plus 3 per Tax Collector"),
                'items' => 2 ),
     2 => array( 'name' => clienttranslate( 'Build' ),
                'description' => self::_("Take 1 palace floor plus 1 per Craftsman"),
                'items' => 1 ),
     3 => array( 'name' => clienttranslate( 'Harvest' ),
                'description' => self::_("Take 1 Rice plus 1 per Rice icon on your Farmers"),
                'items' => 1 ),
     4 => array( 'name' => clienttranslate( 'Fireworks' ),
                'description' => self::_("Take 1 Fireworks plus 1 per Fireworks icon on your Pyrotechnicists"),
                'items' => 1 ),
     5 => array( 'name' => clienttranslate( 'Military Parade' ),
                'description' => self::_("Move 1 space on the Person Track plus 1 for each helmet icon on your Warriors"),
                'items' => 1 ),
     6 => array( 'name' => clienttranslate( 'Research' ),
                'description' => self::_("Gain 1 VP plus 1 for each Book icon on your Scholars"),
                'items' => 1 ),
     7 => array( 'name' => clienttranslate( 'Privilege' ),
                'description' => self::_("Pay 2 yuan for a small privilege or 6 yuan for a large privilege"), // gets switched inplace if new edition
                'items' => 1 ),
    8 => array( 'name' => clienttranslate( 'Build Wall'),
                'description' => self::_("Build Great Wall section, gain bonus"),
                'items' => 1)
);

// types of wall tile bonuses
$this->wall_tiles = array (
    1 => array( 'name' => clienttranslate('Person Track'),
                'description' => self::_("Advance 3 spaces on the Person Track")),
    2 => array( 'name' => clienttranslate('Rice'),
                'description' => self::_("Gain 1 Rice")),
    3 => array( 'name' => clienttranslate('Palace'),
                'description' => self::_("Gain 1 palace section")),
    4 => array( 'name' => clienttranslate('Yuan'),
                'description' => self::_("Gain 2 yuan")),
    5 => array( 'name' => clienttranslate('Fireworks'),
                'description' => self::_("Gain 1 Firework")),
    6 => array( 'name' => clienttranslate('Victory Points'),
                'description' => self::_("Gain 3 victory points")),
);

$this->superevents = array(
    1 => array( 'name' => totranslate('Lanternfest'),
                'description' => self::_("Players score the people in their palaces, as at game end: each player earns 2 victory points for each person.") ),
    2 => array( 'name' => totranslate('Buddha'),
                'description' => self::_("Players score monks just as they would at game end: Buddhas x number of floors = victory points.") ),
    3 => array( 'name' => totranslate('Earthquake'),
                'description' => self::_("Beginning with the starting player, each player loses two palace sections (back to the supply). This may require players to release people.") ),
    4 => array( 'name' => totranslate('Flood'),
                'description' => self::_("Beginning with the starting player, each player adds their yuan, rice tiles, and fireworks tiles, and returns half the total (rounded down).") ),
    5 => array( 'name' => totranslate('Solar Eclipse'),
                'description' => self::_("Execute the event of the seventh round a second time.") ),
    6 => array( 'name' => totranslate('Volcanic Eruption'),
                'description' => self::_("All players move their person markers to space 0 on the person track, without changing the actual order (i.e. the marker of the leading player is on top and the marker of the last player is on the bottom).") ),
    7 => array( 'name' => totranslate('Tornado'),
                'description' => self::_("Beginning with the starting player, each player must discard 2 of their person cards. This means that the players have only one card each for the months 8 and 9 - and skip the person phase in the months 10, 11, and 12.") ),
    8 => array( 'name' => totranslate('Sunrise'),
                'description' => self::_("Beginning with the starting player, each player selects a young person (note: without using a person card!) and places it as usual. Each player must choose a different person type. Advance the appropriate number of spaces on the Person Track.") ),
    9 => array( 'name' => totranslate('Assassination Attempt'),
                'description' => self::_("All players must discard all their privileges - without compensation! - back into the supply. Thus, the privileges are not scored in the scoring phase that follows.") ),
    10 => array('name' => totranslate('Charter'),
                'description' => self::_("Beginning with the starting player, each player selects one type of person in their realm and receives the advantages that type of person offers.") ),
    11 => array('name' => totranslate('Hidden'),
                'description' => self::_("Random super event, hidden until month 7.") ),
);

// Action groups, depending on player number
// for basic game
$this->action_to_actiongroup_7 = array(
    2 => array(
        1=>1, 2=>1, 3=>1, 4=>1,  5=>2, 6=>2, 7=>2
    ),
    3 => array(
        1=>1, 2=>1, 3=>1,  4=>2, 5=>2,  6=>3, 7=>3
    ),
    4 => array(
        1=>1, 2=>1,  3=>2, 4=>2,  5=>3, 6=>3,  7=>4
    ),
    5 => array(
        1=>1, 2=>1,  3=>2, 4=>2,   5=>3,  6=>4,  7=>5
    )
);
// for game with extra (Great Wall) card
$this->action_to_actiongroup_8 = array(
    2 => array(
        1=>1, 2=>1, 3=>1, 4=>1,  5=>2, 6=>2, 7=>2, 8=>2
    ),
    3 => array(
        1=>1, 2=>1, 3=>1,  4=>2, 5=>2,6=>2, 7=>3,8=>3
    ),
    4 => array(
        1=>1, 2=>1,  3=>2, 4=>2,  5=>3, 6=>3,  7=>4, 8=>4
    ),
    5 => array(
        1=>1, 2=>1,  3=>2, 4=>2,   5=>3,6=>3,  7=>4, 8=>5
    )
);