<?php
require_once '../../../wp-config.php';


global $wpdb;
global $current_user;
$post_id = $_POST['postId'];
$comment_id = $_POST['commentId'];
$ip = $_SERVER['REMOTE_ADDR'];
$like = get_post_meta($post_id, '_liked', true);

//save current user_id and their vote (comment_id)in the tallytree_vote table
if($post_id != '') {
	//$current_user = wp_get_current_user();
	//if($current_user->ID > 0) {
	//echo $current_user->display_name;
	TallyTree::add_vote($comment_id, $current_user->ID, $post_id);	
	//}
	
	
	/*	
	$wpdb->insert( $wpdb->prefix . "tallytree_votes",
			array(
					'comment_id' => $comment_id,
					'voter_id' => $current_user->ID
			),
			array(
					'%d',
					'%d'
			)
	);
	*/
	
	/*$voteStatusByIp = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."ilikethis_votes WHERE post_id = '$post_ID' AND ip = '$ip'");
	
    if (!isset($_COOKIE['liked-'.$post_ID]) && $voteStatusByIp == 0) {
		$likeNew = $like + 1;
		update_post_meta($post_ID, '_liked', $likeNew);

		setcookie('liked-'.$post_ID, time(), time()+3600*24*365, '/');
		$wpdb->query("INSERT INTO ".$wpdb->prefix."ilikethis_votes VALUES ('', NOW(), '$post_ID', '$ip')");

		echo $likeNew;
	}
	else {
		echo $like;
	}*/
}
?>
