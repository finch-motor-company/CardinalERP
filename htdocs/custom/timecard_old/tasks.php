<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/custom/timecard/tasks.php
 *	\ingroup    projet
 *	\brief      List all tasks
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("users");
$langs->load("projects");

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$taskref = GETPOST('taskref', 'alpha');
$backtopage=GETPOST('backtopage','alpha');
$cancel=GETPOST('cancel');

$search_user_id = GETPOST('search_user_id', 'int');

//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
$taskstatic = new Task($db);
$extrafields_project = new ExtraFields($db);
$extrafields_task = new ExtraFields($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

if ($id > 0 || ! empty($ref))
{
	// fetch optionals attributes and labels
	$extralabels_projet=$extrafields_project->fetch_name_optionals_label($object->table_element);
}
$extralabels_task=$extrafields_task->fetch_name_optionals_label($taskstatic->table_element);

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('projecttaskcard','globalcard'));

$progress=GETPOST('progress', 'int');
$label=GETPOST('label', 'alpha');
$description=GETPOST('description');
$planned_workloadhour=(GETPOST('planned_workloadhour','int')?GETPOST('planned_workloadhour','int'):0);
$planned_workloadmin=(GETPOST('planned_workloadmin','int')?GETPOST('planned_workloadmin','int'):0);
$planned_workload=$planned_workloadhour*3600+$planned_workloadmin*60;

$userAccess=0;



/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_user_id="";
    $toselect='';
    $search_array_options=array();
}


$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
    {
        $search_all="";
        $search_categ="";
        $search_project="";
        $search_projectstatus=-1;
        $search_project_ref="";
        $search_project_title="";
        $search_task_ref="";
        $search_task_label="";
        $search_task_description="";
        $search_task_user=-1;
        $search_project_user=-1;
        $search_sday='';
        $search_smonth='';
        $search_syear='';
        $search_eday='';
        $search_emonth='';
        $search_eyear='';
        $toselect='';
        $search_array_options=array();
    }


}




/*
 * View
 */

$now = dol_now();
$form=new Form($db);
$formother=new FormOther($db);
$taskstatic = new Task($db);
$userstatic=new User($db);
$socstatic=new Societe($db);
$projectstatic = new Project($db);
//$puser=new User($db);
$tuser=new User($db);
//if ($search_project_user > 0) $puser->fetch($search_project_user);
if ($search_task_user > 0) $tuser->fetch($search_task_user);

$title=$langs->trans("Activities");
//if ($search_task_user == $user->id) $title=$langs->trans("MyActivities");

$title=$langs->trans("Project").' - '.$langs->trans("Tasks").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Tasks");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url);

if ($id > 0 || ! empty($ref))
{
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$res=$object->fetch_optionals($object->id,$extralabels_projet);


	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite  = $object->restrictedProjectArea($user,'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


	$tab=GETPOST('tab')?GETPOST('tab'):'tasks';

	$head=project_prepare_head($object);
	dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($object->public?'projectpub':'project'));

	$param='';
    if ($search_user_id > 0) $param.='&search_user_id='.dol_escape_htmltag($search_user_id);

    // Project card

    $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref='<div class="refidno">';
    // Title
    $morehtmlref.=$object->title;
    // Thirdparty
    if ($object->thirdparty->id > 0)
    {
        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
    }
    $morehtmlref.='</div>';

    // Define a complementary filter for search of next/prev ref.
    if (! $user->rights->projet->all->lire)
    {
        $objectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
        $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
    }

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Visibility
    print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
    if ($object->public) print $langs->trans('SharedProject');
    else print $langs->trans('PrivateProject');
    print '</td></tr>';

	/*if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
    {
        // Opportunity status
        print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
        $code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
        if ($code) print $langs->trans("OppStatus".$code);
        print '</td></tr>';

        // Opportunity percent
        print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
        if (strcmp($object->opp_percent,'')) print price($object->opp_percent,'',$langs,1,0).' %';
        print '</td></tr>';

        // Opportunity Amount
        print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
        if (strcmp($object->opp_amount,'')) print price($object->opp_amount,'',$langs,1,0,0,$conf->currency);
        print '</td></tr>';
    }*/

    // Date start - end
    print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
    $start = dol_print_date($object->date_start,'dayhour');
    print ($start?$start:'?');
    $end = dol_print_date($object->date_end,'dayhour');
    print ' - ';
    print ($end?$end:'?');
    if ($object->hasDelay()) print img_warning("Late");
    print '</td></tr>';

    // Budget
    print '<tr><td>'.$langs->trans("Budget").'</td><td>';
    if (strcmp($object->budget_amount, '')) print price($object->budget_amount,'',$langs,1,0,0,$conf->currency);
    print '</td></tr>';

    // Other attributes
    $cols = 2;
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';

    print '</div>';
    print '<div class="fichehalfright">';
    print '<div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Description
    print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
    print nl2br($object->description);
    print '</td></tr>';

    // Categories
    if($conf->categorie->enabled) {
        print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
        print $form->showCategories($object->id,'project',1);
        print "</td></tr>";
    }

    print '</table>';

    print '</div>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';


	dol_fiche_end();
}


