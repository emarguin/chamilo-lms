<?php

$icons_for_search_terms['Author']=Display::return_icon('search_author.gif');
$icons_for_search_terms['Technology']=Display::return_icon('search_technology.gif');
$icons_for_search_terms['Body part']=Display::return_icon('search_bodyparts2.gif');


/**
 * Search widget. Shows the search screen contents.
 * @package dokeos.search
 */
require_once dirname(__FILE__) . '/IndexableChunk.class.php';
require_once api_get_path(LIBRARY_PATH).'/specific_fields_manager.lib.php';

/**
 * Add some required CSS and JS to html's head.
 *
 * Note that $htmlHeadXtra should be passed by reference and not value,
 * otherwise this function will have no effect and your form will be broken.
 *
 * @param   array $htmlHeadXtra     A reference to the doc $htmlHeadXtra
 */
function search_widget_prepare(&$htmlHeadXtra) {
    $htmlHeadXtra[] = '
    <style type="text/css">
    .tags {
        display: block;
        margin-top: 20px;
        width: 90%;
    }
    .tag {
        float: left;
        display: block;
        padding: 5px;
        padding-right: 4px;
        padding-left: 4px;
        margin: 3px;
        border: 1px solid #ddd;
    }
    .tag:hover {
        background: gray;
        color:white;
        cursor:pointer;
      /*  font-weight:bold;*/
    }
    .lighttagcolor {
        background: gray;
        color:white;
       /* font-weight:bold;*/
    }
    .sf-select-multiple {
        width: 14em;
        margin: 0 1em 0 1em;
    }
    .sf-select-multiple-title {
    	font-weight: bold;
        margin-left: 1em; 
        font-size: 130%;
    }
    #submit {
    	background-image: url(\'../img/search-lense.gif\');
        background-repeat: no-repeat;
        background-position: 0px -1px;
        padding-left:18px;
    }
    .lower-submit {
    	float:right;
        margin: 0 0.9em 0 0.5em;
    }
    #tags-clean {
    	float: right;
    }
    .sf-select-splitter {
    	margin-top: 4em;
    }
    .search-links-box {
    	background-color: #ddd;
        border: 1px solid #888;
        padding: 1em;
        -moz-border-radius: 0.8em;
    }
    .search-help-box {
        border: 1px solid #888;
        padding: 0 1em 1em 1em;
        -moz-border-radius: 0.8em;
        float: right;
        color: #888;
        margin-right: 5%;
        width: 300px;
    }

    </style>';


    $htmlHeadXtra[] = '
    <script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript"></script>';

    $htmlHeadXtra[] ='
      <script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.autocomplete.js"></script>
    <link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.autocomplete.css" />';

    $htmlHeadXtra[] = "
    <script type=\"text/javascript\">
    var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $(document).ready(function() {
      $('#dokeos_search').submit(function (e) {
          var tags = String();
          $('.lighttagcolor').each(function (b, a) {
             // tags = tags.concat(a.id +',');
              tags += $(this).attr('title') + jQuery.trim($(this).html().replace(".'"\n"'.",'')) + ',';
          });
          $('#tag_holder').val(tags);
          return true;
      });
      /*$('#tags').hide();*/
      $('a#tags-toggle').click(function() {
        $('#tags').toggle(150);
        return false;
      });
      $('#tags-clean').click(function() {
        $('span#key_wrapper').html('');

        // clear multiple select
        $('select option:selected').each(function () {
            $(this).attr('selected', '');
        });

        $('.lighttagcolor').removeClass('lighttagcolor');
        return false;
      });
      $('#query').autocomplete('search_suggestions.php', {
        multiple: false,
        selectFirst: false,
        mustMatch: false,
        autoFill: false
      });

    });
    </script>";
}
/**
 * Trim tags off first XAPIAN_PREFIX_TAG only
 */
function trim_tag($tag)
{
  if(substr($tag,0,1)==XAPIAN_PREFIX_TAG)
  {
    return substr($tag,1);
  }
  return $tag;
}

/**
 * Show the search widget
 * TODO: reorganize an clean this to reuse code
 *
 * The form will post to lp_controller.php by default, you can pass a value to
 * $action to use a custom action.
 * IMPORTANT: you have to call search_widget_prepare() before calling this
 * function or otherwise the form will not behave correctly.
 *
 * @param   string $action     Just in case your action is not
 * lp_controller.php
 */
