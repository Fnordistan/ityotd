<?php

$gameinfos = array( 

// Name of the game in English (will serve as the basis for translation) 
'game_name' => totranslate("In the Year of the Dragon: 10th Anniversary"),

// Game designer (or game designers, separated by commas)
'designer' => 'Stefan Feld',       

// Game artist (or game artists, separated by commas)
'artist' => 'Harald Lieske, Michael Menzel',         

// Year of FIRST publication of this game. Can be negative.
'year' => 2017,

// Game publisher
'publisher' => 'alea',                     

// Url of game publisher website
'publisher_website' => 'http://www.aleaspiele.de/',   

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 9,

// Board game geek id of the game
'bgg_id' => 214000,

// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2,3,4,5 ),    

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => 4,

// Discourage players to play with this number of players. Must be null if there is no such advice.
'not_recommend_player_number' => array( ),

'tie_breaker_description' => totranslate( "Position on the person track (descending order)" ),

// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 21,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 35,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 45,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 55,           

// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0, 

// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
'complexity' => 3,    

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
'luck' => 1,    

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
'strategy' => 4,    

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
'diplomacy' => 1,    

// Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => true,


// Games categories
//  You can attribute any number of "tags" to your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
'tags' => array( 4, 102,106 )
);