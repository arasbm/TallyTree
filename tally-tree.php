<?php
/*
Plugin Name: TallyTree
Plugin URI: http://www.tallytree.com
Description: An interactive visualization to breathe life into comments on you blog!
Version: 0.1
Author: Aras Balali Moghaddam
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
register_activation_hook(WP_PLUGIN_DIR . '/tally-tree/tally-tree.php', array('TallyTree','install_tallytree_plugin'));

class TallyTree
{
    var $widget;

    public function __construct() {
        /*
         * Register Actions
         */
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('comment_form', array($this, 'modify_comment_form'));
        add_action('comment_text', array($this, 'modify_posted_comment'));
        add_action('comment_post', array($this, 'comment_save'));
        add_action('wp_print_styles', array($this, 'add_stylesheet'));

        /**
         * Register Filters
         */
        //add_filter('preprocess_comment', array($this, 'preprocess_comment'));

        add_shortcode('tallytree', array($this,'tallytree_shortcode'));
    }

    /**
     * This method is called on plugin activation
     */
    function install_tallytree_plugin() {
        self::create_db_tables();
    }

    /**
     * Create TallyTree database tables
     */
    function create_db_tables()
    {
        global $wpdb;
        //TODO: global $tallytree_version;

        $themes_table = $wpdb->prefix . "tallytree_themes";
        $votes_table = $wpdb->prefix . "tallytree_votes";

        if ($wpdb->get_var("show tables like '$themes_table'") != $themes_table) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $sql = "CREATE TABLE " . $themes_table . " (
                theme_id int  NOT NULL  PRIMARY KEY,
                theme_name varchar(255)  NOT NULL,
                widget_name varchar(255)  NOT NULL,
                statue_path varchar(255),
                background_image varchar(255),
                css_path varchar(255),
                width int DEFAULT 640  NOT NULL,
                height int DEFAULT 400  NOT NULL,
                min_votes_before_fall int DEFAULT 10  NOT NULL,
                fall_percentage float DEFAULT 70.0  NOT NULL
                );";
            maybe_create_table($themes_table, $sql);
        }

        if ($wpdb->get_var("show tables like '$votes_table'") != $votes_table) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $sql = "CREATE TABLE IF NOT EXISTS " . $votes_table . " (
            voter_id int,
            user_email varchar(255),
            comment_id int  NOT NULL,
            vote_date date  NOT NULL
            );";
            maybe_create_table($votes_table, $sql);
            echo "vote table";
            //dbDelta($sql);
        }

        add_option("tallytree_db_version", "2.0");
    }

    /**
     * Register javascript files for TallyTree visualization widgets
     */
    function register_scripts() {
        //wp_enqueue_script("jquery");
        wp_enqueue_script('arbor', WP_PLUGIN_URL . '/tally-tree/scripts/arbor/lib/arbor.js');
        //TODO: remove this main script below which is just for testing arbor
        wp_enqueue_script('main', WP_PLUGIN_URL . '/tally-tree/scripts/arbor/docs/sample-project/main.js');
    }

    /**
     * This function is triggered when a new tallytree shortcode is
     * detected in a post. It changes the content of the post by adding
     * a new TallyTree canvas to it
     */
    function tallytree_shortcode($atts) {
        global $post;

        if(isset($atts['theme_id'])) {
            //TODO: load default configurations based on theme
        } else {
            //manually set defaults
            $defaults = array(
                // main attributes
                'id' => 0,
                'question' => 'Is there a need for TallyTree?',
                'answers' => 'Yes%No',
                'show_threaded_comments' => true,
                //theme attributes
                'theme_name' => 'default',
                'widget_name' => 'leaning-statue',
                'statue_path' => '',
                'background_image' => '',
                'css_path' => '',
                'width' => 640,
                'height' => 400,
                'min_votes_before_fall' => 10,
                'fall_percentage' => 70.0
            );
        }

        echo self::create_new_tallytree($post->ID, shortcode_atts($defaults, $atts));
    }

    /**
     * Create and return a canvas containing a new TallyTree for the post
     * @param post_id the id of post that will get this tallytree
     * @param config an array of configurations for this tallytree
     * @return the html code for inserting tallytree widget
     */
    function create_new_tallytree($post_id, $config) {
        extract($config);

        switch ($widget_name) {
            case 'leaning-statue':
                include 'leaning-statue.php';
                $this->widget = new LeaningStatue(
                    $id,
                    $question,
                    $answers,
                    $theme_name,
                    $statue_path,
                    $background_image,
                    $css_path,
                    $width,
                    $height,
                    $min_votes_before_fall,
                    $fall_percentage
                );
                break;
            case 'convex-polygon':
                //TODO: implement convex polygon visualization for
                //questions with more than two sides
                break;
        }

        return $this->widget->get_html_code();
    }

    function preprocess_comment($comment) {
        echo "preprocessing comment" . $comment;
    }

    /**
     * This function runs right after the comment is saved in the database
     * $_POST data is available in this function so any custom fields can be
     * processed here
     */
    function comment_save($comment_id) {
        add_comment_meta($comment_id, 'tallytree_side', $_POST['tallytree_side'], true);
    }

    /**
     * Attached to comment_text
     * This function is responsible for modifying a comment that has been posted
     * to add tallytree specific options such as vote button to it
     */
    function modify_posted_comment($comment) {
        global $wpdb, $post;
        $comment_id = get_comment_ID();
        $current_user = wp_get_current_user();
        $comment_side = get_comment_meta($comment_id, 'tallytree_side', true);
        echo "in support of " . $comment_side;

        if($current_user->ID < 1) {
            //User is not logged in
            $comment .= "<div style = 'float:centre'>Please login to vote</div>";
        } elseif( self::user_can_vote($current_user->ID, $post->ID) ) {
            //make sure this comment is votable
            if ( self::comment_is_votable($comment_id) ) {
                //display the "Vote for this" button
                $comment .= self::get_vote_btn();
            } else {
                //this is a neutral comment
            }
        } else {
            //user have already voted. We just need to check if this user voted for this post, if so we display a checkmark
            if ( self::user_vote_comment( $current_user->ID, $comment_id ) ) {
                $comment .= "<div style = 'float:right'><img src='" . bloginfo('template_directory') . "/img/check_selected.png' alt='You have voted for this comment' /></div>";
            }
        }
        return $comment;
    }

    /**
     * this function sets up the vote for this button and returns its html code
     */
    function get_vote_btn() {
        //TODO: write me
        //return "<div style = 'float:right'><img src='http://127.0.0.1/wordpress/wp-content/plugins/tally-tree/img/check_default.png' alt='You can only select one comment, so choose carefully!' /></div>";
        return "<div class='vote-button' title='You can only select one comment, so choose carefully!'></div>";
    }

    /**
     * if comment is neutral return false otherwise return true
     */
    function comment_is_votable($comment_id ) {
        //TODO: write me
        return true;
    }

    /**
     * returns true if current user (with user_id) has not voted in this post, returns false otherwise
     */
    function user_can_vote($user_id, $post_id) {
        //TODO: write me
        return true;
    }

    /**
     * Modify the comment to add our custom left and right buttons
     * Only modify the comment form if this post has a tallytree
     */
    function modify_comment_form($post_id) {
        //TODO: move this function to leaning-statue or make it more general
        if ( self::has_tallytree($post_id) ) {
            echo "
            <label class='comment'>Choose your side:</label>

            <select id='tallytree_side' name='tallytree_side'>
                <option selected='selected' value='neutral'>Neutral Comment</option>
                <option value='left_answer'>" . $this->widget->get_left_answer() . "</option>
                <option value='right_answer'>" . $this->widget->get_right_answer() . "</option>
            </select>
            <script type='text/javascript'>
                var selectmenu=document.getElementById('comment_side');
                selectmenu.onchange=function(){
                    var chosenoption=this.options[this.selectedIndex];
                    console.log(chosenoption.value);
                };
            </script>";
        }
    }

    /**
     * Returns true if the post contains a tallytree and return false otherwise
     */
    function has_tallytree($post_id) {
        //TODO: write me
        return true;
    }

    /**
     * Register tallytree CSS files
     */
    function add_stylesheet() {
        //$myStyleUrl = plugins_url('css/tallystyle.css', __FILE__);
        $myStyleUrl = WP_PLUGIN_URL . '/tally-tree/css/tallystyle.css';
        $myStyleFile = WP_PLUGIN_DIR . '/tally-tree/css/tallystyle.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('myStyleSheets', $myStyleUrl);
            wp_enqueue_style( 'myStyleSheets');
        }
    }

}

$tallytree = new TallyTree();
?>