function search_widget_show($action="lp_controller.php") {
    global $charset, $icons_for_search_terms;
    require_once api_get_path(LIBRARY_PATH).'/search/DokeosQuery.php';
    $sf_terms = array();
    $specific_fields = get_specific_field_list();

    if ( ($cid=api_get_course_id()) != -1 ) {
	    // with cid
	    $course_filter = dokeos_get_boolean_query(XAPIAN_PREFIX_COURSEID . $cid);
	    $dktags = dokeos_query_simple_query('',0,1000,array($course_filter));
	    $dktags_org = $dktags;

	    $temp = array();
	    foreach($dktags[1] as $obj){
		    $temp = array_merge($obj['tags'], $temp);
	    }
	    $dktags = $temp;
	    unset($temp);

        //prepare specific fields names (and also get possible URL param names)
        $url_params = array();
	    foreach ($specific_fields as $specific_field) {
        	$temp = array();
        	foreach($dktags_org[1] as $obj) {
        		$temp = array_merge($obj['sf-'.$specific_field['code']], $temp);
        	}
        	$sf_terms[] = $temp;
            $url_params[] = 'sf_'.$specific_field['code'];
        	unset($temp);
        }
        //var_dump($sf_terms);
	    
    }
    else {
	    //without cid
	    $dktags = xapian_get_all_terms(1000, XAPIAN_PREFIX_TAG);
        //prepare specific fields names (and also get possible URL param names)
        $url_params = array();
	    foreach ($specific_fields as $specific_field) {
        	$temp = array();
        	//get Xapian terms for a specific term prefix, in ISO, apparently
            $sf_terms[] = xapian_get_all_terms(1000, $specific_field['code']);
            $url_params[] = 'sf_'.$specific_field['code'];
        	unset($temp);
        }
	    //var_dump($sf_terms);
    }

    //check if URL params are defined (to see if we show the thesaurus or not)
    $show_thesaurus = false;
    foreach ($url_params as $param) {
    	if (is_array($_REQUEST[$param])) {
	    	foreach ($_REQUEST[$param] as $term) {
		    	if (!empty($term)) { $show_thesaurus = true; }
	    	}
    	}
    }

    $post_tags = array();
    
    if (isset($_REQUEST['tags'])) {
        $filter = TRUE;
        $post_tags = explode(',', stripslashes($_REQUEST['tags']));
    }
    //sorting the array of tags and keywords
    foreach ($dktags as $key => $value) {
        $temp[trim(trim_tag(stripslashes(mb_convert_encoding($value['name'],$charset,'UTF-8'))))] = $key;
    }
    
    $temp = array_flip($temp);
    unset($dktags);
    natcasesort($temp);
    $dktags = $temp;
  
    $i = 0;
    foreach ($temp as $key) {
        if (in_array($key, $post_tags)==true && !empty($key)) {
            $tags_list .= '<span id="t_'.md5($key).'">'.(($i==0)?'':', ').$key.'</span>';
            $i++;
        }
    }

    //echo '<div class="search-help-box"><h3>'.get_lang('ThesaurusHelpTitle').'</h3>'.get_lang('ThesaurusHelpComment').'</div>';
    echo '<h2>'.get_lang('LectureLibrary').'</h2>';
    //echo '<span class="tool-intro">'.get_lang('LectureLibraryIntro').'</span><br /><br />';
    //echo '<span class="tool-intro">'.get_lang('ThesaurusHelpComment').'</span><br /><br />';

    if (!empty($_SESSION['_gid'])) {
        Display::display_introduction_section(TOOL_SEARCH.$_SESSION['_gid'],'left');
    } else {
        Display::display_introduction_section(TOOL_SEARCH,'left');
    }
    
    $op = 'or';
    if (!empty($_REQUEST['operator']) && in_array($op,array('or','and'))) { 
        $op = Security::remove_XSS($_REQUEST['operator']); 
    }
 ?>
<form id="dokeos_search" action="<?php echo $action.'?mode='.htmlentities($_GET['mode']) ?>"
method="get">
    <input type="hidden" name="action" value="search"/>
    <input type="text" id="query" name="query" size="40" />
    <input type="submit" id="submit" value="<?php echo get_lang("Search") ?>" />
    <!--span id="keywords" style="font-size:12px;font-weight:bold"><?php echo get_lang("Keywords") ?>:</span-->
    <span id="key_wrapper"><?php echo $tags_list ?></span>
    <input type="hidden" name="tags" id="tag_holder" />
    <br /><br />
    <?php
    echo '<span class="search-links-box">';
        echo Display::return_icon('thesaurus.gif',get_lang('ShowTags'),array('style'=>'margin-bottom:-6px;')) .' <a id="tags-toggle" href="#">'.  get_lang('ShowTags') .'</a>';
        echo '&nbsp;';
        //$specific_fields = get_specific_field_list();
        //$icon = Display::return_icon('thesaurus.gif',get_lang('ShowTags'),array('style'=>'margin-bottom:-6px;'));
//        foreach ($specific_fields as $specific_field) {
//        	echo '<a id="tags-toggle-sf-'. $specific_field['code'] .'" href="#">'. $icon .' '. $specific_field['name'] .'</a>&nbsp;';
//        }
        //echo Display::return_icon('eraser.gif',get_lang('CleanTags'),array('style'=>'margin-bottom:-6px;')).' <a id="tags-clean" href="#">'. get_lang('CleanTags') .'</a>';
    echo '</span>'."\n";
    echo '<div id="tags" class="tags" style="display:'.($show_thesaurus==true?'block':'none').';">'."\n";
    /*
    foreach ($dktags as $tagged)
    {
        //$tag = trim(trim($tagged, 'T '));
        $tag = trim($tagged); //strimming of XAPIAN_PREFIX_TAG has already been done
        $color = "";
        if (empty($tag)) continue;
        if ($filter) {
            if (array_search($tag, $post_tags) !== FALSE)
                $color = "lighttagcolor";
        }
        $word =htmlspecialchars($tag,ENT_QUOTES,$charset);
        $tag = md5($tag);
        ?>
        <span title="<?php echo XAPIAN_PREFIX_TAG ?>" class="tag <?php echo $color?>" id="<?php echo $tag ?>">
            <?php echo $word ?></span>

        <script type="text/javascript">
            $('#<?php echo $tag ?>').click(function waaa (e) {
                 total_list = $('span#key_wrapper').children(":first");
                 count_total_list = total_list.length;
                 if ( $('.lighttagcolor').size() < 5) {
                  if ($('#t_<?php echo $tag ?>').length>0){
                    $('#t_<?php echo $tag ?>').remove();
                  } else {
                    if (count_total_list == 0) {
                      $('#key_wrapper').append('<span id="t_<?php echo $tag ?>"><?php echo XAPIAN_PREFIX_TAG . $word ?></span>');
                    } else {
                      $('#key_wrapper').append('<span id="t_<?php echo $tag ?>">, <?php echo XAPIAN_PREFIX_TAG . $word ?></span>');                    	
                    }
                  }
                    $('#<?php echo $tag ?>').toggleClass('lighttagcolor');
                 } else {
                    $('#<?php echo $tag ?>').removeClass('lighttagcolor');
                    $('#t_<?php echo $tag ?>').remove();
                 }
            });
        </script>
        <?php
    } //*/

    // Process each prefix type term
    echo '<table><tr>';
    $i = 0;
    $max = count($sf_terms);
    foreach ($sf_terms as $sf_term_array) {
        //$multiple_select = '<div style="float:left;">';
        $multiple_select = '';         
        //sorting the array of tags and keywords
        if ($i>0) {
        	//print "+" image
            
            $multiple_select .= '<td><img class="sf-select-splitter" src="../img/search-big-plus.gif" alt="plus-sign-decoration"/></td>';
        }
    	$temp = array();
    	foreach ($sf_term_array as $key => $value) {
		    //$temp[trim(stripslashes(mb_convert_encoding($value['name'],$charset,'UTF-8')))] = $key;
            $temp[trim(stripslashes($value['name']))] = $key;
	    }
	    $temp = array_flip($temp);
	    unset($sf_term_array);
	    natcasesort($temp);
	    $sf_term_array = $temp;
	    //		    $temp = array_merge($obj['sf-'.$specific_field['code']], $temp);
	    //var_dump($sf_term_array);

	    $sf_copy = $sf_term_array;
	    $one_element = array_shift($sf_copy);
        //we took the Xapian results in the same order as the specific fields
        //came, so using the specific fields index, we are able to recover
        //each field's name
        $multiple_select .= '<td><label class="sf-select-multiple-title" for="sf_'. substr($one_element, 0, 1) .'[]">'.$icons_for_search_terms[$specific_fields[$i]['name']].' '.$specific_fields[$i]['name'].'</label><br />';
	    $multiple_select .= '<select multiple="multiple" size="7" class="sf-select-multiple" name="sf_'. substr($one_element, 0, 1) .'[]">';
	    
		foreach ($sf_term_array as $tagged)
		{
		    $tag = substr($tagged, 1);
		    $prefix = substr($tagged, 0, 1);
		    $color = "";
		    if (empty($tag)) continue;
		    if ($filter) {
			    if (array_search($tag, $post_tags) !== FALSE)
				    $color = "lighttagcolor";
		    }
		    $word =htmlspecialchars($tag,ENT_QUOTES,$charset);
		    $tag = md5($tag);
		    /*
		    ?>
        <span title="<?php echo $prefix ?>" class="tag <?php echo $color?>" id="<?php echo $tag ?>">
            <?php echo $word ?></span>

        <script type="text/javascript">
            $('#<?php echo $tag ?>').click(function waaa (e) {
                 total_list = $('span#key_wrapper').children(":first");
                 count_total_list_<?php echo $prefix ?> = total_list.length;
                 if ( $('.lighttagcolor').size() < 5) {
                  if ($('#<?php echo $prefix .'_'. $tag ?>').length>0){
                    $('#<?php echo $prefix .'_'. $tag ?>').remove();
                  } else {
                    if (count_total_list_<?php echo $prefix ?> == 0) {
                      $('#key_wrapper').append('<span title="<?php echo $prefix ?>" id="<?php echo $prefix .'_'. $tag ?>"><?php echo $prefix . $word ?></span>');
                    } else {
                      $('#key_wrapper').append('<span title="<?php echo $prefix ?>" id="<?php echo $prefix .'_'. $tag ?>">, <?php echo $prefix . $word ?></span>');                    	
                    }
                  }
                    $('#<?php echo $tag ?>').toggleClass('lighttagcolor');
                 } else {
                    $('#<?php echo $tag ?>').removeClass('lighttagcolor');
                    $('#<?php echo $prefix .'_'. $tag ?>').remove();
                 }
            });
        </script>
        <?php
        //*/
            $selected = '';
            $tag_mark = substr($tagged,0,1);
            $tagged_clean = substr($tagged,1);
            if (isset($_REQUEST['sf_'.$tag_mark]) && in_array($tagged_clean,$_REQUEST['sf_'.$tag_mark])) { 
                $selected = 'selected="selected"';
            }
            $multiple_select .= '<option value="'. $word .'" '.$selected.'>'. $word .'</option>';
        }
        $multiple_select .= '</select>';
        //if (($i+1) == $max) {
        //    $multiple_select .= '<br /><input type="submit" id="tags-clean" href="#" value="'. get_lang('CleanTags') .'" /><input class="lower-submit" type="submit" value="'.get_lang('Validate').'" />';   
        //}
        //$multiple_select .= '</div>';
        $multiple_select .= '</td>';
        print $multiple_select;
        $i++;
    }
    echo '</tr><tr>';
    echo '<td colspan="'.((($i-1)*2)-1).'" align="right" style="padding-right:0.9em;">';
    echo get_lang('CombineSearchWith').':';
    echo '<input type="radio" class="search-operator" name="operator" value="or" '.($op=='or'?'checked="checked"':'').'>'.strtoupper(get_lang('Or')).'</input>';
    echo '<input type="radio" class="search-operator" name="operator" value="and" '.($op=='and'?'checked="checked"':'').'>'.strtoupper(get_lang('And')).'</input>';
    echo '</td><td></td><td><br /><input class="lower-submit" type="submit" value="'.get_lang('Validate').'" /><input type="submit" id="tags-clean" value="'. get_lang('CleanTags') .'" /></td>';
    
    ?>
    </tr></table>
    <div style="clear:both;"></div>
    </div>

</form>
<br style="clear: both;"/>

<?php
}
