<?php
 /**
  * intheyearofthedragonexp.view.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * intheyearofthedragonexp main static view construction
  *
  */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_intheyearofthedragonexp_intheyearofthedragonexp extends game_view
  {
    function getGameName() {
        return "intheyearofthedragonexp";
    }    
  	function build_page( $viewArgs )
  	{		

        $template = self::getGameName() . "_" . self::getGameName();

        $this->page->begin_block( $template, "player" );
        $this->page->begin_block( $template, "persontile" );
        $this->page->begin_block( $template, "personcard" );
        $this->page->begin_block( $template, "actionplace" );


  	    // Get players
        $players = $this->game->loadPlayersBasicInfos();
        self::watch( "players", $players );
        
        $player_nbr = count( $players );    // Note: number of players = number of rows

        global $g_user;

        foreach( $players as $player )
        {
            if( $player['player_id'] == $g_user->get_id() )
            {
                $this->page->insert_block( "player", array( "PLAYER_ID" => $player['player_id'],
                                                            "PLAYER_NAME" => $player['player_name'] ) );
            }
        }

        foreach( $players as $player )
        {
            if( $player['player_id'] != $g_user->get_id() )
            {
                $this->page->insert_block( "player", array( "PLAYER_ID" => $player['player_id'],
                                                            "PLAYER_NAME" => $player['player_name'] ) );
            }
        }
        
        // Events
        $this->page->begin_block( $template, "event" );
        $events = $this->game->getEvents();
        foreach( $events as $event_id => $event_type )
        {
            $this->page->insert_block( 'event', array(
                'ID' => $event_id,
                'TYPE' => $event_type
            ) );
        }

        if ($this->game->useGreatWall()) {
            $this->page->begin_block( $template, "wall" );
            for ($w = 1; $w <=12; $w++) {
                $this->page->insert_block( 'wall', array(
                    'ID' => $w,
                ) );
            }
        }

        // Persontiles
        foreach( $this->game->person_types as $type_id => $person_type )
        {
            foreach( $person_type['subtype'] as $level => $subtype )
            {
                $this->page->insert_block( 'persontile', array(
                    'ID' => $type_id.'_'.$level
                ) );
            }
        }
        
        // Personcard
        $cards = $this->game->getPersoncards($g_user->get_id());
        $second_joker = false;
        foreach( $cards as $card_id => $card )
        {
            $this->page->insert_block( 'personcard', array(
                'ID' => $card_id,
                'TYPE' => $card['type'],
                'SECONDJOKER' => ( $card['type']==0 && $second_joker ) ? 'second_joker' : ''
            ) );
            
            if( $card['type'] == 0 )    // Joker
                $second_joker = true;
        }
        // open hands?
        if ($this->game->isOpenhand()) {
            $this->page->begin_block( $template, 'openhand_person');
            $this->page->begin_block( $template, 'openhand_player');

            foreach( $players as $player ) {
                $this->page->reset_subblocks( 'openhand_person');
                if( $player['player_id'] != $g_user->get_id() ) {
                    $opencards = $this->game->getPersoncards($player['player_id']);
                    $second_wild = false;
                    foreach($opencards as $card_id => $card) {
                        $this->page->insert_block( 'openhand_person', array(
                            'PLAYER_ID' => $player['player_id'],
                            'ID' => $card_id,
                            'TYPE' => $card['type'],
                            'SECONDJOKER' => ( $card['type']==0 && $second_wild ) ? 'second_joker' : ''
                        ));
                        if( $card['type'] == 0 ) {
                            $second_wild = true;
                        }
                    }

                    $this->page->insert_block('openhand_player', array(
                        "PLAYER_ID" => $player['player_id'],
                        "PLAYER_NAME" => $player['player_name'],
                        "PLAYER_COLOR" => $player['player_color']
                    ));
    
                }
            }
        }
        
        // Actions
        $actiongroups = $this->game->getActionGroups();
        $actionmap = $actiongroups[ count( $players ) ];
        $current_group = 1;
        $actionct = $this->game->useGreatWall() ? 8 : 7;
        for( $i=1; $i<=$actionct; $i++ )
        {
            $space_before = '';
        
            if( $current_group != $actionmap[ $i ] )
            {
                $current_group = $actionmap[ $i ];
                $space_before = '&nbsp;&nbsp;';
            }
        
            $this->page->insert_block( 'actionplace', array(
                'ID' => $i,
                'SPACE' => self::raw( $space_before )
            ) );
        }
  	}
}