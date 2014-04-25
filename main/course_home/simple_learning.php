<?php

require_once '../inc/global.inc.php';

/*	Is the user allowed here? */
api_protect_course_script(true);

$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;

$tpl = new Template($_course['title']);
$ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';

// on va chercher la liste des modules visibles du thème
$list = new LearnpathList(api_get_user_id());
$modules = $list->get_flat_list();

foreach($modules as $id=>&$module)
{
	// on retire les modules non visibles
	if(!$module['lp_visibility'])
	{
		unset($modules[$id]);
		continue;
	}
	
	// on va chercher l'auteur et la description
	$sql = 'SELECT author, description
			FROM '.Database::get_course_table(TABLE_LP_MAIN).' 
			WHERE id = '.intval($id).'
			AND c_id = '.intval(api_get_course_int_id());
	if($rs = Database::query($sql))
	{
		$module['author'] = Database::result($rs,0,'author');
		$module['description'] = Database::result($rs,0,'description');
	}
	
	// barre de progression
	$module['progress'] = learnpath::get_db_progress($id, api_get_user_id(), '%', '', false, api_get_session_id());
	$module['progress_bar'] = learnpath::get_progress_bar('%', learnpath::get_db_progress($id, api_get_user_id(), '%', '', false, api_get_session_id()));
	
	// url
	$module['url'] = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$id;
}
$tpl->assign('modules', $modules);


// on liste les outils actifs
$sql = 'SELECT *
		FROM '.Database::get_course_table(TABLE_TOOL_LIST).'
		WHERE c_id = '.api_get_course_int_id().'
		AND visibility = 1
		AND name != "learnpath"
		AND image != "scormbuilder.gif"';
$rs = Database::query($sql, __FILE__, __LINE__);
$tools = array();
while($tool = Database::fetch_array($rs))
{
	$tool['url'] = api_get_path(WEB_CODE_PATH).$tool['link'].'?'.api_get_cidreq();
	$tool['langname'] = api_underscore_to_camel_case($tool['name']);
	if($tool['name'] == 'forum')
	{
		$tpl->assign('forum', $tool);
		continue;
	}
	if($tool['name'] == 'wiki')
	{
		$tpl->assign('wiki', $tool);
		continue;
	}
	$tools[] = $tool;
}
usort($tools, function($tool1, $tool2){
	return strcmp($tool1['name'], $tool2['name']);
});
$tpl->assign('tools', $tools);
$tpl->assign('course_title', $_course['title']);
$content = $tpl->get_template('course_home/simple_learning.tpl');


$tpl->assign('content', $tpl->fetch($content));
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);

?>