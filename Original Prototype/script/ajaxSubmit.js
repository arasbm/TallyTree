var $vote = jQuery.noConflict();
$vote(document).ready(function() {
    	$vote('form#commentform p').each(function(i,item) {
        if ($vote('textarea[name=comment]', $vote(item)).length > 0) {
            $vote(item).after('<input name="voteradio" class="voteType" type="radio"  value="left" style="display:none;"/><input name="voteradio" class="voteType" type="radio"  value="neutral" align="middle"  style="display:none;"/><input name="voteradio" class="voteType" type="radio"  value="right" align="middle"  style="display:none;"/><input type="hidden" name="tally-vote" value="0" />');   
        }
    });
    $vote('div.vote-buttons a').bind('click', function(e) { 
    	//$(this).toggleClass('down');
    	$vote('input[name=tally-vote]').val($vote(this).html()); 
   	//TODO: toggle the button highlights
    });   

});
