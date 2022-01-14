<?php

/////////////////////////////////////////////////////////////////////
///// Game statistics description
/////

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "person_lost_events_allplayers" => array( "id"=> 10,
                                "name" => totranslate("Number of persons lost in events"), 
                                "type" => "int" ),

        "walls_built_allplayers" => array( "id"=> 11,
                "name" => totranslate("Number of walls built by players"), 
                "type" => "int" ),

        "super_event" => array( "id" => 25,
                "name" => totranslate("Super Event"),
                "type" => "int"),
        ),
    
    // Statistics existing for each player
    "player" => array(
    
        "person_lost_events" => array( "id"=> 10,
                                "name" => totranslate("Number of persons lost in events"), 
                                "type" => "int" ),
        "palace_nbr" => array( "id"=> 11,
                                "name" => totranslate("Final number of palaces"),
                                "type" => "int" ),
        "decay" => array( "id"=> 12,
                                "name" => totranslate("Palace floors lost during decay"), 
                                "type" => "int" ),
        "action_payed" => array( "id"=> 13,
                                "name" => totranslate("Actions paid for with 3 yuan"), 
                                "type" => "int" ),
        "points_palace" => array( "id"=> 14,
                                "name" => totranslate("Points: Palaces"), 
                                "type" => "int" ),
        "points_privilege" => array( "id"=> 15,
                                "name" => totranslate("Points: Privileges"), 
                                "type" => "int" ),
        "points_court_ladies" => array( "id"=> 16,
                                "name" => totranslate("Points: Court ladies"), 
                                "type" => "int" ),
        "points_scholars" => array( "id"=> 17,
                                "name" => totranslate("Points: Scholars"), 
                                "type" => "int" ),
        "points_fireworks" => array( "id"=> 18,
                                "name" => totranslate("Points: Fireworks"), 
                                "type" => "int" ),
        "points_person" => array( "id"=> 19,
                                "name" => totranslate("Points: Number of persons"), 
                                "type" => "int" ),
        "points_monks" => array( "id"=> 20,
                                "name" => totranslate("Points: Monks"), 
                                "type" => "int" ),
        "points_remaining" => array( "id"=> 21,
                                "name" => totranslate("Points: Remaining resources"), 
                                "type" => "int" ),
        "points_mongol" => array( "id"=> 22,
                                "name" => totranslate("Points: Mongol Invasion"), 
                                "type" => "int" ),
        "points_wall" => array( "id" => 23,
                                "name" => totranslate("Points: Great Wall"),
                                "type" => "int"),
        "walls_built" => array( "id" => 24,
                                "name" => totranslate("Walls Built"),
                                "type" => "int")
    ),

    "value_labels" => array(
		25 => array( 
			0 => totranslate("None"),
			1 => totranslate("Lanternfest"), 
			2 => totranslate("Buddha"), 
			3 => totranslate("Earthquake"), 
			4 => totranslate("Flood"), 
			5 => totranslate("Solar Eclipse"), 
			6 => totranslate("Volcanic Eruption"), 
			7 => totranslate("Tornado"), 
			8 => totranslate("Sunrise"), 
			9 => totranslate("Assassination Attempt"), 
			10 => totranslate("Charter"), 
        )
    )
);