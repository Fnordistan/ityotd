// In The Year of the Dragon main javascript

const COLORS_PLAYER = {"0000ff" : 1, "008000" : 2, "ffa500": 3, "ff0000": 4, "ff00ff": 5 }

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
                var html = '<b>'+this.gamedatas.event_types[ event ].name+'</b><hr/>'+this.gamedatas.event_types[ event ].description;
                this.addTooltipToClass( 'eventtype_'+event, html, '' );
            }
            
            // Persons
            for( var type in this.gamedatas.person_types )
            {
                var persontype = this.gamedatas.person_types[ type ];

                var html = '<b>'+persontype.name+'</b><hr/>'+persontype.description;

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
                this.placeAction( action_id, this.gamedatas.actions[ action_id ] );
            }
            
            // Actions choices
            for( var player of this.sortByPersonOrder()) {
                this.setActionChoice( player.id, player.action_choice );                
            }            
            
            // Person pool
            for( var i in gamedatas.personpool )
            {
                var personpool = gamedatas.personpool[ i ];
                $('persontile_nbr_'+personpool.type+'_'+personpool.level).innerHTML = personpool.nbr;
                
               if( toint( personpool.nbr ) == 0 )
                {   this.fadeOutAndDestroy( 'persontile_'+personpool.type+'_'+personpool.level );   }
            }
            
            this.setCurrentMonth( this.gamedatas.month );

            this.placeSuperEvent( this.gamedatas.superEvent);
                        
            // Tooltips
            this.addTooltipToClass( 'ttyuan', _('Yuan'), '' );
            this.addTooltipToClass( 'ttpers', _('Person Points'), '' );
            this.addTooltipToClass( 'ttrice', _('Rice'), '' );
            this.addTooltipToClass( 'ttfw', _('Fireworks'), '' );
            this.addTooltipToClass( 'ttpriv', _('Privileges'), '' );

            // Great Wall
            if (this.gamedatas.greatWall) {
                this.placeWallTiles(this.gamedatas.greatWall);
            } else {
                document.getElementById("great_wall").style["display"] = "none";
            }

            // openhand
            document.getElementById("openhands").style["display"] = "none";
            if (this.gamedatas.openhand) {
                // Show/Hide Player shares button
                document.getElementById('openhands_button').addEventListener('click', () => {
                    this.onShowHands();
                });
            } else {
                document.getElementById("openhands_button").style.display = "none";
            }
        },

        /**
         * Button for showing/hiding open hands
         */
        onShowHands: function() {
            const openhandsdiv = document.getElementById("openhands");
            var handsdisplay = openhandsdiv.style.display;
            // toggle display
            handsdisplay = (handsdisplay == 'none') ? 'block' : 'none';
            var button_text = (handsdisplay == 'none') ? _("Open Hands (Show)") : _("Open Hands (Hide)");
            openhandsdiv.style.display = handsdisplay;
            document.getElementById('openhands_button').innerHTML = button_text;
        },

        /**
         * 
         */
        setupPlayerHand: function(player_id, openhand) {
            const handdiv = this.format_block('jstpl_openhand', {'name': this.gamedatas.players[player_id]['name'], 'color' : this.gamedatas.players[player_id]['color'], 'id' : player_id});
            dojo.place(handdiv, openhand);
        },

        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;
                }
                if (args.superevent_icon) {
                    var event_html = this.createSuperEventTile("superevent", args.superevent, 0.35);
                    event_html = event_html.replace('class="ityotd_superevent"', 'class="ityotd_superevent_log"');
                    args.superevent_icon = event_html;
                }
                if (args.superevent_name) {
                    args.superevent_name = '<b>'+args.superevent_name+'</b>';
                }
                if (args.event_name) {
                    args.event_name = '<b>'+args.event_name+'</b>';
                }
            } catch (e) {
                console.error(log, args, "Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        /**
         * Add text below the title banner.
         * @param {string} html 
         */
         addToActionHeader : function(html) {
            const main = $('pagemaintitletext');
            main.innerHTML += html;
        },

        /**
         * Place each Wall Tile, on player board and in Great Wall.
         * @param {Object} wallTiles 
         */
        placeWallTiles: function(wallTiles) {
            for( const w in wallTiles) {
                const wallTile = wallTiles[w];
                this.placeWallTile(wallTile['player_id'], wallTile['location'], wallTile['bonus']);
            }
            const len = this.gamedatas.wallLength;
            this.decorateUnbuiltWallSections(len);
        },

        /**
         * Place a wall tile at the specified wall section.
         * @param {string} player_id 
         * @param {int} location 
         * @param {int} bonus 
         */
        placeWallTile: function(player_id, location, bonus) {
            const WALL_BONUS = [
                _("Advance 3 spaces on the Person Track"),
                _("Gain 1 Rice"),
                _("Gain 1 palace section"),
                _("Gain 2 yuan"),
                _("Gain 1 Firework"),
                _("Gain 3 victory points"),
            ];
            var wall_div = document.getElementById('great_wall_'+player_id);
            if (!wall_div) {
                wall_div = dojo.place(this.format_block('jstpl_player_great_wall', {'id': player_id}), 'player_board_'+player_id);
            }

            const pcolor = this.gamedatas.players[ player_id ].color;
            const xoff = -60*(COLORS_PLAYER[pcolor]-1);
            let wall_tile = document.getElementById('player_wall_'+player_id+'_'+bonus);
            if (location == 0) {
                // face up on player board
                const yoff = -36*bonus;
                if (wall_tile) {
                    wall_tile.style['background-position'] = xoff+"px "+yoff+"px";
                } else {
                    wall_tile = dojo.place( this.format_block('jstpl_player_wall', {'id': player_id, 'type': bonus, 'x': xoff, 'y': yoff}), 'great_wall_'+player_id);
                }
                let walltile_tt = _("Wall tile bonus: ${bonus}");
                walltile_tt = walltile_tt.replace('${bonus}', WALL_BONUS[bonus-1]);
                this.addTooltip(wall_tile.id, walltile_tt, '');
            } else {
                // flip face down on player board
                if (wall_tile) {
                    wall_tile.style['background-position'] = xoff+"px 0px";
                } else {
                    wall_tile = dojo.place( this.format_block('jstpl_player_wall', {'id': player_id, 'type': bonus, 'x': xoff, 'y': 0}), 'great_wall_'+player_id);
                }
                wall_tile.classList.add("ityotd_wall_built");
                this.addTooltip(wall_tile.id, _("Wall section (built)"), '');
                // flip over the Great Wall tile
                const wall = document.getElementById('wall_'+location);
                wall.style['background-position-x'] = xoff+"px";
                let wall_tt = _("Great Wall section built by ${player_name}");
                const player_name = this.spanPlayerName(player_id);
                wall_tt = wall_tt.replace('${player_name}', player_name);
                this.addTooltip(wall.id, wall_tt, '');
            }
        },

        /**
         * Put tooltip on unbuilt wall sections.
         * @param {int} len first unbuilt section 
         */
        decorateUnbuiltWallSections: function(len) {
            if (len === "0") {
                len = 0;
            }
            for (let ln = len+1; ln <= 12; ln++) {
                const id = 'wall_'+ln;
                this.addTooltip(id, _("Great Wall (unbuilt)"), '');
            }
        },

        /**
         * Place SuperEvent tile (or not if value is 0) on Event 7.
         * @param {int} se
         */
        placeSuperEvent: function(se) {
            if (se != 0) {
                var superevent = this.gamedatas.super_events[ se ];
                var event_7 = document.getElementById("event_7");

                var parent = event_7.parentNode;
                var desc_div = document.createElement("div");
                desc_div.style['display'] = 'inline-block';
                desc_div.style['margin-bottom'] = '65px';
                var desc_span = document.createElement("span");
                desc_span.id = "superevent_label";
                desc_span.innerHTML = superevent.name;
                desc_span.classList.add('ityotd_se_label');
                desc_div.appendChild(desc_span);
                parent.insertBefore(desc_div, event_7);

                if (this.gamedatas.month > 7) {
                    desc_span.style['opacity'] = 0.5;
                    var fin_event_div = this.createSuperEventTile("superevent", 12, 0.3);
                    dojo.place(fin_event_div, event_7);
                    this.addTooltipToClass( 'ityotd_superevent', superevent.name, '' );
                } else {
                    desc_span.style['font-weight'] = 'bold';
                    var superevent_div = this.createSuperEventTile("superevent", se, 0.3);
                    dojo.place(superevent_div, event_7);
    
                    var tooltip_icon = this.createSuperEventTile("superevent_tt", se, 1);
                    tooltip_icon = tooltip_icon.replace('class="ityotd_superevent"', 'class="ityotd_superevent_icon"');
                    var tooltip = this.format_block('jstpl_super_event_icon', {'name': superevent.name, 'description': superevent.description, 'icon': tooltip_icon});

                    this.addTooltipToClass( 'ityotd_superevent', tooltip, '' );
                    this.addTooltipToClass('ityotd_se_label', tooltip, '');
                }
            }
        },

        /**
         * Get div tile with superevent icon.
         * @param {string} id for div
         * @param {int} se index of super event
         * @param {float} scale 
         * @returns html string
         */
        createSuperEventTile: function(id, se, scale) {
            var xoff = -80 * scale * (se-1);
            var superevent_div = this.format_block('jstpl_super_event', {id: id, x: xoff, scale: scale});
            return superevent_div;
        },

        /**
         * Remove previous superevent tile.
         */
        removeSuperEventTile: function() {
            var superevent = document.getElementById("superevent");
            if (superevent) {
                superevent.remove();
            }
        },

        /**
         * Show/Hide icons above palaces.
         * @param {boolean} true to show, else hide
         * @param {boolean} opt true to hide the ones over full palaces
         */
        displayPalaceSelectors: function(show, checkfull) {

            if (show) {
                const player_palace = document.getElementById('palaces_'+this.player_id);
                for (let palace of player_palace.getElementsByClassName("palace")) {
                    const choosepalace = document.getElementById('choose'+palace.id);
                    choosepalace.style['display'] = 'block';

                    if (checkfull) {
                        var size = 3;
                        for (let f of palace.getElementsByClassName("palacefloor")) {
                            if (f.style['display'] == 'none') {
                                size--;
                            }
                        }
                        var ppct = palace.getElementsByClassName("persontile").length;
     
                        if (ppct < size) {
                            choosepalace.style['opacity'] = 1;
                            choosepalace.style['cursor'] = 'pointer';
                        } else {
                            choosepalace.style['opacity'] = 0;
                            choosepalace.style['cursor'] = 'default';
                        }
                    } else {
                        choosepalace.style['opacity'] = 1;
                        choosepalace.style['cursor'] = 'pointer';
                    }
                }
            } else {
                // hide all arrows
                dojo.query( '#palaces_'+this.player_id+' .choosepalace' ).style( {'display': 'none', 'opacity' : 1, 'cursor': 'pointer'} );
            }
        },

        /**
         * Decorate person tiles to be placed with read border.
         * @param {int} type 
         * @param {int} level 
         */
        decoratePersonTileToPlace: function(type, level) {
            dojo.addClass( $('persontile_'+type+'_'+level), 'persontileToPlace' );
        },

        /**
         * Create span with Player's name in color.
         * @param {int} player 
         */
         spanPlayerName: function(player_id) {
            const player = this.gamedatas.players[player_id];
            const color_bg = player.color_back ?? "";
            const pname = "<span style=\"font-weight:bold;color:#" + player.color + ";" + color_bg + "\">" + player.name + "</span>";
            return pname;
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: '+stateName );
            
            switch( stateName ) {
                case 'initialChoice':
                    if( this.isCurrentPlayerActive() ) {
                        this.activatePersonTiles(true, 1);
                    }
                    break;
                case 'palaceFull':
                    this.decoratePersonTileToPlace(args.args.type, args.args.level);
                    this.activatePersonTiles(true, 0);
                    break;
                case 'initialPlace':
                    // fall-thru!
                case 'personPhasePlace':
                    this.decoratePersonTileToPlace(args.args.type, args.args.level);

                    if( this.isCurrentPlayerActive() ) {
                        this.displayPalaceSelectors(true, true);
                    }                
                    break;
                case 'actionPhaseChoose':
                    if (this.isCurrentPlayerActive()) {
                        const actioncards = document.getElementsByClassName('actioncard');
                        [...actioncards].forEach(a => a.classList.add('ityotd_action_active'));
                        $('actionscontainer').classList.add('ityotd_container_active');
                    }
                    break;
                case 'actionPhaseBuild':
                    if( this.isCurrentPlayerActive() ) {
                        // Insert a new "pseudo" palace
                        this.createNewPalace( this.player_id, 'new' );
                        this.displayPalaceSelectors(true);
                    }                
                    break;
                case 'reducePalace':
                    if( this.isCurrentPlayerActive() ) {
                        this.displayPalaceSelectors(true);
                    }
                    break;
                case 'discardPersonCards':
                    if( this.isCurrentPlayerActive() ) {
                        this.makePersonsDiscardable(true);
                    }
                    break;
                case 'actionBuildWall':
                    if( this.isCurrentPlayerActive() ) {
                        this.activateWallTiles(true);
                    }
                    break;
                case 'personPhaseChoosePerson':
                    if( this.isCurrentPlayerActive() ) {
                        this.activatePersonTiles(true, 2);
                    }
                    break;
                case 'sunriseRecruit':
                    if( this.isCurrentPlayerActive() ) {
                        this.activatePersonTiles(true, 1);
                    }
                    break;
                case 'release':
                    if( this.isCurrentPlayerActive() ) {
                        this.activatePersonTiles(true, 0);
                    }
                    break;
                case 'greatWallRelease':
                    if( this.isCurrentPlayerActive() ) {
                        this.activatePersonTiles(true, 0);
                    }
                    break;
                case 'dummmy':
                    break;
            }
        },

        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName ) {
                case 'initialChoice':
                    this.activatePersonTiles(false);
                    break;
                case 'initialPlace':
                    this.displayPalaceSelectors(false);
                    this.stripClass("persontileToPlace");
                    break;
                case 'personPhaseChoosePerson':
                    this.activatePersonTiles(false);
                    break;
                case 'personPhasePlace':
                    this.displayPalaceSelectors(false);
                    this.stripClass("persontileToPlace");
                    break;
                case 'palaceFull':
                    this.displayPalaceSelectors(false);
                    this.stripClass("persontileToPlace");
                    this.activatePersonTiles(false);
                    break;
                case 'actionPhaseChoose':
                    // remove the hover effects
                    this.stripClass("ityotd_action_active");
                    $('actionscontainer').classList.remove('ityotd_container_active');
                    break;
                case 'actionPhaseBuild':
                    this.displayPalaceSelectors(false);
                    this.removePalace( 'new' );
                    break;
                case 'reducePalace':
                    this.displayPalaceSelectors(false);
                    break;
                case 'discardPersonCards':
                    this.makePersonsDiscardable(false);
                    break;
                case 'actionBuildWall':
                    this.activateWallTiles(false);
                    break;
                case 'sunriseRecruit':
                    this.activatePersonTiles(false);
                    break;
                case 'release':
                    this.activatePersonTiles(false);
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
                    this.addActionButton( 'refillyuan', _('Take money (up to 3 yuan)'), 'onTakeUpMoney' ); 
                    break;
                case 'palaceFull':
                    this.addActionButton( 'noReplace', _('None ! Release the new one.'), 'onNoReplace' );                     
                    break;
                case 'reduceResources':
                    this.createResourceButtons();
                    break;
                }
            }
        },

        /**
         * Create div with buttons for resources
         * @returns div html
         */
        getResourceIcons: function(rice, fw, yn) {
            var rsrc_html = '<div id="yd_resources_div">';

            for (let r = 0; r < rice; r++) {
                rsrc_html += this.format_block('jstpl_rsrc_btn', {type: 'rice', i: r});
            }
            rsrc_html += '<span id="rice_top_mk"></span>';
            for (let f = 0; f < fw; f++) {
                rsrc_html += this.format_block('jstpl_rsrc_btn', {type: 'fw', i: f});
            }
            rsrc_html += '<span id="fw_top_mk"></span>';
            for (let y = 0; y < yn; y++) {
                rsrc_html += this.format_block('jstpl_rsrc_btn', {type: 'yuan', i: y});
            }
            rsrc_html += '<span id="yuan_top_mk"></span>'+
                        '</div>';
            return rsrc_html;
        },

        /**
         * Add buttons for removing resources.
         */
        createResourceButtons: function() {
            const main = $('pagemaintitletext');
            const res_rx = /choose (\d+) resource/;
            var toReduce = parseInt(main.innerHTML.match(res_rx)[1]);

            var player_id = this.player_id;
            var rice = toint( $('ricenbr_'+player_id).innerHTML);
            var fw = toint( $('fwnbr_'+player_id).innerHTML);
            var yn = toint( $('yuannbr_'+player_id).innerHTML);

            text = '<br/>'+
                   '<div style="display: flex; flex-direction: column; align-items: center;">';
            text += this.getResourceIcons(rice, fw, yn);
            text += '<br/>'+
                    '<div id="yd_rsrc_box"><span id="rice_bottom_mk"></span><span id="fw_bottom_mk"></span><span id="yuan_bottom_mk"></span></div>'+
                    '</div>';

            this.addToActionHeader(text);

            var rsrc_box = document.getElementById("yd_rsrc_box");
            rsrc_box.style["width"] = toReduce*27 + "px";

            // need to add tooltips to buttons
            this.addTooltipToClass( 'ttyuan', _('Yuan'), '' );
            this.addTooltipToClass( 'ttrice', _('Rice'), '' );
            this.addTooltipToClass( 'ttfw', _('Fireworks'), '' );

            this.addActionButton( 'reduceResource', _('Reduce Resources'), 'onRemoveResources' );
            var resources = this.getResourcesSelected();
            var total = resources["total"];

            if (total < toReduce) {
                $('reduceResource').classList.add("disabled");
            } else {
                $('reduceResource').classList.remove("disabled");
            }

            for (let r = 0; r < rice; r++) {
                this.createResourceButton(rsrc_box, "rice", r, toReduce);
            }
            for (let f = 0; f < fw; f++) {
                this.createResourceButton(rsrc_box, "fw", f, toReduce);
            }
            for (let y = 0; y < yn; y++) {
                this.createResourceButton(rsrc_box, "yuan", y, toReduce);
            }
        },

        /**
         * Create each button which will go to or from resource box.
         * @param {node*} rsrc_box 
         * @param {string} type rice|fw|yuan
         * @param {int} i 
         * @param {int} toReduce
         */
        createResourceButton: function(rsrc_box, type, i, toReduce) {
            var btn = document.getElementById(type+"_"+i+"_btn");
            btn.addEventListener("click", () => {
                let ibtn = document.getElementById(type+"_"+i+"_btn");
                // are we adding or removing a resource to remove?
                if (rsrc_box.contains(ibtn)) {
                    // deselecting resource to remove
                    $('yd_resources_div').insertBefore(ibtn, document.getElementById(type+"_top_mk"));
                    $('reduceResource').classList.add("disabled");
                } else {
                    var resources = this.getResourcesSelected();
                    var total = resources["total"];
                    // selecting resource to lose
                    if (total < toReduce) {
                        rsrc_box.insertBefore(ibtn, document.getElementById(type+"_bottom_mk"));
                        if (total === toReduce-1) {
                            $('reduceResource').classList.remove("disabled");
                        }
                    }
                }
            });
        },

        /**
         * 
         * @returns resources with rice|fw|yuan|total count
         */
        getResourcesSelected: function() {
            var rsrc_box = document.getElementById("yd_rsrc_box");
            var children = rsrc_box.children;
            var resources = {"rice" : 0, "fw": 0, "yuan": 0, "total": 0};
            for (c of children) {
                if (c.classList.contains("ttrice")) {
                    resources["rice"]++;
                    resources["total"]++;
                } else if (c.classList.contains("ttfw")) {
                    resources["fw"]++;
                    resources["total"]++;
                } else if (c.classList.contains("ttyuan")) {
                    resources["yuan"]++;
                    resources["total"]++;
                }
            }
            return resources;
        },

        ///////////////////////////////////////////////////
        //// Utility

        /**
         * Return array of gamedatas.players sorted in REVERSE play order
         * (last to act to first to act)
         */
        sortByPersonOrder: function() {
            const orderedplayers = [];
            for (let player_id in this.gamedatas.players) {
                orderedplayers.push(this.gamedatas.players[player_id]);
            }
            orderedplayers.sort((p1, p2) => {
                pp = toint(p1.person_score) - toint(p2.person_score);
                if (pp == 0) {
                    pp = toint(p1.person_score_order) - toint(p2.person_score_order);
                }
                return pp;
            });
            return orderedplayers;
        },

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

            const palace_person_tile = 'palacepersontile_'+person_id+'_inner';

            // Move this tile from corresponding stack
            this.placeOnObject( $(palace_person_tile), $('persontile_'+person_type+'_'+person_level) );
            this.slideToObject( $(palace_person_tile), $('palacepersontile_'+person_id) ).play();
       
            // Tool tip
            var persontype = this.gamedatas.person_types[ person_type ];
            var html = '<br>'+persontype.name+'</br><hr/>'+persontype.description;
            this.addTooltip( palace_person_tile, html, _('Release this person') );
            
            dojo.connect( $(palace_person_tile), 'onclick', this, 'onSelectPalacePerson' );
        },

        /**
         * Place an Action tile (each one is done sequentially).
         * @param {*} action_id
         * @param {*} action_type
         */
        placeAction: function( action_id, action_type )
        {
            dojo.place( this.format_block('jstpl_action', { 
                type: action_type
            } ), $('actionplace_'+action_id) ); 
            
            // Move animation
            this.placeOnObject( $('actioncard_'+action_type), $('actionplace_1') );
            this.slideToObject( $('actioncard_'+action_type), $('actionplace_'+action_id) ).play();
            
            // Tooltip
            var html = this.actionString(action_type);
            
            this.addTooltip( 'actioncard_'+action_type, html, _('Do this action') );
            
            dojo.connect( $('actioncard_'+action_type), 'onclick', this, 'onAction' );
        },

        /**
         * <b>Action Name</b>
         * <hr/>
         * Description
         * @param {int} action_type 
         * @returns 
         */
        actionString: function(action_type) {
            let html = '<b>'+this.gamedatas.action_types[action_type].name+'</b><hr/>';
            html += this.gamedatas.action_types[action_type].description;
            
            if( this.gamedatas.largePrivilegeCost == 7 && action_type == 7 )
            {
                html = html.replace( '6','7' );
            }
            return html;
        },
        
        /**
         * Player chose an action tile.
         * @param {int} player_id 
         * @param {int} action_id 
         */
        setActionChoice: function( player_id, action_id ) {
            if( action_id == null )
            {
                // No action => do nothing
            }
            else
            {
                // Place player flag on this action
                const flagsAlreadyThere = dojo.query( '#actioncard_'+action_id+' .actionflag' ).length;
                const bottom = flagsAlreadyThere*8;
                let zi = 10-flagsAlreadyThere;
                const flag_html = this.actionFlagHtml(player_id, bottom, zi);

                dojo.place(flag_html, 'actioncard_'+action_id );
            }
        },

        /**
         * For the person track score.
         * @param {*} player_id 
         * @param {*} person_score 
         * @param {*} person_score_order 
         */
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
            if (this.gamedatas.greatWall) {
                dojo.query( '.ityotd_nextwall' ).removeClass( 'ityotd_nextwall' );
                dojo.addClass( 'wall_'+month, 'ityotd_nextwall' );
            }
            
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

        /**
         * Decorate person cards to discardable or not discardable.
         * @param {bool} toDiscard 
         */
        makePersonsDiscardable: function(toDiscard) {
            const person_container = document.getElementById("personcards");
            const ppid_rx = /personcard_(\d+)/;
            if (toDiscard) {
                person_container.onclick = event => {
                    var card = event.target;
                    if (card.classList.contains("personcard")) {
                        var id = parseInt(card.id.match(ppid_rx)[1]);
                        this.discardPersonCard(id);
                    }
                };
            } else {
                person_container.onclick = null;
            }

            const personcards = document.getElementsByClassName("personcard");
            for (let pp of personcards) {
                if (toDiscard) {
                    pp.classList.add("ityotd_person_discard");
                } else {
                    pp.classList.remove("ityotd_person_discard");
                }
            }
        },

        /**
         * Decorate wall tiles to be selectable
         * @param {bool*} enable 
         */
        activateWallTiles: function(enable) {
            var player_id = this.player_id;
            const wall_container = document.getElementById("great_wall_"+player_id);
            const wallid_rx = /player_wall_.+_(\d+)/;

            if (enable) {
                wall_container.onclick = event => {
                    var wall_tile = event.target;
                    if (wall_tile.classList.contains("ityotd_wall") && !wall_tile.classList.contains("ityotd_wall_built")) {
                        var id = parseInt(wall_tile.id.match(wallid_rx)[1]);
                        this.onChooseWall(id);
                    }
                };
                const pcolor = '#'+this.gamedatas.players[ player_id ].color;
                wall_container.style['border'] = '4px dashed '+pcolor;
            } else {
                wall_container.onclick = null;
                wall_container.style['border'] = '';
            }
            const wall_tiles = wall_container.getElementsByClassName("ityotd_wall");
            for (let w of wall_tiles) {
                if (enable && !w.classList.contains("ityotd_wall_built")) {
                    w.classList.add("ityotd_wall_active");
                } else {
                    w.classList.remove("ityotd_wall_active");
                }
            }
        },

        /**
         * Decorate palace person tiles so it moves when you hover over it, or remove decoration.
         * @param {boolean} activate true to add, false to remove
         * @param {int} 0 if not recruiting, 1 if young people only, 2 if young or old
         */
        activatePersonTiles: function(activate, recruiting) {
            var persontiles = document.getElementsByClassName('persontile');
            if (!activate) {
                this.stripClass("ityotd_hvr_pers");
            } else {
                if (recruiting == 1) {
                    persontiles = Array.from(document.getElementById("persontiles").children).filter(p => p.id.endsWith("1"));
                } else if (recruiting == 2) {
                    persontiles = document.getElementById("persontiles").children;
                } else {
                    var player_palaces = document.getElementById("palaces_"+this.player_id);
                    if (player_palaces) {
                        persontiles = player_palaces.getElementsByClassName('persontile');
                    }
                }
                [...persontiles].forEach(pp => pp.classList.add("ityotd_hvr_pers"));
            }
        },

        /**
         * Strip all elements with this class name of the class
         */
        stripClass: function(classToStrip) {
            const elements = document.getElementsByClassName(classToStrip);
            [...elements].forEach(e => e.classList.remove(classToStrip));
        },

        ///////////////////////////////////////////////////
        //// UI

        /**
         * Create html with person tile.
         * @param {string} action
         * @param {string} level 
         * @param {string} type 
         * @returns html
         */
        personTileHtml: function(action, level, type) {
            const persontype = this.gamedatas.person_types[type];
            let person_str = '${persontype}';

            if (persontype.subtype[2]) {
                person_str = level == 1 ? _("Young ${persontype}") : _("Old ${persontype}");
            }
            const personname = this.gamedatas.person_types[type].name_sg;
            person_str = person_str.replace('${persontype}', personname);

            let act_str = _('${action} a ${person}?');
            act_str = act_str.replace('${action}', action);

            person_str = '<b>'+person_str+'</b>';
            act_str = act_str.replace('${person}', person_str);
            let div_html = act_str +
                            '<hr/>' +
                            '<div class="persontile persontile_'+type+'_'+level+'"></div>';
            return div_html;
        },

        /**
         * Create Action tile confirmation HTML.
         * @param {int} action_id 
         * @returns html
         */
        actionTileHtml: function(action_id) {
            let html = this.actionString(action_id)
                + '<div class="actioncard actioncard_'+action_id+'" style="margin: 5px;"></div>'+
                '<div style="height: 80px;"></div>';
            return html;
        },

        /**
         * Create HTML for stacked action flag.
         * @param {string} player_id 
         * @param {int} bottom 
         * @param {int} zi 
         * @returns flag html
         */
        actionFlagHtml: function(player_id, bottom, zi) {
            const flag_html = this.format_block('jstpl_actionflag', {
                id: player_id,
                color: this.gamedatas.players[ player_id ].color,
                b: bottom,
                z: zi,
            });
            return flag_html;
        },


        ///////////////////////////////////////////////////
        //// Action ajax calls

        onRecruit: function( evt )
        {
            console.log( 'onRecruit' );
            evt.preventDefault( );
            if( ! (this.isCurrentPlayerActive() && this.checkAction( 'recruit', true )) )
            {   
                return; 
            }

            const level = evt.currentTarget.id.substr( 13 );
            const type = evt.currentTarget.id.substr( 11, 1 );

            if (this.prefs[100].value == 2 || this.prefs[100].value == 5) {
                const person_html = this.personTileHtml(_('Recruit'), level, type);
                this.confirmationDialog( person_html, () => {this.recruitConfirmed(level, type)}, function() { return; });
            } else {
                // without confirmation dialog
                this.recruitConfirmed(level, type);
            }
        },

        /**
         * After confirmation, recruit Person of level, type
         * @param {string} level 
         * @param {string} type 
         */
        recruitConfirmed: function(level, type) {
            const isSunrise = this.gamedatas.gamestate.name == 'sunriseRecruit';
            // skip warning dialog  in case of sunrise recruit because php game logic will throw error message
            if(!isSunrise && toint( $('persontile_nbr_'+type+'_'+level).innerHTML ) == 0 )
            {
                this.confirmationDialog( _("There are no more persons of this type and your card will be discarded. Confirm?"),
                dojo.hitch( this, function() {
                    this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/recruit.html", {
                        lock: true, 
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
            // hack to disable selecting the arrows that have been made invisible
            if (evt.currentTarget.style['opacity'] == 0) {
                return;
            }

            // choosepalace_<id>
            var palace_id = evt.currentTarget.id.substr( 13 );

            if( this.checkAction('place', true))
            {
                // Place a tile
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/place.html", { 
                    lock: true, 
                    id: palace_id
                }, this, function( result ) {  } );             
            } 
            else if( this.checkAction('build', true))
            {
                console.log('build palace');
                // Build a floor
                if( palace_id == 'new' ) {   
                    palace_id = 0;  // Note: meaning = "new palace"
                }
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/build.html", { 
                    lock: true, 
                    id: palace_id   
                }, this, function( result ) {  } );             
            }
            else if (this.checkAction('reduce')) {
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/reduce.html", { 
                    lock: true, 
                    id: palace_id   
                }, this, function( result ) {  } );             

            }
        },
        
        onAction: function( evt )
        {
            console.log( 'onAction' );
            evt.preventDefault( );    

            if( ! (this.checkAction( 'action', true ) && this.isCurrentPlayerActive()) )
            {   return; }           
            
            // actioncard_<id>
            const action_id = evt.currentTarget.id.substr( 11 );

            if (this.prefs[100].value == 3 || this.prefs[100].value == 5) {
                const action_html = this.actionTileHtml(action_id);
                this.confirmationDialog( action_html, () => {this.actionConfirmed(action_id)}, function() { return; });
            } else {
                // without confirmation dialog
                this.actionConfirmed(action_id);
            }
        },

        /**
         * Submit action choice.
         * @param {int} action_id 
         */
        actionConfirmed: function(action_id) {
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/action.html", { 
                lock: true, 
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

        /**
         * Player choose "Take 3 yuan"
         * @returns 
         */
        onTakeUpMoney: function()
        {
            if( ! this.checkAction( 'refillyuan' ) )
            {   return; } 
            const yuans = $('yuannbr_'+this.player_id).innerHTML;
            if (toint(yuans) >= 3) {
                let yuanstr = _("You already have ${yuan} yuan, so this action will have no effect. Confirm?");
                yuanstr = yuanstr.replace('${yuan}', yuans);
                this.confirmationDialog( yuanstr,
                dojo.hitch( this, function() {
                    this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/refillyuan.html", {
                        lock: true, 
                    }, this, function( result ) {  } );             
                } ) );
            } else {
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/refillyuan.html", { lock: true
                }, this, function( result ) {  } );
            }
        },
        
        onSelectPalacePerson: function( evt )
        {
            evt.preventDefault();
            if( !this.isCurrentPlayerActive() ) {
                return;
            }

            // palacepersontile_${id}_inner
            var person_id = evt.currentTarget.id.substr( 17 );
            person_id = person_id.substr( 0, person_id.length-6 );

            if (this.prefs[100].value == 4 || this.prefs[100].value == 5) {
                const classname = evt.currentTarget.className;

                const match = classname.match(/.*persontile_(\d+)_(\d+).*/);
                if (match) {
                    var person_type = match[1];
                    var person_level = match[2];
                } else {
                    throw new Exception("Unknown person tile: "+className);
                }

                const state = this.gamedatas.gamestate.name;

                let action = '';
                if (state == 'release' || state == 'greatWallRelease' || state == 'reducePopulation') {
                    action = _('Release');
                } else if (state == 'palaceFull') {
                    action = _('Release and replace');
                } else if (state == 'charterPerson') {
                    action = _('Charter');
                }
                const select_html = this.personTileHtml(action, person_level, person_type);
                this.confirmationDialog( select_html, () => {this.confirmSelectPalacePerson(person_id, evt)}, function() { return; });
            } else {
                // without confirmation dialog
                this.confirmSelectPalacePerson(person_id, evt);
            }
         },


         /**
          * 
          * @param {string} person_id 
          * @param {Object} evt 
          * @returns 
          */
         confirmSelectPalacePerson: function(person_id, evt) {
            if( this.checkAction( 'releaseReplace', true ) )
            {
                // Special case: release a person to replace it immediately by a new one
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/releaseReplace.html", { lock: true,
                    id: person_id
                }, this, function( result ) {  } );                 
                return;
            } else if ( this.checkAction( 'release', true ) )
            {
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/release.html", { lock: true,
                    id: person_id
                }, this, function( result ) {  } );
            } else if ( this.checkAction('depopulate', true)) {
                this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/depopulate.html", { lock: true,
                    id: person_id
                }, this, function( result ) {  } );
            } else if ( this.checkAction('charter', true) ) {
                const person_rx = /persontile_(\d+)_/;
                var ptype = evt.currentTarget.className.match(person_rx)[1];
                if (ptype == 7) {
                    this.confirmationDialog( _("Chartering Healers has no effect: are you sure?"),
                    dojo.hitch( this, function() {
                        this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/charter.html", {
                            lock: true,
                            type: 7
                        }, this, function( result ) {  } );
                    } ) );
                } else {
                    this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/charter.html", {
                        lock: true,
                        type: ptype
                    }, this, function( result ) {  } );
                }
            }
         },

         onNoReplace: function( evt )
         {
            evt.preventDefault();
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/noReplace.html", { lock: true,
            }, this, function( result ) {  } );                 
            
         },

         /**
          * 
          * @param {*} evt 
          */
         onRemoveResources: function( evt ) {
            if( ! this.checkAction( 'removeResources' ) )
            {   return; } 
            var resources = this.getResourcesSelected();
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/removeResources.html", { 
                lock: true,
                rice: resources["rice"],
                fireworks: resources["fw"],
                yuan: resources["yuan"]
            }, this, function( result ) {  } );                 
         },
        
         /**
          * id for person card to discard.
          * @param {int} pp 
          */
          discardPersonCard: function( pp ) {
            if( ! this.checkAction( 'discard' ) )
            {   return; } 
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/discard.html", {
                lock: true,
                id: pp
            }, this, function( result ) {  } );                 
         },

         /**
          * Choose a Great Wall section to build.
          * @param {int} wall_id 
          */
         onChooseWall: function(wall_id) {
            if( ! this.checkAction( 'buildWall' ) )
            {   return; } 
            this.ajaxcall( "/intheyearofthedragonexp/intheyearofthedragonexp/buildWall.html", {
                lock: true,
                wall: wall_id
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
            dojo.subscribe( 'reducePalace', this, "notif_reducePalace" );
            dojo.subscribe( 'newMonth', this, "notif_newMonth" );
            dojo.subscribe( 'release', this, "notif_release" );
            dojo.subscribe( 'eventPayYuan', this, "notif_eventPayYuan" );
            dojo.subscribe( 'eventPayRice', this, "notif_eventPayRice" );
            dojo.subscribe( 'loseResources', this, "notif_loseResources" );

            dojo.subscribe( 'decay', this, "notif_decay" );
            dojo.subscribe( 'endOfTurnScoring', this, "notif_endOfTurnScoring" );
            dojo.subscribe( 'endOfGameScoring', this, "notif_endOfGameScoring" );

            dojo.subscribe( 'wallBuilt', this, "notif_wallBuilt");
            dojo.subscribe( 'superEventChosen', this, "notif_superEventChosen");
            dojo.subscribe( 'superEvent', this, 'notif_superEvent');
        },
        
        notif_placePerson: function( notif )
        {
            console.log( 'notif_placePerson' );
            console.log( notif );
            
            this.addPersonToPalace( notif.args.palace_id, notif.args.person_id, notif.args.person_type, notif.args.person_level );
            
            var tile_id = notif.args.person_type+'_'+notif.args.person_level;
            $('persontile_nbr_'+tile_id).innerHTML = toint( $('persontile_nbr_'+tile_id).innerHTML ) - 1;
            if( toint( $('persontile_nbr_'+tile_id).innerHTML ) == 0 ) {
                this.fadeOutAndDestroy( 'persontile_'+tile_id );
            }
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
                this.placeAction( action_id, type );
                action_id ++;
            }
        },

        /**
         * Player chose an action.
         * @param {Object} notif 
         */
        notif_actionChoice: function( notif ) {
            console.log( 'notif_actionChoice' );
            console.log( notif );

            const player_id = notif.args.player_id;
            const action_id = notif.args.action_id;

            const prevFlags = $('actioncard_'+action_id).children;
            for (let f = 0; f < prevFlags.length; f++) {
                const flag = prevFlags[f];
                flag.style["bottom"] = ((f+1)*8)+"px";
                flag.style["z-index"] = 10-f;
            }

            const flag_html = this.actionFlagHtml(player_id, 0, 10);
            this.slideTemporaryObject(flag_html, 'overall_player_board_'+player_id, 'player_board_'+player_id, 'actioncard_'+action_id, 500, 0).play();
            dojo.place(flag_html, $('actioncard_'+action_id));

            // change yuan if paid for action
            if( toint( notif.args.pay ) > 0 ) {
                $('yuannbr_'+player_id).innerHTML = ( toint( $('yuannbr_'+player_id).innerHTML ) - 3 );
            }
        },

        notif_usePersonCard: function( notif )
        {
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
            this.scoreCtrl[ notif.args.player_id ].incValue( parseInt(notif.args.nbr) );
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

        notif_reducePalace: function(notif) {
            console.log( 'notif_reducePalace' );
            console.log( notif );
            var palace_id = notif.args.reduce;
            var newsize = parseInt(notif.args.size);

            if (newsize == 0) {
                this.removePalace( palace_id );
            } else {
                this.removeFloorToPalace( palace_id );
            }
        },

        notif_newMonth:function( notif )
        {
            console.log( 'notif_newMonth' );
            console.log( notif );
            this.setCurrentMonth( notif.args.month );
        },
        notif_release: function( notif )
        {
            console.log( 'notif_release' );
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
            if (this.gamedatas.superEvent) {
                var month = parseInt(notif.args.month);
                if (month == 7) {
                    this.removeSuperEventTile();
                    var event_7 = document.getElementById("event_7");
                    var fin_event_div = this.createSuperEventTile("superevent", 12, 0.3);
                    dojo.place(fin_event_div, event_7);
                    const se_lbl = document.getElementById("superevent_label");
                    se_lbl.style['opacity'] = 0.5;
                }
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
        },

        /**
         * A wall section was built.
         * @param {Object} notif 
         */
        notif_wallBuilt: function(notif) {
            const newSec = parseInt(notif.args.length);
            const player_id = notif.args.player_id;
            const bonus = parseInt(notif.args.bonus);

            const pcolor = this.gamedatas.players[ player_id ].color;
            const xoff = -60*(COLORS_PLAYER[pcolor]-1);
            const wall_tile = this.format_block('jstpl_player_wall', {id: player_id, type: 'temp', x: xoff, y: 0});
            this.slideTemporaryObject( wall_tile, 'player_board_'+player_id, 'player_wall_'+player_id+'_'+bonus, 'wall_'+newSec, 1000, 1000 ).play();
            this.placeWallTile(player_id, newSec, bonus);
            this.decorateUnbuiltWallSections(newSec);
        },

        /**
         * Flood SuperEvent.
         * @param {Object*} notif 
         */
        notif_loseResources: function(notif) {
            var player_id = notif.args.player_id;
            var rice = parseInt(notif.args.nbrrice);
            var fw = parseInt(notif.args.nbrfw);
            var yuan = parseInt(notif.args.nbryuan);
            if (rice > 0) {
                $('ricenbr_'+player_id).innerHTML = ( toint( $('ricenbr_'+player_id).innerHTML ) - rice );
            }
            if (fw > 0) {
                $('fwnbr_'+player_id).innerHTML = ( toint( $('fwnbr_'+player_id).innerHTML ) - fw );
            }
            if (yuan > 0) {
                $('yuannbr_'+player_id).innerHTML = ( toint( $('yuannbr_'+player_id).innerHTML ) - yuan );
            }
        },

        /**
         * This happens in HARD MODE games where super event is revealed only on turn 7.
         * Remove the hidden tile, replace with the chosen one.
         * @param {Object} notif 
         */
        notif_superEventChosen: function(notif) {
            const superevent = parseInt(notif.args.superevent);
            this.removeSuperEventTile();
            this.placeSuperEvent(superevent);
        },

        /**
         * When a SuperEvent happens - only need to check for assassination to change privileges
         */
        notif_superEvent: function(notif) {
            const event = parseInt(notif.args.superevent);
            if (event == 9) {
                // Assassination
                for( let player_id in this.gamedatas.players ) {
                    $('privnbr_'+player_id).innerHTML = 0;
                }
            }
        },
  });      
});