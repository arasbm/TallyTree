<?php
/*
Plugin Name: TallyTree
Plugin URI: http://www.tallytree.com
Description: 
Version: 0.1
Author: Lu Di, Elyse Regan, and Aras Balali Moghaddam
Author URI: http//www.tallytree.com/authors/
License: GPL3
*/

?>
<?php
/*
Copyright (C) 2011  TallyTree Authors

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>


<?php
wp_enqueue_script("jquery");
wp_enqueue_script("raphael", "http://yandex.st/raphael/2.0/raphael.min.js");
wp_enqueue_script("springy_tallytree", WP_PLUGIN_URL."/tally-tree/script/springy_tallytree.js", array("jquery","raphael"), "", false);
wp_enqueue_script("ajaxSubmit",  WP_PLUGIN_URL."/tally-tree/script/ajaxSubmit.js", false);
wp_enqueue_script('ajaxVoteThis', WP_PLUGIN_URL.'/tally-tree/script/ajaxVoteThis.js');
wp_enqueue_script('iLikeThis', WP_PLUGIN_URL.'/tally-tree/script/ajaxVoteThis.js');
 
register_activation_hook(__FILE__,array('TallyTree','init_tallytree'));
//add_action('wp_insert_post',array('TallyTree','create_new_tallytree'));
add_shortcode( 'tallytree', array('TallyTree','tallytree_shortcode') );
add_action( 'comment_form', array('TallyTree', 'comment_form_buttons') );
//add_filter( 'comment_form_defaults', array('TallyTree', 'change_submit_buttons') );
add_action( 'wp_print_styles', 'add_my_stylesheet');
// Save vote to meta
add_action( 'comment_post', array('TallyTree', 'save_init_response') );
add_action( 'comment_text', array('TallyTree', 'display_vote_btn_and_count') );
//add_filter('the_content', array( 'TallyTree', 'putILikeThis'));
//add_action('wp_head', array('TallyTree', 'addHeaderLinks'));

function add_my_stylesheet() {
	$myStyleUrl = plugins_url('css/tallystyle.css', __FILE__);
   $myStyleFile = WP_PLUGIN_DIR . '/tally-tree/css/tallystyle.css';
   if ( file_exists($myStyleFile) ) {
   	wp_register_style('myStyleSheets', $myStyleUrl);
      wp_enqueue_style( 'myStyleSheets');
   }
   
}

class TallyTree
{	
	/**
	* This function creates the database tables needed by tallytree
	* It is called on plugin activation
	*/
	function init_tallytree()
	{
		echo "<b> initializing ... </b>";
		global $wpdb;
		//TODO: global $tallytree_version;
		
		$main_table_name = $wpdb->prefix . "tallytree_main";
		$votes_table_name = $wpdb->prefix . "tallytree_votes";
		$comments_table_name = $wpdb->prefix . "tallytree_comments";
		
		if ($wpdb->get_var("show tables like '$main_table_name'") != $main_table_name) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $main_table_name . " (
				post_id int  NOT NULL  PRIMARY KEY,
				left_text varchar(255)  NOT NULL,
				right_text varchar(255)  NOT NULL,
				question varchar(255)  NOT NULL,
				num_left_vote int DEFAULT 0  NOT NULL,
				num_right_vote int DEFAULT 0  NOT NULL,
				statue_path varchar(255)  NOT NULL,
				min_num_voter int DEFAULT 10  NOT NULL,
				fall_percentage float DEFAULT 70.0  NOT NULL
				);";
			echo "Creating Tables:";
	      maybe_create_table($main_table_name, $sql);
      }
      
      //if ($wpdb->get_var("show tables like '$votes_table_name'") != $votes_table_name) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');		
			$sql = "CREATE TABLE IF NOT EXISTS " . $votes_table_name . " (
			comment_id int  NOT NULL,
			voter_id int  NOT NULL
			);";
      	//maybe_create_table($votes_table_name, $sql);
      	dbDelta($sql);
      //}	
		
		if ($wpdb->get_var("show tables like '$comments_table_name'") != $comments_table_name) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = "CREATE TABLE " . $comments_table_name . " (
			comment_id int  NOT NULL,
			comment_side varchar(255)  NOT NULL,
			comment_vote_count int  NOT NULL
			);
			";      
      	maybe_create_table($comments_table_name, $sql);
      }  
      add_option("tallytree_db_version", "1.0");
	}
	
    /**
    * this function sets up the vote for this button and returns its html code
    */
	function get_vote_btn() {
		global $wpdb;
		$post_ID = get_the_ID();
		$ip = $_SERVER['REMOTE_ADDR'];
		//echo "<b>Post ID: " . $post_ID . "</b><br />";
		//echo "IPIPIPIPIP: " . $ip;
		$comment_ID = get_comment_ID();
		$user = get_current_user();
		$voter_id = $user['ID'];
		
	   //$liked = get_post_meta($post_ID, '_liked', true) != '' ? get_post_meta($post_ID, '_liked', true) : '0';
	   //echo "<br />LIKED:" . $liked;
	   
	   // If the voter has the right to vote, then display the voter button
	   // TODO: check if current user has not voted (if stmt) 
	   
		//$voteStatusByIp = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ilikethis_votes WHERE post_id = '$post_ID' AND ip = '$ip'");	
	   // if (!isset($_COOKIE['liked-'.$post_ID]) && $voteStatusByIp == 0) {
	   // 	if (get_option('ilt_textOrImage') == 'image') {
	   // 	}
	   // 	else {
	   // 		$counter = $liked.' <a onclick="likeThis('.$post_ID.');">'.get_option('ilt_text').'</a>';
	   // 	}
	   // }
	   //else {
	    //	$counter = $liked;
	   // }
	   
	   // Display number of votes, for this comment
	    $vote_button = "";
	    $vote_count = self::get_comment_vote_count( $comment_ID );
	    if ($vote_count == 0 ) { 
            $vote_button .= '<div class="numVotes"> no votes </div>';
        } elseif ($vote_count == 1) {
            $vote_button .= '<div class="numVotes"> one vote </div>';
        } else {
            $vote_button .= '<div class="numVotes"> ' . $vote_count . ' votes </div>';
        }
	    $vote_button .= '<div id="iLikeThis-'.$comment_ID.'" class="iLikeThis">';
	    $vote_button .= '<div class="counter"><a title="You only have one vote, so choose carefully!" onclick="likeThis('.$post_ID.','.$comment_ID.');" class="image"></a></div>';
	    $vote_button .= '</div>';
		return $vote_button;
	}

	function addHeaderLinks() {
		echo '<link rel="stylesheet" type="text/css" href="'.WP_PLUGIN_URL.'/tally-tree/css/tallystyle.css" media="screen" />'."\n";
		echo '<script type="text/javascript">var blogUrl = \''.get_bloginfo('wpurl').'\'</script>'."\n";
	}
	
	/**
    * Saves the vote type for the given comment ID to the comment meta table 
    * @param int $comment_id
    */
	function save_init_response( $comment_id ) {
		add_comment_meta($comment_id, 'tally-vote', $_POST['tally-vote'], true);
		//$user_id = get_current_user()->ID;
		$side = get_comment_meta($comment_id, 'tally-vote', true);
		//Check what side button was pressed
		if( stristr($side, 'postleft' ) ) {
			self::add_comment($comment_id, 'left');
		} elseif( stristr( $side, 'postright' ) ) {
			self::add_comment($comment_id, 'right');
		} else {
			//neutral comment
		}
	}	
	
	/**
	* Attached to 'comment_post' action. This function is responsible for adding
	* a vote button to comments if needed, and to display a vote count.
	*/
	function display_vote_btn_and_count($comment) {
		global $wpdb, $post;
		$comment_id = get_comment_ID();
		$current_user = wp_get_current_user();
		//echo "comment_id: " . $comment_id . " current user: " . $current_user->ID;
		
		//add vote type icon to the comment
		$votebutton = get_comment_meta($comment_id, 'tally-vote', true);
		//$comment_side = self::get_comment_side( $comment_id );
/*		
		if ($comment_side == 'left') {
			
		} elseif( $comment_side == 'right' ) {
			
		} else {
			//neutral
					
		}
		*/
		
		$comment .= '<div class="voteType">'. $votebutton . '</div>';
			
		//echo "can vote? = " . self::user_can_vote($current_user->ID, $post->ID);
		if($current_user->ID < 1) {
			return $comment . "<div style = 'float:centre'>Please <a href='http://goliath.cs.uvic.ca/playground/wordpress/wp-login.php?action=register'> Register</a> and<a href='http://goliath.cs.uvic.ca/playground/wordpress/wp-login.php'> Log in</a> to vote. Thanks!</div><br />";
		} elseif( self::user_can_vote($current_user->ID, $post->ID) ) {
		    //make sure this comment is votable
		    if ( self::comment_is_votable( $comment_id ) ) {
			    //display the "Vote for this" button
                return $comment . self::get_vote_btn();
            } else {
                //this is a neutral comment
                return $comment;
            }
		} else {
			//user have already voted. We just need to check if this user voted for this post, if so we display a checkmark
			if ( self::user_vote_comment( $current_user->ID, $comment_id ) ) {
				$comment .= "<div style = 'float:right'><img src='http://goliath.cs.uvic.ca/playground/wordpress/wp-content/plugins/tally-tree/img/check_icon.png' alt='You have voted for this comment' /></div>";
			}	
			return $comment;
		}
		
		/*
	   echo 'Username: ' . $current_user->user_login . '<br />';
	   echo 'User email: ' . $current_user->user_email . '<br />';
	   echo 'User first name: ' . $current_user->user_firstname . '<br />';
	   echo 'User last name: ' . $current_user->user_lastname . '<br />';
	   echo 'User display name: ' . $current_user->display_name . '<br />';
	   echo 'User ID: ' . $current_user->ID . '<br />';
	   */
	   // echo self::get_vote_btn('put');
	}
	
	/**
	* if comment is of 'left' or 'right' type return true otherwise return false
	*/
	function comment_is_votable( $comment_id ) {
	    global $wpdb;
	    $sql = "
	    SELECT count(*) FROM " . $wpdb->prefix . "tallytree_comments WHERE comment_id = " . $comment_id;
	    $result = $wpdb->get_var( $sql );
	    return ($result > 0 );
	}
	
	/**
	* returns true of user voted for this comment otherwise return false 
	*/
	function user_vote_comment( $user_id, $comment_id) {
	    global $wpdb;
	    $sql = "
	    SELECT count(*) FROM " . $wpdb->prefix . "tallytree_votes WHERE voter_id = " . $user_id . " AND comment_id = " . $comment_id;
	    $result = $wpdb->get_var( $sql );
	    return ($result > 0 );
	}
	
	/**
	* returns true if current user (with user_id) has not voted in this post, returns false otherwise
	*/
	function user_can_vote($user_id, $post_id) {	
		global $wpdb;
		$sql = '
		SELECT count(*) as count, comment_id, voter_id
        FROM ' . $wpdb->prefix . 'tallytree_votes
        WHERE 
        voter_id = ' . $user_id . ' AND 
        (comment_id)
        IN (
        SELECT comment_ID
        FROM ' . $wpdb->prefix . 'comments
        WHERE comment_post_ID = ' . $post_id . '
        )';
		
		$result = $wpdb->get_row( $sql );
		
		if ( $result->count > 0 ) {
	       return FALSE;		
		} else {
		    return TRUE;
		}
	}
	
	/**
	* return the number of votes for this particular comment
	*/
	function get_comment_vote_count($comment_id) {
		global $wpdb;
        $sql = "
        SELECT comment_vote_count 
        FROM " . $wpdb->prefix . "tallytree_comments 
        WHERE comment_id = " . $comment_id;
		return $wpdb->get_var( $sql );
	}
	
	/**
	* return the total votes this user has received on her votable comments
	*/
	function get_user_vote_count($user_id, $post_id) {
		global $wpdb;
		$total_votes = 0;
		$sql = '
		SELECT comment_id 
        FROM ' . $wpdb->prefix . 'tallytree_votes
        WHERE 
        voter_id = ' . $user_id . ' AND 
        (comment_id)
        IN (
        SELECT comment_ID
        FROM ' . $wpdb->prefix . 'comments
        WHERE comment_post_ID = ' . $post_id . '
        )';
		$result = $wpdb->get_results( $sql );
		foreach ( $result as $row ) {
		    $total_votes += self::get_comment_vote_count( $row->comment_id );
		}
		return $total_votes;
	}
	
	/**
	* This function is called when post is saved or published and it contains 
	* a tallytree shortcode
	*/
	function create_new_tallytree( $post_id, $left_text, $right_text, $question, 
									$statue_path, $min_num_voter, $fall_percentage )
	{
	    //verify the post is not a revision
		if ( !self::has_tallytree($post_id) ){
			//$post
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . "tallytree_main", 
							array(
									'post_id' => $post_id,
									'left_text' => $left_text,
									'right_text' => $right_text,
									'question' => $question,
									'statue_path' => $statue_path,
									'min_num_voter' => $min_num_voter,
									'fall_percentage' => $fall_percentage
									),
							array(
									'%d',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%f'
									)
							);	
			
		}
	
	}
	
	/**
	* Add a new row to the comment table containing information for a new comment
	*/
	function add_comment($comment_id, $comment_side) {
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . "tallytree_comments", 
							array(
									'comment_id' => $comment_id,
									'comment_side' => $comment_side,
									'comment_vote_count' => 0
									),
							array(
									'%d',
									'%s',
									'%d'
								   )
							);
	}
	
	/**
	* Store the vote information whenever someone votes for a non-neutral post 
	* votes are stored in the "wp_tallytree_votes" table
	*/
	function add_vote($comment_id, $voter_id, $post_id) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . "tallytree_votes",
						array(
								'comment_id' => $comment_id,
								'voter_id' => $voter_id
						),
						array(
								'%d',
								'%d'
						)
				);
				
		//get the vote count
		$vote_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "tallytree_votes WHERE comment_id = " . $comment_id ) + 1 ;
		
		//update the comment table
		$wpdb->update( $wpdb->prefix . "tallytree_comments",
					array(
							'comment_vote_count' => $vote_count
					),
					array(
							'comment_id' => $comment_id
					),
					array(
							'%d' 
					),
					array(
							'%d'					
					)
				);
				
		//get the current vote counts and increment one accordingly
	    $total_left_vote_count = $wpdb->get_var("SELECT num_left_vote FROM " . $wpdb->prefix . "tallytree_main WHERE post_id = " . $post_id );
	    $total_right_vote_count = $wpdb->get_var("SELECT num_right_vote FROM " . $wpdb->prefix . "tallytree_main WHERE post_id = " . $post_id );
		if ( self::get_comment_side( $comment_id ) == 'left' ) {
    		$total_left_vote_count += 1;
	    } elseif ( self::get_comment_side( $comment_id ) == 'right' ) {
	        $total_right_vote_count += 1;
	    } else {
	        //not a votable comment so this should never happen
	    }	
		//update the main table with new vote count
	    $wpdb->update( $wpdb->prefix . "tallytree_main",
	                array(
							'num_left_vote' => $total_left_vote_count,
							'num_right_vote' => $total_right_vote_count
					),
					array(
							'post_id' => $post_id
					),
					array(
							'%d',
							'%d'
					),
					array(
							'%d'					
					)
				);
	}
	
	//function change_submit_buttons( $defaults ) {
	//	$defaults['label_submit'] = __('For Right');
	//	$defaults['id_submit'] = 'right_submit';
		
	//	return $defaults;
	//	}
	/**
	* Modify the comment to add our custom left and right buttons
	* Only modify the comment form if this post has a tallytree
	*/	
	function comment_form_buttons($post_id) {
		if ( self::has_tallytree($post_id) ) {	
			echo "<div class='vote-buttons-group'><div id='left' class='vote-buttons'><a title='Left-leaning Comment'/><img  src='http://goliath.cs.uvic.ca/playground/wordpress/wp-content/plugins/tally-tree/img/postleft_icon.png' height='18px'/></a></div>
			<div class='vote-buttons'><a title='Neutral Comment'/><img  src='' alt='Neutral Comment' title='Neutral Comment' height='20px'/></a></div>
			<div class='vote-buttons'><a title='Right-leaning Comment'/><img  src='http://goliath.cs.uvic.ca/playground/wordpress/wp-content/plugins/tally-tree/img/postright_icon.png' height='18px'/></a></div>
			</div>		
			<br /><div style='color:#777777;'><i> Please make sure you have <u>thoroughly</u> clicked the comment type button before you post!</i></div>	
			";
		}
	}
	
	/**
	* Returns true if the post contains a tallytree and return false otherwise
	*/	
	function has_tallytree($post_id) {
		global $wpdb;
		$count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "tallytree_main WHERE post_id = " . $post_id ) ;
		return ( $count > 0 );
	}
	
	/*
	* This function is responsible for extracting shortcode from post
	* and creating a tallytree record in the database if necessary. It
	* will then return the code for tallytree visualization to replace 
	* the shortcode
	*/ 
	function tallytree_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
				'post_id' => 0,
				'left_text' => 'Yes!',
				'right_text' => 'No!',
				'question' => 'Is this a controversial question?',
				'statue_path' => 'king.png',
				'min_num_voter' =>  100,
				'fall_percentage' => 70,
				), $atts));
				
		self::create_new_tallytree( $post_id, $left_text, $right_text, $question, 
							$statue_path, $min_num_voter, $fall_percentage);
			
		return self::get_pre_graph_code() . self::get_graph_code($post_id) . self::get_post_graph_code($post_id); 
	}
	
	/**
	* this function returns the javascript and html code that needs to be executed before graph
	* is drown to setup connections and bazier curves etc. 
	*/
	function get_pre_graph_code() {
		return "
<script>
Raphael.fn.connection = function (obj1, obj2, style) {
    var selfRef = this;
    /* create and return new connection */
    var edge = {/*
        from : obj1,
        to : obj2,
        style : style,*/
        draw : function() {
            /* get bounding boxes of target and source */
            var bb1 = obj1.getBBox();
            var bb2 = obj2.getBBox();
            var off1 = 0;
            var off2 = 0;
            
            /* coordinates for potential connection coordinates from/to the objects */
            var p = [
                {x: bb1.x + bb1.width / 2, y: bb1.y - off1},              /* NORTH 1 */
                {x: bb1.x + bb1.width / 2, y: bb1.y + bb1.height + off1}, /* SOUTH 1 */
                {x: bb1.x - off1, y: bb1.y + bb1.height / 2},             /* WEST  1 */
                {x: bb1.x + bb1.width + off1, y: bb1.y + bb1.height / 2}, /* EAST  1 */
                {x: bb2.x + bb2.width / 2, y: bb2.y - off2},              /* NORTH 2 */
                {x: bb2.x + bb2.width / 2, y: bb2.y + bb2.height + off2}, /* SOUTH 2 */
                {x: bb2.x - off2, y: bb2.y + bb2.height / 2},             /* WEST  2 */
                {x: bb2.x + bb2.width + off2, y: bb2.y + bb2.height / 2}  /* EAST  2 */
            ];
            
            /* distances between objects and according coordinates connection */
            var d = {}, dis = [];

            /*
             * find out the best connection coordinates by trying all possible ways
             */
            /* loop the first object's connection coordinates */
            for (var i = 0; i < 4; i++) {
                /* loop the seond object's connection coordinates */
                for (var j = 4; j < 8; j++) {
                    var dx = Math.abs(p[i].x - p[j].x),
                        dy = Math.abs(p[i].y - p[j].y);
                    if ((i == j - 4) || (((i != 3 && j != 6) || p[i].x < p[j].x) && ((i != 2 && j != 7) || p[i].x > p[j].x) && ((i != 0 && j != 5) || p[i].y > p[j].y) && ((i != 1 && j != 4) || p[i].y < p[j].y))) {
                        dis.push(dx + dy);
                        d[dis[dis.length - 1].toFixed(3)] = [i, j];
                    }
                }
            }
            
            var res = dis.length == 0 ? [0, 4] : d[Math.min.apply(Math, dis).toFixed(3)];
            /* bezier path is modifies for tallytree so lines go from center of nodes to nodes */
            var x1 = bb1.x + bb1.width / 2;
                y1 = bb1.y + bb1.height / 2; 
                x4 = bb2.x + bb2.width / 2; 
                y4 = bb2.y + bb2.height / 2;
                dx = Math.max(Math.abs(x1 - x4) / 2, 10),
                dy = Math.max(Math.abs(y1 - y4) / 2, 10),
                x2 = [x1, x1, x1 - dx, x1 + dx][res[0]].toFixed(3),
                y2 = [y1 - dy, y1 + dy, y1, y1][res[0]].toFixed(3),
                x3 = [0, 0, 0, 0, x4, x4, x4 - dx, x4 + dx][res[1]].toFixed(3),
                y3 = [0, 0, 0, 0, y1 + dy, y1 - dy, y4, y4][res[1]].toFixed(3);
            /* assemble path and arrow */
            var path = ['M', x1.toFixed(3), y1.toFixed(3), 'C', x2, y2, x3, y3, x4.toFixed(3), y4.toFixed(3)].join(',');
            /* arrow */
            if(style && style.directed) {
                /* magnitude, length of the last path vector */
                var mag = Math.sqrt((y4 - y3) * (y4 - y3) + (x4 - x3) * (x4 - x3));
                /* vector normalisation to specified length  */
                var norm = function(x,l){return (-x*(l||5)/mag);};
                /* calculate array coordinates (two lines orthogonal to the path vector) */
                var arr = [
                    {x:(norm(x4-x3)+norm(y4-y3)+x4).toFixed(3), y:(norm(y4-y3)+norm(x4-x3)+y4).toFixed(3)},
                    {x:(norm(x4-x3)-norm(y4-y3)+x4).toFixed(3), y:(norm(y4-y3)-norm(x4-x3)+y4).toFixed(3)}
                ];
                path = path + ',M'+arr[0].x+','+arr[0].y+',L'+x4+','+y4+',L'+arr[1].x+','+arr[1].y; 
            }
            /* function to be used for moving existent path(s), e.g. animate() or attr() */
            var move = 'attr';
            /* applying path(s) */
            edge.fg && edge.fg[move]({path:path}) 
                || (edge.fg = selfRef.path(path).attr({stroke: style && style.stroke || '#000', fill: 'none'}).toBack());
            edge.bg && edge.bg[move]({path:path})
                || style && style.fill && (edge.bg = style.fill.split && selfRef.path(path).attr({stroke: style.fill.split('|')[0], fill: 'none', 'stroke-width': style.fill.split('|')[1] || 3}).toBack());
            /* setting label */
            style && style.label 
                && (edge.label && edge.label.attr({x:(x1+x4)/2, y:(y1+y4)/2}) 
                    || (edge.label = selfRef.text((x1+x4)/2, (y1+y4)/2, style.label).attr({fill: '#000', 'font-size': style['font-size'] || '12px'})));
            style && style.label && style['label-style'] && edge.label && edge.label.attr(style['label-style']);
            style && style.callback && style.callback(edge);
        }
    }
    edge.draw();
    return edge;
};
</script>";
	}
	
	/**
	* This function iterates through the tallytree comment and votes data and returns 
	* the graph nodes and edges to be used inside the javascript code for drawing the visualization 
	*/
	function get_graph_data($post_id) {
		global $wpdb;
		$image_path = WP_PLUGIN_URL . "/tally-tree/img/";
		$return_nodes = "";
		$return_edges = "";
		//Insert the root node
		//example: var root = graph.newNode({label: 'root', side: 'root', 
		//img: '". $image_path . "king.png', width: 110, height: 180, angle: -30});
		$sql = "SELECT left_text, right_text, question, num_left_vote, num_right_vote, 
		statue_path, min_num_voter, fall_percentage FROM " . $wpdb->prefix . "tallytree_main 
		WHERE post_id = " . $post_id;
		$result = $wpdb->get_row($sql, ARRAY_A);
		//var_dump($result);
		$return_nodes .= "
		var root = graph.newNode({label: 'root', side: 'root', img: '". $image_path . $result['statue_path'] . "', width: 110, height: 180, angle: " . 
		self::get_root_angle( $result['num_left_vote'], $result['num_right_vote'], $result['fall_percentage'], $result['min_num_voter'] ) . "});";
	
		//now iterate through data and echo all the rest of nodes and their links
		//example: graph.newEdge(ludi, elyse, {color: '#CC333F'});
		$sql = "SELECT comment_id, voter_id 
		FROM " . $wpdb->prefix . "tallytree_votes 
		WHERE ((SELECT comment_post_ID 
					FROM " . $wpdb->prefix . "comments 
					WHERE " . $wpdb->prefix . "comments.comment_ID = " . $wpdb->prefix . "tallytree_votes.comment_id)  = ". $post_id .")";	
		$result = $wpdb->get_results($sql);
		//first iterate through votes for this post
		foreach ( $result as $row ) {
			//Check to see if user has voted for themselves, if so add a node for them and connect them to the root	
			$node1_id = "node_" . $row->voter_id;
			$node2_id = "node_" . self::get_comment_owner_id( $row->comment_id );
			$user_info = self::get_user_info( $row->voter_id );
			$avatar = simplexml_load_string( get_avatar( $row->voter_id ) );
			if ( $node1_id == $node2_id ) {
				//connect this node to root
				$return_nodes .= "
				var " . $node1_id . " = graph.newNode({label: \"" . $user_info->user_nicename . "\", side: \"" . self::get_comment_side($row->comment_id) . "\", img: \"" . $avatar['src'] . "\", width: 32, height: 32, votes: " . self::get_user_vote_count( $row->voter_id, $post_id ) . ", comment1: \"" . self::get_comment_content( $row->comment_id ) . "\", comment1Date: \"\" });";
				$return_edges .= "
				graph.newEdge( " . $node1_id . ", root, {color: '#CC333F'});" ;
			} else {
				//connect this node to the node_user who made the comment that this user voted for
				$last_comment = self::get_latest_comment( $row->voter_id, $post_id );
				$return_nodes .= "
				var " . $node1_id . " = graph.newNode({label: '" . $user_info->user_nicename . "', side: '" . self::get_comment_side($row->comment_id) . "', img: \"" . $avatar['src'] . "\", width: 32, height: 32, votes: " . self::get_user_vote_count($row->voter_id, $post_id) . ", comment1: '" . $last_comment->comment_content . "', comment1Date: '" . $last_comment->comment_date . "'});";
				$return_edges .=  "
				graph.newEdge( " . $node1_id . ", " . $node2_id . ", {color: '#BB223F'});";
			}				
		}
		
		return $return_nodes . $return_edges;
	}
	
	/**
	* returns the content of this comment
	*/
	function get_comment_content( $comment_id ) {
	   global $wpdb;
		$sql = "SELECT comment_content FROM " .$wpdb->prefix . "comments 
					WHERE comment_ID = " . $comment_id;
		return $wpdb->get_var($sql);
	}
	
	/**
	* return the latest comment of this user on a particular post as a row
	*/
	function get_latest_comment( $user_id, $post_id ) {
	    global $wpdb;
	    $sql = "
	    SELECT comment_author_url, comment_date, comment_content, comment_parent 
	    FROM " .$wpdb->prefix . "comments 
	    WHERE user_id = " . $user_id . " AND comment_post_ID = " . $post_id . " 
	    ORDER BY comment_date";
        //NOTE: there may be more than one row or not rows available
		return $wpdb->get_row($sql);
	}

	/**
	* Return the id of user who wrote this comment
	*/
	function get_comment_owner_id( $comment_id ) {
		global $wpdb;
		$sql = "SELECT user_id FROM " .$wpdb->prefix . "comments 
					WHERE comment_ID = " . $comment_id;
		return $wpdb->get_var($sql); 
	}

	/**
	* Returns 'left' 'right' or 'neutral' depending on what side this comment is made for.
	*/	
	function get_comment_side( $comment_id ) {
		global $wpdb;
		$sql = "SELECT comment_side FROM " .$wpdb->prefix . "tallytree_comments
				WHERE comment_id = " . $comment_id;
		return $wpdb->get_var($sql);
	}
	
	/**
	* Returns a row object containing all the info needed about this user
	*/
	function get_user_info( $user_id ) {
		global $wpdb;
		$sql = "SELECT  `user_login` ,  `user_nicename` ,  `user_email` ,  `user_url` ,  `display_name` 
					FROM  `wp_users` 
					WHERE ID =" . $user_id;
		return $wpdb->get_row($sql);
	}
	
	/**
	* Calculates the tilt angle of root node depending on left and right 
	* vote counts, The return angle is between -90 and 90
	*/
	function get_root_angle($num_left_vote, $num_right_vote, $fall_percentage, $min_num_voter ) {
		$alpha = ($num_left_vote + $num_right_vote ) / $min_num_voter * 90 ;
		if ( 0 != $num_left_vote + $num_right_vote){
			$percentage = ($num_right_vote - $num_left_vote) / ($num_left_vote + $num_right_vote );
		} else {
			$percentage = 0;
		}
		if ( $min_num_voter > ($num_left_vote + $num_right_vote) ) // if the number of votes doesn't reach the minimun-voter requirment.
		{
			$beta = $percentage * $alpha;
		}
		
		/* the next 2 conditions are for" if the minimun-voter requirement is met */
		// if the minimum requirements in both number of voters and fall percentage are met, the poll is over.
		else if ( $percentage >= ( 2 * $fall_percentage -1 ) || $percentage <= ( 1 - 2 * $fall_percentage) )
		{
			if ($percentage > 0 )
				$beta = 90;
			else
				$beta = -90;
		}
		// if only the minimun voters are reached, and the percentage is not so 
		else
		{
			$beta = $percentage * 90;
		}
		
		return $beta;				
	}
	
	/**
	* this function returns the code that adds the nodes and edges to the graph
	*/
	function get_graph_code($post_id) {
		$image_path = WP_PLUGIN_URL . "/tally-tree/img/";
		return "<script>
var graph = new Graph();"
. self::get_graph_data($post_id) . "
/*
var root = graph.newNode({label: 'root', side: 'root', img: '". $image_path . "king.png', width: 110, height: 180, angle: -30});
var jason = graph.newNode({label: 'Jason', side: 'left', img: '". $image_path . "profile/Jason.jpeg', width: 32, height: 32, votes: 1});
var ludi = graph.newNode({label: 'Ludi', side: 'right', img: '". $image_path . "profile/Ludi.png', width: 32, height: 32, votes: 1, comment1: 'This is a comment1', comment1Date: '1/1/2000'});
var elyse = graph.newNode({label: 'Elyse', side: 'right', img: '". $image_path . "profile/Elyse.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1 with lost of text to test the wrapping functionality. This is a comment1 with lost of text to test the wrapping functionality'});
var elena = graph.newNode({label: 'Elena', side: 'left', img: '". $image_path . "profile/Elena.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1' });
var noel = graph.newNode({label: 'Noel', side: 'left', img: '". $image_path . "profile/Noel.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var halimat = graph.newNode({label: 'Halimat', side: 'left', img: '". $image_path . "profile/halimat.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var peggy = graph.newNode({label: 'Peggy', side: 'right', img: '". $image_path . "profile/Peggy.jpeg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var arber = graph.newNode({label: 'Arber', side: 'right', img: '". $image_path . "profile/Arber.jpeg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var thor = graph.newNode({label: 'Thor', side: 'right', img: '". $image_path . "profile/Thor.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var omar = graph.newNode({label: 'Omar', side: 'left', img: '". $image_path . "profile/Omar.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var zooby = graph.newNode({label: 'Zooby', side: 'right', img: '". $image_path . "profile/Zooby.png', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var shasta = graph.newNode({label: 'Shasta', side: 'left', img: '". $image_path . "profile/person.png', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var sness = graph.newNode({label: 'Sness', side: 'left', img: '". $image_path . "profile/Sness.jpg', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var sanaz = graph.newNode({label: 'Sanaz', side: 'left', img: '". $image_path . "profile/Sanaz.png', width: 32, height: 32, votes: 1, comment1: 'This is a comment1'});
var aras = graph.newNode({label: 'Aras', side: 'left', img: '". $image_path . "profile/Aras.jpeg', width: 24, height: 24, votes: 1, comment1: 'This is a comment1'});

graph.newEdge(root, jason, {color: '#00A0B0'});
graph.newEdge(elyse, root, {color: '#CC333F'});
graph.newEdge(aras, root, {color: '#CC333F'});
graph.newEdge(ludi, elyse, {color: '#CC333F'});
//graph.newEdge(jason, dennis, {color: '#6A4A3C'});
graph.newEdge(jason, elena, {color: '#CC333F'});
//graph.newEdge(elena, halimat, {color: '#EB6841'});
graph.newEdge(jason, noel, {color: '#EDC951'});
graph.newEdge(peggy, arber, {color: '#7DBE3C'});
graph.newEdge(root, arber, {color: '#000000'});
graph.newEdge(arber, thor, {color: '#00A0B0'});
graph.newEdge(halimat, noel, {color: '#6A4A3C'});
graph.newEdge(root, omar, {color: '#CC333F'});
graph.newEdge(zooby, peggy, {color: '#CC333F'});
graph.newEdge(shasta, elena, {color: '#CC333F'});
graph.newEdge(sness, root, {color: '#CC333F'});
graph.newEdge(sness, sanaz, {color: '#CC333F'});

//graph.newEdge(omar, arber, {color: '#EB6841'});
*/
";
	}

	/**
	* this function returns the javascript and html code after the nodes have been added
	* to the visualization. It is responsible for draing of the nodes and inserting the
	* canvas holder object.
	*/
	function get_post_graph_code($post_id) {
		return "
//This function specifies how each node is drawn. Be careful that bounding box
//containing the elements in each node is calculated dynamically. So avoid adding
//larger items to the set unless its really neaded (such as for root node) 
Raphael.fn.label = function(str, side, img, width, height, votes) {

//    var color = Raphael.getColor();
    this.setStart();
//    var shape = this.circle(0, 0, 10).setOffset();
//    shape.attr({fill: color, stroke: color, 'fill-opacity': 0, 'stroke-width': 2, cursor: 'move'}).setOffset();
    if(str == 'root') {
        var image = this.image(img, 0, 0, width, height).setOffset();
        //var question = this.text(100, 450, 'Is this a really controversial question?').attr({font: '50px Helvetica', opacity: 0.5, fill: '#0f0'});
    } else {
        var text = this.text(20, 40, str).attr({'font-size': 15, 'opacity': 0}).setOffset();
        var rect = this.rect(0, 0, width + 4, height + 4, 5).attr({fill: '#222', opacity: 0.20}).setOffset();
	    var image = this.image(img, 2, 2, width, height).setOffset();
    }
    return this.setFinish();
}

Raphael.el.setOffset = function() {
    this.offsetx = this.attr('x');
    this.offsety = this.attr('y');
}

function moveSet(set, x, y) {
    set.forEach(function(item) {
        item.attr({
            x: x + item.offsetx,
            y: y + item.offsety
        })
    });
}

function doit() {
    var layout = new Layout.ForceDirected(graph, 400, 600.0, 0.7); // graph, stiffness, repulsion, damping
    var root_angle = 0
    var rotation_steps = 1; //Must be positive, smaller number means slower rotation or root

    var r = Raphael('holder" . $post_id . "', 640, 480);

	// calculate bounding box of graph layout.. with ease-in
	var currentBB = layout.getBoundingBox();
	var targetBB = {bottomleft: new Vector(-2, -2), topright: new Vector(2, 2)};
    
	// auto adjusting bounding box
	Layout.requestAnimationFrame(function adjust() {
		targetBB = layout.getBoundingBox();
		// current gets 20% closer to target every iteration
		currentBB = {
			bottomleft: currentBB.bottomleft.add( targetBB.bottomleft.subtract(currentBB.bottomleft)
				.divide(10)),
			topright: currentBB.topright.add( targetBB.topright.subtract(currentBB.topright)
				.divide(10))
		};

		Layout.requestAnimationFrame(adjust);
	});

	// convert to/from screen coordinates
	toScreen = function(p) {
		var size = currentBB.topright.subtract(currentBB.bottomleft);
		var sx = p.subtract(currentBB.bottomleft).divide(size.x).x * r.width;
		var sy = p.subtract(currentBB.bottomleft).divide(size.y).y * r.height;
		return new Vector(sx, sy);
	};    

    var renderer = new Renderer(10, layout,
        function clear() {
            // code to clear screen
        },
        function drawEdge(edge, p1, p2) {
            var connection;

            if (!edge.connection) {

                if (!edge.source.shape || !edge.target.shape)
                    return;

                connection = r.connection(edge.source.shape, edge.target.shape, {stroke: edge.data['color']});
                edge.connection = connection;

            } else {
                edge.connection.draw();
            }

        },
        function drawNode(node, p) {

            var shape;

            if (!node.shape) {
                node.shape = r.label(node.data['label'], node.data['side'], node.data['img'], node.data['width'], node.data['height'], node.data['votes']);
            }
            shape = node.shape;

            s = toScreen(p);
            if (node.data.side == 'root') {
                moveSet(shape, 300, 200);
                if(node.data.angle < 0) {
                    if(root_angle > node.data.angle) {
                        root_angle -= rotation_steps; //TODO: get rid of inacuracy if rotation_steps > 1
                        shape.attr({transform: 'r' + root_angle});
                    }
                } else {
                    if(root_angle < node.data.angle) {
                        root_angle += rotation_steps; //TODO: get rid of inacuracy
                        shape.attr({transform: 'r' + root_angle});
                    }
                }
            } else {
                moveSet(shape, Math.floor(s.x), Math.floor(s.y));
 		
		var popup = jQuery('#popup').hide();
		var over = false;
		var nameLabel = node.data['label'];
		var voteLabel =  'Votes:  ' + node.data['votes'];
		var commentLabel = node.data['comment1']; 
		var imgLabel = node.data['img']; 
		
		(node.shape).mouseover(function() { 
			if(node.data['label'] != 'root'){
				node.shape.toFront();
				r.safari();
				jQuery(popup).stop(true, true).fadeIn();
				over = true;
			}
			

		}); 
		(node.shape).mouseout(function(){
                        node.shape.toFront();
                        r.safari();
                        jQuery(popup).stop(true, true).fadeOut();
                        over = false;
                    
		});
		
 		jQuery(document).mouseover(function(e){
			if (over){
                    		jQuery(popup).css('left', e.clientX-350).css('top', e.clientY+200);
                    		jQuery(popup).html('<div id=\"headerWrap\"><img src=\"' + imgLabel+ '\" height=\"70px\" width=\"70px\"/>' + '<div id=\"header\">' + nameLabel + '</div>'+ '<div id=\"numvotes\">' + voteLabel+ '</div>' + '<div class=\"arg\">' + commentLabel + '</div></div>' + '<div class=\"commentWrap\"><img src=\"' + imgLabel+'\" height=\"30px\" width=\"30px\"/>' +'<div class=\"commentName\">' + nameLabel + '</div>' + '<div class=\"comment\">' + commentLabel + '</div></div>');
				
			
                  }
              	});

            }

        });

    renderer.start();
}

jQuery(function(){
    doit();
});
</script>

<div id='holder" . $post_id . "' width='640' height='480'>
<div id='popup'></div>
</div>";
	}
}

?>