if ($action == 'create' && $user->rights->projet->creer && (empty($object->thirdparty->id) || $userWrite > 0))
{
	if ($id > 0 || ! empty($ref)) print '<br>';

	print load_fiche_titre($langs->trans("NewTask"), '', 'title_project');

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createtask">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if (! empty($object->id)) print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	$defaultref='';
	$obj = empty($conf->global->PROJECT_TASK_ADDON)?'mod_task_simple':$conf->global->PROJECT_TASK_ADDON;
	if (! empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php"))
	{
		require_once DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$defaultref = $modTask->getNextValue($object->thirdparty,null);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

	// Ref
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
	print ($_POST["ref"]?$_POST["ref"]:$defaultref);
	print '<input type="hidden" name="taskref" value="'.($_POST["ref"]?$_POST["ref"]:$defaultref).'">';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input type="text" name="label" class="flat minwidth300" value="'.$label.'">';
	print '</td></tr>';

	// List of projects
	print '<tr><td class="fieldrequired">'.$langs->trans("ChildOfTask").'</td><td>';
	print $formother->selectProjectTasks(GETPOST('task_parent'),$projectid?$projectid:$object->id, 'task_parent', 0, 0, 1, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	$contactsofproject=(! empty($object->id)?$object->getListContactId('internal'):'');
	if (count($contactsofproject))
	{
		print $form->select_dolusers($user->id, 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, '', 'maxwidth300');
	}
	else
	{
		print $langs->trans("NoUserAssignedToTheProject");
	}
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $form->select_date(($date_start?$date_start:''),'dateo',1,1,0,'',1,1,1);
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $form->select_date(($date_end?$date_end:-1),'datee',1,1,0,'',1,1,1);
	print '</td></tr>';

	// Planned workload
	print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
	print $form->select_duration('planned_workload', $planned_workload?$planned_workload : $object->planned_workload,0,'text');
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
	print $formother->select_percent($progress,'progress',0,5,0,100,1);
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" class="quatrevingtpercent" rows="'.ROWS_4.'">'.$description.'</textarea>';
	print '</td></tr>';

	// Other options
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields_task->attribute_label))
	{
		print $object->showOptionals($extrafields_task,'edit');
	}

	print '</table>';

	dol_fiche_end();

	print '<div align="center">';
	print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

}
else if ($id > 0 || ! empty($ref))
{
	/*
	 * Fiche projet en mode visu
	 */

	/*
	 * Actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->projet->all->creer || $user->rights->projet->creer)
	{
		if ($object->public || $userWrite > 0)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id).'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';


	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$title=$langs->trans("ListOfTasks");
	$linktotasks='<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?projectid='.$object->id.'&withproject=1">'.$langs->trans("GoToListOfTimeConsumed").'</a>';
	//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'title_generic.png', 0, '', '', 0, 1);
	print load_fiche_titre($title,$linktotasks,'title_generic.png');

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user can have a parent that is not affected to him).
	$filteronthirdpartyid = $socid;
	$tasksarray=$taskstatic->getTasksArray(0, 0, 0, $filteronthirdpartyid, 0);
	// We load also tasks limited to a particular user
	$tmpuser=new User($db);
	if ($search_user_id > 0) $tmpuser->fetch($search_user_id);

	$tasksrole=($tmpuser->id > 0 ? $taskstatic->getUserRolesForProjectsOrTasks(0, $tmpuser, $object->id, 0) : '');
	//var_dump($tasksarray);
	//var_dump($tasksrole);

	if (! empty($conf->use_javascript_ajax))
	{
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<div class="div-table-responsive">';
	print '<table id="tablelines" class="noborder" width="100%">';

	if (count($tasksarray) > 0)
	{
    	// Link to switch in "my task" / "all task"
    	print '<tr class="liste_titre_filter nodrag nodrop"><td colspan="8">';
   	    print $langs->trans("TasksAssignedTo");
   	    print $form->select_dolusers($tmpuser->id > 0 ? $tmpuser->id : '', 'search_user_id', 1);
    	print '</td>';

        // Action column
        print '<td class="liste_titre" align="right">';
        $searchpicto=$form->showFilterButtons();
        print $searchpicto;
        print '</td>';
	}

	print '<tr class="liste_titre nodrag nodrop">';
	// print '<td>'.$langs->trans("Project").'</td>';
	print '<td width="100">'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="center">'.$langs->trans("DateStart").'</td>';
	print '<td align="center">'.$langs->trans("DateEnd").'</td>';
	print '<td align="right">'.$langs->trans("PlannedWorkload").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print '<td align="right">'.$langs->trans("ProgressCalculated").'</td>';
	print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	if (count($tasksarray) > 0)
	{
	    // Show all lines in taskarray (recursive function to go down on tree)
		$j=0; $level=0;
		$nboftaskshown=projectLinesa($j, 0, $tasksarray, $level, true, 0, $tasksrole, $object->id, 1, $object->id);
	}
	else
	{
		print '<tr class="oddeven"><td colspan="9" class="opacitymedium">'.$langs->trans("NoTasks").'</td></tr>';
	}

	print "</table>";
	print '</div>';

	print '</form>';


	// Test if database is clean. If not we clean it.
	//print 'mode='.$_REQUEST["mode"].' $nboftaskshown='.$nboftaskshown.' count($tasksarray)='.count($tasksarray).' count($tasksrole)='.count($tasksrole).'<br>';
	if (! empty($user->rights->projet->all->lire))	// We make test to clean only if user has permission to see all (test may report false positive otherwise)
	{
		if ($search_user_id == $user->id)
		{
			if ($nboftaskshown < count($tasksrole))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		}
		else
		{
			if ($nboftaskshown < count($tasksarray))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		}
	}
}

llxFooter();

$db->close();
