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
                "type" => "int" )
        ),
    
    // Statistics existing for each player
    "player" => array(
    
        "person_lost_events" => array( "id"=> 10,
                                "name" => totranslate("Number of persons lost in events"), 
                                "type" => "int" ),
        "palace_nbr" => array( "id"=> 11,
                                "name" => totranslate("Final number of palace"), 
                                "type" => "int" ),
        "decay" => array( "id"=> 12,
                                "name" => totranslate("Palace floors lost during decay"), 
                                "type" => "int" ),
        "action_payed" => array( "id"=> 13,
                                "name" => totranslate("Actions payed with 3 yuans"), 
                                "type" => "int" ),
        "points_palace" => array( "id"=> 14,
                                "name" => totranslate("Points: palaces"), 
                                "type" => "int" ),
        "points_privilege" => array( "id"=> 15,
                                "name" => totranslate("Points: privileges"), 
                                "type" => "int" ),
        "points_court_ladies" => array( "id"=> 16,
                                "name" => totranslate("Points: court ladies"), 
                                "type" => "int" ),
        "points_scholars" => array( "id"=> 17,
                                "name" => totranslate("Points: scholars"), 
                                "type" => "int" ),
        "points_fireworks" => array( "id"=> 18,
                                "name" => totranslate("Points: fireworks"), 
                                "type" => "int" ),
        "points_person" => array( "id"=> 19,
                                "name" => totranslate("Points: number of persons"), 
                                "type" => "int" ),
        "points_monks" => array( "id"=> 20,
                                "name" => totranslate("Points: monks"), 
                                "type" => "int" ),
        "points_remaining" => array( "id"=> 21,
                                "name" => totranslate("Points: remaining items"), 
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
    )
);
?>
