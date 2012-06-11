function likeThis(postId,commentId) {
	if (postId != '') {
		jQuery('#iLikeThis-'+commentId+' .counter').html('<img alt="good!" src="http://goliath.cs.uvic.ca/playground/wordpress/wp-content/plugins/tally-tree/img/check_icon.png" width="30px" height="30px" style="padding-right:10px;"/>');
		
		jQuery.post("http://goliath.cs.uvic.ca/playground/wordpress/wp-content/plugins/tally-tree/vote.php",
			{ postId: postId, commentId: commentId },
			function(data){
				//alert(data);			
				alert("Your vote has been submitted! Please refresh your browser to see your impact on the Tally Tree.")
				jQuery('#iLikeThis-'+commentId+' .counter').text(data);
			});
	}
	window.location.href = window.location.href;
}