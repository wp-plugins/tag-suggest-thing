<?php 
/*
Plugin Name: Tag Suggest Thing
Plugin URI: http://www.neato.co.nz/wordpress-things/tag-suggest-thing
Description: Tag Suggestions for Wordpress using 
Version: beta 1
Author: Christine From The Internet
Author URI: http://www.neato.co.nz
*/

class Things_TagSuggest {
	/**
	 * ShowEditControl() - Render the form elements for tag selection
	 */
	function ShowEditControl() {
		?>
<fieldset id="tagsuggestions" class="dbx-box">
<h3 class="dbx-handle"><?php _e('Tag Suggestions') ?></h3>
<div class="dbx-content">
	<p>
		<input name="get_suggestions" type="button" onClick="Things_FindTagSuggestions()" value="<?php _e('Get Tag Suggestions') ?>">
		<div id="tag_suggestions"></div>
	</p>
</div>
</fieldset>
<?
	}

	/**
	 * TagSuggestionScripts() - Render the javascript that supports the tag selection process.
	 */
	function TagSuggestionScripts() {
	  // use JavaScript SACK library for AJAX
	  wp_print_scripts( array( 'sack' ));

	  // Define custom JavaScript function
	?>
	<script type="text/javascript">
	//<![CDATA[
	
	/** 
	 * Things_FindTagSuggestions
	 * Perform the AJAX magic needed to retrieve tag suggestions.
	 */	
	function Things_FindTagSuggestions()
	{
	   var mysack = new sack( 
	       "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );    

	  mysack.execute = 1;
	  mysack.method = 'POST';
	  mysack.setVar( "action", "things_tagSuggest" );
	  mysack.setVar( "content", document.getElementById("content").value);
	  mysack.encVar( "cookie", document.cookie, false );
	  mysack.onError = function() { alert('AJAX error in looking up tag suggestions' )};
	  mysack.runAJAX();
		
	  return true;
	}
	
	/** 
	 * Things_ProcessMatches
	 * Generate HTML that contains the links needed for adding tag suggestions.
	 */
	function Things_ProcessMatches(matches) {
		html = "";
		htmlall = "";
		if(matches.ResultSet) {
			for(i = 0; i < matches.ResultSet.Result.length; i++) {
				html += "<a onClick=\"Things_AddTag('" + matches.ResultSet.Result[i] +"')\">" + matches.ResultSet.Result[i] +"</a><br />";
				if (htmlall != "") htmlall += ", ";
				htmlall += matches.ResultSet.Result[i];
			}

			html += "<br /><a onClick=\"Things_AddTag('" + htmlall + "')\"><?php _e('Add All') ?></a>";
		} else {
			html = "<i><?php _e('No Suggestions') ?></i>";
		}
		elt = document.getElementById("tag_suggestions");
		elt.innerHTML = html;
	}
	
	/** 
	 * Things_AddTag
	 * Adds the specified tag to the end of the tag input field.
	 */
	function Things_AddTag(tagname) {
		if (document.getElementById('tags-input').value == "") {
			document.getElementById('tags-input').value = tagname;
		} else {
			document.getElementById('tags-input').value += ", " + tagname;
		}
	}
	//]]>
	</script>
	<?php
	}

	/**
	 * FindTagSuggestions() - Talk to the Yahoo! content term extraction service to get
	 * a JSON object back.
	 * This is hooked up to the wp_ajax_{this plugin} hook.
	 */
	function FindTagSuggestions() {
		$content = $_REQUEST['content'];

		$keywordAPISite = "api.search.yahoo.com";
		$keywordAPIUrl = "/ContentAnalysisService/V1/termExtraction";
		$appID = "JBtBxV3V34HIwe1eOXF8soqqdSOsauIR_HzOzOov2dsyB3om5GFnw4jdSLJ0lLY-";

		$data = "appid=" . $appID . "&output=json&context=" . $content;
	
		$sock = fsockopen($keywordAPISite, 80, $errno, $errstr, 30);
		if (!$sock) die("$errstr ($errno)\n");

		fputs($sock, "POST $keywordAPIUrl HTTP/1.0\r\n");
		fputs($sock, "Host: $keywordAPISite\r\n");
		fputs($sock, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($sock, "Content-length: " . strlen($data) . "\r\n");
		fputs($sock, "Accept: */*\r\n");
		fputs($sock, "\r\n");
		fputs($sock, "$data\r\n");
		fputs($sock, "\r\n");

		$headers = "";
		while ($str = trim(fgets($sock, 4096)))
		  $headers .= "$str\n";
		print "\n";

		while (!feof($sock))
		  $json .= fgets($sock, 4096);

		fclose($sock);
		
		die("Things_ProcessMatches(eval(" . $json . "));");
	}
}

add_action('admin_print_scripts', array('Things_TagSuggest','TagSuggestionScripts'));
add_action('dbx_post_sidebar', array('Things_TagSuggest','ShowEditControl'));
add_action('wp_ajax_things_tagSuggest', array('Things_TagSuggest','FindTagSuggestions'));
?>