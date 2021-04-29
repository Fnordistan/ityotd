// Coloretto main javascript

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/scrollmap",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.intheyearofthedragonexp", ebg.core.gamegui, {
        constructor: function(){
            console.log('intheyearofthedragonexp constructor');
              

        },
        setup: function( gamedatas )
        {
            console.log( "start creating player boards" );
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                var player_board_div = $('player_board_'+player_id);

                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div ); 
                
                $('yuannbr_'+player_id).innerHTML = player.yuan;              
                $('ricenbr_'+player_id).innerHTML = player.rice;              
                $('fwnbr_'+player_id).innerHTML = player.fireworks;              
                $('privnbr_'+player_id).innerHTML = player.favor;              
                
                this.setPersonScore( player_id, player.person_score, player.person_score_order );
            }
            
            this.setupNotifications();
            
            this.ensureSpecificImageLoading( ['../common/point.png'] );
            
            // Tooltips ///////////////////////////:
            
            // Events
            for( var event=1;event<=6;event++ )
            {
                var html = '<b>'+this.gamedatas.event_types[ event ].nametr+'</b><hr/>'+_(this.gamedatas.event_types[ event ].description);
                this.addTooltipToClass( 'eventtype_'+event, html, '' );
            }
            
            // Persons
            for( var type in this.gamedatas.person_types )
            {
                var persontype = this.gamedatas.person_types[ type ];

                var html = '<b>'+persontype.nametr+'</b><hr/>'+persontype.description;

                for( var level in persontype.subtype )
                {
                    this.addTooltipToClass( 'persontile_'+type+'_'+level, html, _('Recruit this person') );
                }
                
                this.addTooltipToClass( 'personcard_'+type, html, '' );
            }
               
            var html = '<b>'+_('Any person')+'</b><hr/>'+_('Using this card you can recruit any person from the display');
            this.addTooltipToClass( 'personcard_0', html, '' );

            dojo.query( '#persontiles .persontile' ).connect( 'onclick', this, 'onRecruit' );

            // Palaces
            for( var palace_id in this.gamedatas.palace )
            {
                var palace = this.gamedatas.palace[ palace_id ];
                this.createNewPalace( palace.player, palace.id );
                
                for( var floorno=1; floorno<toint( palace.size ); floorno++ )
                {
                    this.addFloorToPalace( palace_id );
                }
            }
            
            // Place persons in palaces
            for( var i in this.gamedatas.personpalace )
            {
                var person = this.gamedatas.personpalace[i];
                this.addPersonToPalace( person.palace_id, person.id, person.type, person.level );
            }
            
            // Actions
            for( var action_id in this.gamedatas.actions )
            {
                this.setAction( action_id, this.gamedatas.actions[ action_id ] );
            }
            
            // Actions choices
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.setActionChoice( player_id, player.action_choice );                
            }            
            
            // Person pool
            for( var i in gamedatas.personpool )
            {
                var personpool = gamedatas.personpool[ i ];
                $('persontile_nbr_'+personpool.type+'_'+personpool.level).innerHTML = personpool.nbr;
                
               // if( toint( personpool.nbr ) == 0 )
                //{   this.fadeOutAndDestroy( 'persontile_'+personpool.type+'_'+personpool.level );   }
            }
            
            this.setCurrentMonth( this.gamedatas.month );
                        
            // Tooltips
            this.addTooltipToClass( 'ttyuan', _('Yuan'), '' );
            this.addTooltipToClass( 'ttpers', _('Person Points'), '' );
            this.addTooltipToClass( 'ttrice', _('Rice'), '' );
            this.addTooltipToClass( 'ttfw', _('Fireworks'), '' );
            this.addTooltipToClass( 'ttpriv', _('Privileges'), '' );

            // Great Wall
            if (this.gamedatas.greatWall) {
                this.placeWallTiles(this.gamedatas.greatWall);
            }
        },

        placeWallTiles: function(wallTiles) {
            for( var player_id in this.gamedatas.players )
            {
                var player = this.gamedatas.players[player_id];
                const player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_wall_tiles', {'id': player_id} ), player_board_div );
            }
            const colors = {"0000ff" : 1, "008000" : 2, "ffa500": 3, "ff0000": 4, "ff00ff": 5 };
            for( const w in wallTiles) {
                const wallTile = wallTiles[w];
                const player_id = wallTile['player_id'];
                const color = this.gamedatas.players[ player_id ].color;
                const xoff = -60*(colors[color]-1);

                if (wallTile['location'] == 0) {
                    dojo.place( this.format_block('jstpl_wall', {'id': player_id, 'type': wallTile['bonus'], 'x': xoff, 'y': 0}), 'wall_tiles_'+player_id);
                } else {
                    dojo.place( this.format_block('jstpl_wall', {'id': player_id, 'type': wallTile['bonus'], 'x': xoff, 'y': -36 * wallTile['bonus']}), 'wall_tiles_'+player_id);
                }
            }
        },


        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'palaceFull':
                // Add red border to tile to be placed
                dojo.addClass( $('persontile_'+args.args.type+'_'+args.args.level), 'persontileToPlace' );
            break;

            case 'initialPlace':
            case 'personPhasePlace':
                // Add red border to tile to be placed
                dojo.addClass( $('persontile_'+args.args.type+'_'+args.args.level), 'persontileToPlace' );

                if( this.isCurrentPlayerActive() )
                {
                    // Show "place here" icons    
                    dojo.query( '#palaces_'+this.player_id+' .choosepalace' ).style( 'display', 'block' );
                }                
                break;
                
            case 'actionPhaseBuild':
                if( this.isCurrentPlayerActive() )
                {
                    // Insert a new "pseudo" palace
                    this.createNewPalace( this.player_id, 'new' );
                
                    // Show "place here" icons    
                    dojo.query( '#palaces_'+this.player_id+' .choosepalace' ).style( 'display', 'block' );
                }                
                break;
                
            case 'dummmy':
                break;
            }
        },
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
             
            switch( stateName )
            {
            case 'initialPlace':
            case 'personPhasePlace':
            case 'palaceFull':
                dojo.query( '.persontileToPlace' ).removeClass( 'persontileToPlace' );
                dojo.query( '.choosepalace' ).style( 'display', 'none' );
                break;

            case 'actionPhaseBuild':
                dojo.query( '.choosepalace' ).style( 'display', 'none' );
                this.removePalace( 'new' );
                break;

            }                
        }, 
        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                case 'actionPhasePrivilege':
                    this.addActionButton( 'smallPrivilege', _('Buy a small privilege'), 'onBuySmallPrivilege' ); 
                    this.addActionButton( 'largePrivilege', _('Buy a large privilege'), 'onBuyLargePrivilege' ); 
                    break;

                case 'actionPhaseChoose':
                    this.addActionButton( 'refillyuan', _('Take up money (up to 3 yuans)'), 'onTakeUpMoney' ); 
                    break;
                case 'palaceFull':
                    this.addActionButton( 'noReplace', _('None ! Release the new one.'), 'onNoReplace' );                     
                    break;

                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility
        
        // Create new palace for given player with given id
        createNewPalace: function( player_id, id )
        {
            console.log( 'createNewPalace' );
            
            dojo.place( this.format_block('jstpl_palace', { id: id } ), $('palaces_'+player_id) );
            
            // By default: only 1 floor
            dojo.style( 'palacefloor_'+id+'_3', 'display', 'none' );
            dojo.style( 'palacefloor_'+id+'_2', 'display', 'none' );
            
            if( id=='new' ) // pseudo "new palace" => no floors are shown
            {
                dojo.style( 'palacefloor_'+id+'_1', 'display', 'none' );
            }
            
            if( toint( player_id ) == toint( this.player_id ) )
            {
                dojo.query( '#palace_'+id+' .choosepalace' ).connect( 'onclick', this, 'onChoosePalace' );            
            }
            
        },
        
        addFloorToPalace: function( id )
        {
            for( var i=1; i<=3; i++ )
            {
                if( dojo.style( 'palacefloor_'+id+'_'+i, 'display' ) == 'none' )
                {
                    dojo.style( 'palacefloor_'+id+'_'+i, 'display', 'block' );
                    return;
                }
            }
        },

        removeFloorToPalace: function( id )
        {
            for( var i=3; i>=1; i-- )
            {
                if( dojo.style( 'palacefloor_'+id+'_'+i, 'display' ) == 'block' )
                {
                    dojo.style( 'palacefloor_'+id+'_'+i, 'display', 'none' );
                    return;
                }
            }
        },
        
        removePalace: function( palace_id )
        {
            dojo.destroy( 'palace_'+palace_id );
        },
        
        addPersonToPalace: function( palace_id, person_id, person_type, person_level )
        {
            dojo.place( this.format_block('jstpl_palace_person', { 
                id: person_id,
                type: person_type,
                level: person_level
            } ), $('palace_persons_'+palace_id) );
            
            // Move this tile from corresponding stack
            this.placeOnObject( $('palacepersontile_'+person_id+'_inner'), $('persontile_'+person_type+'_'+person_level) );
            this.slideToObject( $('palacepersontile_'+person_id+'_inner'), $('palacepersontile_'+person_id) ).play();
       
            // Tool tip
            var persontype = this.gamedatas.person_types[ person_type ];
            var html = '<b>'+persontype.nametr+'</b><hr/>'+persontype.description;
            this.addTooltip( 'palacepersontile_'+person_id+'_inner', html, _('Release this person') );
            
            dojo.connect( $('palacepersontile_'+person_id+'_inner'), 'onclick', this, 'onReleasePerson' );
        },
        
        setAction: function( action_id, action_type )
        {
            dojo.place( this.format_block('jstpl_action', { 
                type: action_type
            } ), $('actionplace_'+action_id) ); 
            
            // Move animation
            this.placeOnObject( $('actioncard_'+action_type), $('actionplace_1') );
            this.slideToObject( $('actioncard_'+action_type), $('actionplace_'+action_id) ).play();
            
            // Tooltip
            var html = '<b>'+_( this.gamedatas.action_types[action_type].name )+'</b><hr/>';
            html += _( this.gamedatas.action_types[action_type].description );
            
            if( this.gamedatas.largePrivilegeCost == 7 && action_type == 7 )
            {
                html = html.replace( '6','7' );
            }
            
            this.addTooltip( 'actioncard_'+action_type, html, _('Do this action') );
            
            dojo.connect( $('actioncard_'+action_type), 'onclick', this, 'onAction' );
        },
        
        setActionChoice: function( player_id, action_id )
        {
            if( action_id == null )
            {
                // No action => do nothing
            }
            else
            {
                // Place player flag on this action
                var flagsAlreadyThere = dojo.query( '#actioncard_'+action_id+' .actionflag' ).length;
                
                dojo.place( this.format_block('jstpl_actionflag', { 
                    id: player_id,
                    color: this.gamedatas.players[ player_id ].color
                } ), $('actioncard_'+action_id) );  
                
                // Position of the flag is swapped 8px down for each flag already there
                var hpos = 70+8*flagsAlreadyThere;
                
                this.placeOnObject( $('actionflag_'+player_id), $('overall_player_board_'+player_id ) );           
                this.slideToObjectPos( $('actionflag_'+player_id), $('actioncard_'+action_id), 5, hpos ).play();          
            }
        },
        
        setPersonScore: function( player_id, person_score, person_score_order )
        {
            var result = person_score;
            for( var order=toint( person_score_order ); order>1; order-- )
            {
                result += '+';
            }
            $('persnbr_'+player_id).innerHTML = result;
        },
        
        setCurrentMonth: function( month )
        {
            dojo.query( '.nextevent' ).removeClass( 'nextevent' );
            dojo.addClass( 'event_'+month, 'nextevent' );
            dojo.query( '.nextwall' ).removeClass( 'nextwall' );
            dojo.addClass( 'wall_'+month, 'nextwall' );
            
            for( var i=1; i<month; i++ )
            {
                dojo.addClass( 'event_'+i, 'eventtype_past' );
            }
        },

        // remove person tile from a palace
        releasePerson: function( person_id )
        {
            this.fadeOutAndDestroy( 'palacepersontile_'+person_id );
        },

        ///////////////////////////////////////////////////
        //// UI
        
        onRecruit: function( evt )
        {
            console.log( 'onRecruit' );
            evt.preventDefault( );
            
            if( ! this.checkAction( 'recruit' ) )
            {   return; }            
            
            // persontile_2_1
            var level = evt.currentTarget.id.substr( 13 );
            var type = evt.currentTarget.id.substr( 11, 1 );
            
            if( toint( $('persontile_nbr_'+type+'_'+level).innerHTML ) == 0 )
            {
                this.confirmationDialog( _("There are no more person from this type and your card will be discarded: do you confirm?"),
                    dojo.hitch( this, function() {
                           this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/recruit.html", { lock: true, 
                            type: type,
                            level: level
                            }, this, function( result ) {  } );             
                    } ) );
            }
            else
            {                
               this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/recruit.html", { lock: true, 
                type: type,
                level: level
                }, this, function( result ) {  } );             
            }
        },
            
        // Choose a palace (ex: to place some tile)
        onChoosePalace: function( evt )
        {
            console.log( 'onChoosePalace' );
            evt.preventDefault( );

            // choosepalace_<id>
            var palace_id = evt.currentTarget.id.substr( 13 );

            if( this.checkAction( 'place', true ) )
            {
                // Place a tile
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/place.html", { lock: true, 
                    id: palace_id
                }, this, function( result ) {  } );             
            } 
            else if( this.checkAction( 'build' ) )
            {
                // Build a floor
                if( palace_id=='new' )
                {   palace_id = 0;  }   // Note: meaning = "new palace"
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/build.html", { lock: true, 
                    id: palace_id   
                }, this, function( result ) {  } );             
            }
        },
        
        onAction: function( evt )
        {
            console.log( 'onAction' );
            evt.preventDefault( );    
            
            if( ! this.checkAction( 'action' ) )
            {   return; }           
            
            // actioncard_<id>
            var action_id = evt.currentTarget.id.substr( 11 );
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/action.html", { lock: true, 
                id: action_id
            }, this, function( result ) {  } );         
        },
        
        onBuySmallPrivilege: function()
        {
            if( ! this.checkAction( 'choosePrivilege' ) )
            {   return; }             
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/choosePrivilege.html", { lock: true, 
                large: 0
            }, this, function( result ) {  } );         
        
        },
        onBuyLargePrivilege: function()
        {
            if( ! this.checkAction( 'choosePrivilege' ) )
            {   return; } 
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/choosePrivilege.html", { lock: true, 
                large: 1
            }, this, function( result ) {  } );                 
        },
        onTakeUpMoney: function()
        {
            if( ! this.checkAction( 'refillyuan' ) )
            {   return; } 
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/refillyuan.html", { lock: true
            }, this, function( result ) {  } );                 
        },
        
        onReleasePerson: function( evt )
        {
            evt.preventDefault();

            // palacepersontile_${id}_inner
            var person_id = evt.currentTarget.id.substr( 17 );
            person_id = person_id.substr( 0, person_id.length-6 );

            if( this.checkAction( 'releaseReplace', true ) )
            {
                // Special case: release a person to replace it immediately by a new one
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/releaseReplace.html", { lock: true,
                    id: person_id
                }, this, function( result ) {  } );                 
                return;
            }
            if( ! this.checkAction( 'release' ) )
            {   return; } 
            
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/release.html", { lock: true,
                id: person_id
            }, this, function( result ) {  } );                 
         },
         
         onNoReplace: function( evt )
         {
            evt.preventDefault();
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/noReplace.html", { lock: true,
            }, this, function( result ) {  } );                 
            
         },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'placePerson', this, "notif_placePerson" );
            dojo.subscribe( 'newActions', this, "notif_newActions" );
            dojo.subscribe( 'actionChoice', this, "notif_actionChoice" );
            dojo.subscribe( 'usePersonCard', this, "notif_usePersonCard" );
            
            
            dojo.subscribe( 'refillyuan', this, "notif_refillyuan" );
            dojo.subscribe( 'harvest', this, "notif_harvest" );
            dojo.subscribe( 'fireworks', this, "notif_fireworks" );
            dojo.subscribe( 'taxes', this, "notif_taxes" );
            dojo.subscribe( 'personScoreUpdate', this, "notif_personScoreUpdate" );
            dojo.subscribe( 'gainPoint', this, "notif_gainPoint" );
            dojo.subscribe( 'gainPointFireworks', this, "notif_gainPointFireworks" );
            
            dojo.subscribe( 'buyPrivilege', this, "notif_buyPrivilege" );
            dojo.subscribe( 'newPalace', this, "notif_newPalace" );
            dojo.subscribe( 'buildPalace', this, "notif_buildPalace" );
            dojo.subscribe( 'newMonth', this, "notif_newMonth" );
            dojo.subscribe( 'release', this, "notif_release" );
            dojo.subscribe( 'eventPayYuan', this, "notif_eventPayYuan" );
            dojo.subscribe( 'eventPayRice', this, "notif_eventPayRice" );

            dojo.subscribe( 'decay', this, "notif_decay" );
            dojo.subscribe( 'endOfTurnScoring', this, "notif_endOfTurnScoring" );
            dojo.subscribe( 'endOfGameScoring', this, "notif_endOfGameScoring" );
            
        },  
        
        notif_placePerson: function( notif )
        {
            console.log( 'notif_placePerson' );
            console.log( notif );
            
            var player_id = notif.args.player_id;
            
            this.addPersonToPalace( notif.args.palace_id, notif.args.person_id, notif.args.person_type, notif.args.person_level );
            
            var tile_id = notif.args.person_type+'_'+notif.args.person_level;
            $('persontile_nbr_'+tile_id).innerHTML = toint( $('persontile_nbr_'+tile_id).innerHTML ) - 1;
            if( toint( $('persontile_nbr_'+tile_id).innerHTML ) == 0 )
            {   this.fadeOutAndDestroy( 'persontile_'+tile_id );    }
        },
        notif_personScoreUpdate: function( notif )
        {
            console.log( 'notif_personScoreUpdate' );
            console.log( notif );
            
            this.setPersonScore( notif.args.player_id, notif.args.person_score, notif.args.person_score_place );

        },
        notif_newActions: function( notif )
        {
            console.log( 'notif_newActions' );
            console.log( notif );
            
            // Remove all old actions
            dojo.query( '.actioncard' ).forEach(dojo.destroy);
            
            for( var action_id in notif.args.actions )
            {
                var type = notif.args.actions[action_id];
                this.setAction( action_id, type );
                action_id ++;
            }
        },
        notif_actionChoice: function( notif )
        {
            console.log( 'notif_actionChoice' );
            console.log( notif );
            
            this.setActionChoice( notif.args.player_id, notif.args.action_id );

            if( toint( notif.args.pay ) > 0 )
            {
                $('yuannbr_'+notif.args.player_id).innerHTML = ( toint( $('yuannbr_'+notif.args.player_id).innerHTML ) - 3 );
            }
        },
        notif_usePersonCard: function( notif )
        {
            console.log( 'notif_usePersonCard' );
            console.log( notif );
            
            this.fadeOutAndDestroy( 'personcard_'+notif.args.personcard_id );
        },
        notif_refillyuan: function( notif )
        {
            console.log( 'notif_refillyuan' );
            console.log( notif );
            $('yuannbr_'+notif.args.player_id).innerHTML = Math.max( 3, toint( $('yuannbr_'+notif.args.player_id).innerHTML ) );
        },        
        notif_harvest: function( notif )
        {
            console.log( 'notif_harvest' );
            console.log( notif );
            $('ricenbr_'+notif.args.player_id).innerHTML = ( toint( $('ricenbr_'+notif.args.player_id).innerHTML ) + toint( notif.args.nbr ) );
        },
        notif_eventPayRice: function( notif )
        {
            console.log( 'notif_eventPayRice' );
            console.log( notif );
            $('ricenbr_'+notif.args.player_id).innerHTML = ( toint( $('ricenbr_'+notif.args.player_id).innerHTML ) - toint( notif.args.nbr ) );
        },
        notif_fireworks: function( notif )
        {
            console.log( 'notif_fireworks' );
            console.log( notif );
            $('fwnbr_'+notif.args.player_id).innerHTML = ( toint( $('fwnbr_'+notif.args.player_id).innerHTML ) + toint( notif.args.nbr ) );
        },
        notif_taxes: function( notif )
        {
            console.log( 'notif_taxes' );
            console.log( notif );
            $('yuannbr_'+notif.args.player_id).innerHTML = ( toint( $('yuannbr_'+notif.args.player_id).innerHTML ) + toint( notif.args.nbr ) );
        },
        notif_eventPayYuan: function( notif )
        {
            console.log( 'notif_eventPayYuan' );
            console.log( notif );
            $('yuannbr_'+notif.args.player_id).innerHTML = ( toint( $('yuannbr_'+notif.args.player_id).innerHTML ) - toint( notif.args.nbr ) );
        },
        notif_gainPoint: function( notif )
        {
            console.log( 'notif_gainPoint' );
            console.log( notif );
            this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.nbr );
        },   
        notif_gainPointFireworks: function( notif )
        {
            console.log( 'notif_gainPointFireworks' );
            console.log( notif );
            this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.nbr );
            $('fwnbr_'+notif.args.player_id).innerHTML = Math.floor( ( toint( $('fwnbr_'+notif.args.player_id).innerHTML ) )/2 ); 
        },          
                   
        notif_buyPrivilege: function( notif )
        {
            console.log( 'notif_buyPrivilege' );
            console.log( notif );
            $('privnbr_'+notif.args.player_id).innerHTML = ( toint( $('privnbr_'+notif.args.player_id).innerHTML ) + toint( notif.args.nbr ) );
            $('yuannbr_'+notif.args.player_id).innerHTML = ( toint( $('yuannbr_'+notif.args.player_id).innerHTML ) - toint( notif.args.price ) );
        },              
        notif_newPalace: function( notif )
        {
            console.log( 'notif_newPalace' );
            console.log( notif );
            this.createNewPalace( notif.args.player_id, notif.args.palace_id );
        },              
        notif_buildPalace: function( notif )
        {
            console.log( 'notif_buildPalace' );
            console.log( notif );
            this.addFloorToPalace( notif.args.palace_id );
        },
        notif_newMonth:function( notif )
        {
            console.log( 'notif_newMonth' );
            console.log( notif );
            this.setCurrentMonth( notif.args.month );
        },
        notif_release: function( notif )
        {
            console.log( 'notif_newMonth' );
            console.log( notif );
            this.releasePerson( notif.args.person_id );        
        },
        notif_decay: function( notif )
        {
            console.log( 'notif_decay' );
            console.log( notif );
            
            for( var i in notif.args.destroy )
            {
                var palace_id = notif.args.destroy[ i ];
                this.removePalace( palace_id );
            }       
            for( var i in notif.args.reduce )
            {
                var palace_id = notif.args.reduce[ i ];
                this.removeFloorToPalace( palace_id );
            }       
        
        }, 
        notif_endOfTurnScoring: function( notif )
        {
            console.log( 'notif_endOfTurnScoring' );
            console.log( notif );
        
            this.displayTableWindow( 'endTurn', _('End of turn scoring'), notif.args.datagrid );
            
            for( var player_id in notif.args.player_to_score )
            {
                this.scoreCtrl[ player_id ].incValue( notif.args.player_to_score[ player_id ] );
            }
        }, 
        notif_endOfGameScoring: function( notif )
        {
            console.log( 'notif_endOfGameScoring' );
            console.log( notif );
        
            this.displayTableWindow( 'endGame', _('End of game scoring'), notif.args.datagrid );
            
            for( var player_id in notif.args.player_to_score )
            {
                this.scoreCtrl[ player_id ].incValue( notif.args.player_to_score[ player_id ] );
            }
        }
  });      
});


