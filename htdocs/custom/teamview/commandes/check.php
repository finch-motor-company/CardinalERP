<?php

//require_once('../../main.inc.php');
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/teamview/class/taches/teamview.class.php');
dol_include_once('/teamview/class/taches/teamview_comments.class.php');
dol_include_once('/teamview/class/others/todo_commande_comments.class.php');
dol_include_once('/teamview/class/taches/taches.class.php');
dol_include_once('/teamview/class/taches/projets.class.php');
dol_include_once('/teamview/class/taches/elements_contacts.class.php');

include_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/html.formother.class.php';


dol_include_once('/teamview/class/others/commandes.class.php');
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

$societe = new Societe($db);
$commande = new commandes($db);

$commandeorig = new Commande($db);
$commandedet = new OrderLine($db);

$projet = new Project($db);
$projet_own = new projets($db);
$task = new Task($db);
$taskstatic = new Task($db);

$teamview = new teamview($db);
$comments 	= new todo_commande_comments($db);
$taches 	= new taches($db);
$tmpuser	= new User($db);
$elements_contacts 	= new elements_contacts($db);

$form 				= new Form($db);

$action 		= $_POST['action'];
$id_projet 		= $_POST['id_projet'];
$id_commande 		= $_POST['id_commande'];
$id_commande 		= $_POST['id_commande'];

$etats = [
	0 => "Brouillon",
	1 => "Validées",
];

$dircustom = DOL_DOCUMENT_ROOT.'/teamview/';
$customtxt = "";
if (!is_dir($dircustom)) {
	$customtxt = "/custom";
}


if ($action == "oneCommande") {
	$commande->fetchAll("", "", "", "", " AND rowid = ".$id_commande);
	$item1 = $commande->rows[0];

	$disabl = "disabled";
	if ($user->rights->projet->creer)
		$disabl = "";
	$commande->fetch($item1->rowid);

	$html["ref"] = '<h2 class="title" id="'.$item1->rowid.'">'.$langs->trans("ref_commande").' : <span style="color: #5780ca;">'.$commande->getNomUrl(0,"","",1).'</span></h2>';

	$societe->fetch($item1->fk_soc);
	$html["tiers"] = $societe->getNomUrl(1,"","",1);
	echo json_encode($html);
}

if ($action == "getallcommandes") {
	$arr = [];

	$actif_onitem 	= $_POST['actif_onitem'];
	$sortfield 		= $_POST['sortfield'];
	$sortorder 		= $_POST['sortorder'];
	$limit 			= $_POST['limit'];
	$offset 		= $_POST['offset'];
	$filter 		= $_POST['filter'];

	$commande->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);

	if (count($commande->rows) > 0) {
		$arr["DRAFT"] = $arr["VALIDATED"] = $arr["SIGNED"] = $arr["NOTSIGNED"] = $arr["BILLED"] = "";

		for ($i=0; $i < count($commande->rows) ; $i++) {
			$item = $commande->rows[$i];
			$commande->fetch($item->rowid);


			// Facure lignes
			$comlines=0;
			if($item->fk_statut == 0){
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX ."commandedet where fk_commande = ".$item->rowid;
				$resql = $commandedet->db->query($sql);
				if ($resql->num_rows) {
					$comlines=1;	
				}
			}


			$opacity2 = "1";
			$classNoComment = "classNoComment";
			$tot_cmnt = 0;
			$comments->fetchAll("", "", "", "", " AND id_commande = ".$item->rowid);
			$tot_cmnt = count($comments->rows);
			if ($tot_cmnt > 0){
				$opacity2 = "1";
				$classNoComment = "";
			}
			$societe->fetch($item->fk_soc);
			$soc = $societe->nom;

			$actf_cls = "";
			if ($item->rowid == $actif_onitem) {
				$actf_cls = "actif_onitem";
			}
			// die();

			$commandeorig->fetch($item->rowid);

			$popupinfo = "";
			$popupinfo .= '<b>'.$langs->trans("Réf").'.</b> : '.$commandeorig->ref   .'<br>';
			$popupinfo .= '<b>'.$langs->trans("Client").'</b> : '.$soc   .'<br>';
			$popupinfo .= '<b>'.$langs->trans("Date_commande").'</b> : '. dol_print_date($commandeorig->date,'day')   .'<br>';
			$popupinfo .= '<b>'.$langs->trans("Date_livraison").'</b> : '. dol_print_date($commandeorig->date_livraison,'day')   .'<br>';
			$popupinfo .= '<b>'.$langs->trans("Total_TTC").'</b> : '. number_format($commandeorig->total_ttc,2,","," ") .' '.$langs->getCurrencySymbol($conf->currency).'<br>';


			$content = '
				<div class="one_content '.$actf_cls.'" id="commande_'.$item->rowid.'"  data-rowid="'.$item->rowid.'"  ondblclick="OpenCommandePop(this)" data-lines="'.$comlines.'" data-title="'.$popupinfo.'">
					<h4 id_commande="'.$item->rowid.'">'.$commande->getNomUrl(0,"","",1).'<span class="tier_span"> - '.$soc.'</span></h4>
					<input type="hidden" class="id_commande" value="'.$item->rowid.'"/>
					<span class="amount" title="'.$langs->trans("Total_TTC").' - '.$langs->trans("date_livraison").'">'.number_format($item->total_ttc,2,","," ").' '.$langs->getCurrencySymbol($conf->currency).' - '.dol_print_date($item->date_livraison,'day').'</span>
					<div class="badges comments '.$classNoComment.'" onclick="comment_clicked(this)" style="opacity:'.$opacity2.';" title="'.$langs->trans("Commentaire").'">
						<div class="badge" title="'.$langs->trans("Commentaire").'">
							<i class="fa fa-comment"></i>
							<span class="badge-text">'.$tot_cmnt.'</span>
						</div>
					</div>
					<div style="clear:both;"></div>
				</div>
			';
		 	$date_now = date("Y-m-d");
			
			if($item->fk_statut == 0){
				$html["DRAFT"]["content"] .= $content;
			}
			if(($item->fk_statut == 1 || $item->fk_statut == 2) && $item->facture == 0){
				$html["VALIDATED"]["content"] .= $content;
			}
			if($item->fk_statut == 3 && $item->facture == 0 ){
				$html["LNF"]["content"] .= $content;
			}
			if($item->fk_statut == 3 && $item->facture == 1 ){
				$html["LF"]["content"] .= $content;
			}
			if($item->fk_statut < 3 && $item->facture == 1 ){
				$html["FNL"]["content"] .= $content;
			}
		}
	}

	echo json_encode($html);
}

if ($action == "updatecommandeetat") {
	$from_etat 		= $_POST['from_etat'];
	$to_etat 		= $_POST['to_etat'];

	$commandeorig->fetch($id_commande);
	// print_r($commandeorig);
	// die();

	if ($to_etat == 1 && $from_etat == 0) {
		$result = $commandeorig->valid($user);
	}

	if ($to_etat == 1 && ($from_etat == 2 || $from_etat == 3)) {
		$result = $commandeorig->set_reopen($user);
	}

	if ($to_etat == 1 && $from_etat == 4) {
		$data = array(
			'fk_statut' => 	1,
			'facture' =>  0
		);
		$isvalid = $commande->update($id_commande, $data);
		$html = "true";
	}

	if ($to_etat == 2) {
		$data = array(
			'fk_statut' => 	3,
			'facture' =>  0
		);
		$isvalid = $commande->update($id_commande, $data);
		$html = "true";
	}

	if ($to_etat == 3) {
		$data = array(
			'fk_statut' => 	3,
			'facture' =>  1
		);
		$isvalid = $commande->update($id_commande, $data);
		$html = "true";
	}

	if ($to_etat == 4) {
		$data = array(
			'fk_statut' => 	1,
			'facture' =>  1
		);
		$isvalid = $commande->update($id_commande, $data);
		$html = "true";
	}



	// if ($to_etat == 1) {
	// 	if ($commandeorig->statut == 0) {
	// 		$result = $commandeorig->validate($user);
	// 	}else{
	// 		$result = $commandeorig->reopen($user, 1);
	// 	}
	// }else{
	// 	if ($to_etat < $commandeorig->statut) {
	// 		$result = $commandeorig->reopen($user, $to_etat);
	// 	}else{
	// 		$result = $commandeorig->cloture($user, $to_etat,"");
	// 	}
	// }
	$html = "true";
	echo json_encode($html);
}
// $result = $object->cloture($user, GETPOST('statut','int'), GETPOST('note_private','none')); //signed or no signed
// $result=$object->cloture($user, 4, ''); //billed
// $result=$object->reopen($user, 1); // reopen

















































// $action = "getallcomments";
if ($action == "getallcomments") {
	$comments->fetchAll("DESC", "created_at", "", "", " AND id_commande = ".$id_commande);
	$content = "";
	
	// print $img;
	if (count($comments->rows) > 0) {
		for ($i=0; $i < count($comments->rows) ; $i++) {
			$item = $comments->rows[$i];
			$tmpuser->fetch($item->id_user);
			$img = $form->showphoto('userphoto',$tmpuser,100);
			$content .= '
				<div class="one_comment" id="comment_'.$item->rowid.'">
					<input type="hidden" class="id_comment" value="'.$item->rowid.'"/>
					<input type="hidden" class="id_user" value="'.$item->id_user.'"/>
					<span class="image" >'.$img.'</span>
					<b class="name" >'.$tmpuser->lastname.' '.$tmpuser->firstname.'</b>
					<span class="cm_created_at">'.$item->created_at.'</span>
					<span class="cm_created_at">';
					if ($item->modified > 0) {
			$content .= '('.$langs->trans("modifié").')';
					}
			$content .='</span>';
					if ($tmpuser->id == $user->id) {
			$content .='<a class="actions_cmt supprimer_cmt" onclick="delete_comment(this);" href="#">'.$langs->trans("Supprimer").'</a>
					<a class="actions_cmt modifier_cmt" onclick="edit_comment(this);" href="#">'.$langs->trans("Modifier").'</a><br>';
					}
			$content .='<div class="commentaire_txt">';
			$content .= nl2br($item->comment);

			$dire = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$item->rowid.'/';
			if (file_exists($dire)){
			$images = scandir($dire);
			$content .= '<div class="files_joints"><ul class="list_joints">';
			$dire = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$item->rowid.'/';
			foreach ($images as $img) {
			    if (!in_array($img,array(".",".."))) 
			    { 
			        $ext = explode(".", $img);
			        $ext = $ext[count($ext) - 1];
			        $filename = explode("_uplodnc_", $img);
			        $picto = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/images/'.$ext.'.png';
			        $nopicto = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/images/file.png';
			        if ($ext == "pdf") {
			            $content .= '<li>';
			                $content .= '<a target="_blank" href="'.$dire.$img.'" class=""  title="'.$filename[1].'"><img src="'.$picto.'" /></a>';
			            $content .= '</li>';
			        }elseif (strtolower($ext) == "png" || strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
			            $content .= '<li class="png">';
			                $content .= '<a href="'.$dire.$img.'" class="lightbox_trigger" onclick="consulter_img(this,event)"  title="'.$filename[1].'"><img src="'.$dire.$img.'" /></a>';
			            $content .= '</li>';
			        }else{
			            $content .= '<li>';
			            if (file_exists(DOL_DOCUMENT_ROOT.$customtxt.'/teamview/images/'.$ext.'.png')) {
			                $content .= '<a href="'.$dire.$img.'" class="" download  title="'.$filename[1].'"><img src="'.$picto.'" /></a>';
			            }else{
			                $content .= '<a href="'.$dire.$img.'" class="" download  title="'.$filename[1].'"><img src="'.$nopicto.'" /></a>';
			            }
			            $content .= '</li>';
			        }
			    }
			}
			$content .= '</ul><div style="clear:both;"></div></div>';
			}
			$content .= '</div>';
			if ($tmpuser->id == $user->id) {
			$content .= '
						<div class="commentaire_txt_input" style="display:none;">
							<textarea class="textarea_comment commande_comment_edit" rows="4" onkeyup="comment_change_edit(this)"></textarea>
							<form method="POST" action="'.$_SERVER["PHP_SELF"].'" class="photos" enctype="multipart/form-data" onsubmit="upload_file(this,event)">';
							$dire = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$item->rowid.'/';
							if (file_exists($dire)){
							$images = scandir($dire);
							$content .= '<div class="files_joints edit"><ul class="list_joints">';
							$dire = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$item->rowid.'/';
							foreach ($images as $img) {
							    if (!in_array($img,array(".",".."))) 
							    { 
							        $ext = explode(".", $img);
							        $filename = explode("_uplodnc_", $img);
							        $ext = $ext[count($ext) - 1];
							        $picto = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/images/'.$ext.'.png';
							        $nopicto = DOL_MAIN_URL_ROOT.$customtxt.'/teamview/images/file.png';
							        if ($ext == "pdf") {
							            $content .= '<li>';
							                $content .= '<a href="'.$dire.$img.'" datafile="'.$img.'" class="delete_file" onclick="to_delete_file(this,event,'.$item->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
							            $content .= '</li>';
							        }elseif (strtolower($ext) == "png" || strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
							            $content .= '<li class="png">';
							                $content .= '<a href="'.$dire.$img.'" datafile="'.$img.'" class="delete_file" onclick="to_delete_file(this,event,'.$item->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$dire.$img.'" /></a>';
							            $content .= '</li>';
							        }else{
							            $content .= '<li>';
							            if (file_exists(DOL_DOCUMENT_ROOT.$customtxt.'/teamview/images/'.$ext.'.png')) {
							                $content .= '<a href="'.$dire.$img.'" datafile="'.$img.'" class="delete_file" onclick="to_delete_file(this,event,'.$item->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
							            }else{
							                $content .= '<a href="'.$dire.$img.'" datafile="'.$img.'" class="delete_file" onclick="to_delete_file(this,event,'.$item->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$nopicto.'" /></a>';
							            }
							            $content .= '</li>';
							        }
							    }
							}
							$content .= '</ul><div style="clear:both;"></div>';
							$content .= '<input type="hidden" name="files_deleted" class="files_deleted" />';
							$content .= '</div>';
							}
			$content .= '			<div class="one_file">
				        			<span class="add_joint" onclick="trigger_upload_file(this)"><i class="fa fa-paperclip"></i></span>
					        		<input class="add_photo" type="file" name="photo[]" onchange="change_upload_file(this)"/>
			        			</div>
			        			<span class="add_plus" onclick="new_input_joint(this)"><i class="fa fa-plus"></i></span>
			        			<div></div>
			        			<hr>
			        		</form>
							<button class="comment_btn update_comment button button_save_" onclick="update_comment(this);">'.$langs->trans("save").'</button> 
							<span class="cancel_cmt" onclick="cancel_cmt(this);" title="'.$langs->trans("Annuler").'"><i class="fa fa-times"></i></span>
						</div>';
			}
	$content .= '</div>';

		}
	}
	echo json_encode($content);
}

if ($action == "create_comment") {
	$id_user 		= $_POST['id_user'];
	$created_at 	= $_POST['created_at'];
	$comment 		= $_POST['comment'];

	$created_at 	= date('Y-m-d H:i:s');

	$data = array(
        'id_commande' =>  $id_commande,
        'id_user' =>  $id_user,
        'modified' =>  0,
        'created_at' =>  $created_at,
        'comment' =>  $comment
    );
	$id = $comments->create(0,$data);

	if(isset($_FILES['files'])) {  
		$dire_file = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id.'/';
	    mkdir($dire_file, 0777, true);
	    $names = array();
		foreach ($_FILES["files"]["name"] as $key => $value) {
	        if ($error == UPLOAD_ERR_OK) {
	            $tmp_name = $_FILES["files"]["tmp_name"][$key];
	            $name = $_FILES["files"]["name"][$key];

	            if(in_array($name,$names))
	                $name = $key.'-'.$name;

	            $names[$name] = $name;

	            $newfile=$dire_file.uniqid().'_uplodnc_'.dol_sanitizeFileName($name);
	            // dol_move_uploaded_file($tmp_name, $newfile, 1);
             	move_uploaded_file( $tmp_name, $newfile );
	        }
	    }
    }
	$html = "done";
	echo json_encode($html);
}

if ($action == "update_comment") {
	$id_comment 	= $_POST['id_comment'];
	$modified 		= $_POST['modified'];
	$comment 		= $_POST['comment'];
	$files_deleted 	= $_POST['files_deleted'];
	$data = array(
        'modified' =>  $modified,
        'comment' =>  $comment
    );
    $dire = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id_comment.'/';
    if($files_deleted){
        $files_deleted = explode(',', $files_deleted);
        foreach ($files_deleted as $d) {
            unlink($dire.$d);
        }
    }
	$comments->update($id_comment,$data);
	$html = "done";
	echo json_encode($html);
}

if ($action == "delete_comment") {
	$id_comment = $_POST['id_comment'];
	$dire = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id_comment.'/';
	$files = glob(DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id_comment.'/*');
	if (file_exists($dire)){
		foreach($files as $file){
		    unlink($file);
		}
		rmdir($dire);
	}
	$comments->fetch($id_comment);
	$comments->delete();
	$html = "done";
	echo json_encode($html);
}

if ($action == "upload_file") {
	$id_comment 	= $_POST['id_comment'];
	if(isset($_FILES['files'])) {  
		$dire_file = DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id_comment.'/';
	    mkdir($dire_file, 0777, true);
	    $names = array();
		foreach ($_FILES["files"]["name"] as $key => $value) {
	        if ($error == UPLOAD_ERR_OK) {
	            $tmp_name = $_FILES["files"]["tmp_name"][$key];
	            $name = $_FILES["files"]["name"][$key];

	            if(in_array($name,$names))
	                $name = $key.'-'.$name;

	            $names[$name] = $name;

	            $newfile=$dire_file.uniqid().'_uplodnc_'.dol_sanitizeFileName($name);
	            // dol_move_uploaded_file($tmp_name, $newfile, 1);
             	move_uploaded_file( $tmp_name, $newfile );
	        }
	    }
    }

	// if(isset($_FILES['files']['name']))
	// {  
	//     $uploads_dir =  DOL_DOCUMENT_ROOT.$customtxt.'/teamview/files_commentaire/commandes/'.$id_comment.'/';
	//     mkdir($uploads_dir, 0777, true);
	// 	$target_path = $uploads_dir.uniqid() . basename( $_FILES[ 'files' ][ 'name' ] );
	// 	if ( move_uploaded_file( $_FILES[ 'files' ][ 'tmp_name' ], $target_path ) )
	// 	{
	// 	    echo 'File uploaded: ' . $target_path;
	// 	}
	// 	else
	// 	{
	// 	    echo 'Error in uploading files ' . $target_path;
	// 	}
	// }
	$html = "done";
	echo json_encode($html);
}

if ($action == "update_avanc_tasks") {
	$progress_tasks = $_POST['progress_tasks'];

	$params = array();
	parse_str($progress_tasks, $params);
	// print_r($params);
    $sql = "";
	foreach ($params['progress_tasks'] as $key => $value) {
		$sql = "UPDATE " . MAIN_DB_PREFIX ."projet_task SET progress = ".$value." WHERE rowid = " . $key."; \n";
		$taches->update_task_avanc($sql);
	} 

	$html = "done";
	echo json_encode($html);
}


if ($action == "getchildtasks") {

	// $taches->fetchAll("", "", "", "", " AND fk_projet = ".$id_projet);
	// if (count($taches->rows) > 0) {
	// 	for ($i=1; $i < count($taches->rows) ; $i++) {
	// 		$item = $taches->rows[$i];
	// 		echo 'parent :'.$item->fk_task_parent.' | '.$item->rowid.' : '.$item->ref.' - '.$item->label."<br>";
	// 		$arr0[$item->fk_task_parent] = array('id' => $item->rowid, 'parent' => $item->fk_task_parent);
	// 	}
	// }
	// echo "<br>--------------------------------------------------------</br>";

	// print_r($arr0);

	// echo "<br>--------------------------------------------------------</br>";

	// $arr2[1] = array('id' => 4, 'parent' => 0);
	// $arr2[2] = array('id' => 8, 'parent' => 1);
	// $arr2[3] = array('id' => 9, 'parent' => 1);
	// $arr2[4] = array('id' => 10, 'parent' => 1);
	// $arr2[5] = array('id' => 11, 'parent' => 2);

	
	// // $arr2[1] = array('id' => 1, 'parent' => 0);
	// // $arr2[2] = array('id' => 2, 'parent' => 1);
	// // $arr2[3] = array('id' => 3, 'parent' => 2);

	// // print_r($arr2);
	// $children = array();
	// foreach($arr0 as $key => $page){
	//     $parent = (int)$page['parent'];
	//     if(!isset($children[$parent]))
	//         $children[$parent] = array();
	//     $children[$parent][$key] = array('id' => $page['id']);
	// }

	// $new_pages = $teamview->recursive_append_children($children[0], $children);
	// print_r($new_pages);




	$html = "";

	// global $taskallows;
	// $taskallows = $taches->arrayofallowstasks($user->id);

	$tasksarray=$taskstatic->getTasksArray(0, 0, $id_projet, $filteronthirdpartyid, 0);

	$tmpuser=new User($db);
	if ($search_user_id > 0) $tmpuser->fetch($search_user_id);
	$tasksrole=($tmpuser->id > 0 ? $taskstatic->getUserRolesForProjectsOrTasks(0, $tmpuser, $id_projet, 0) : '');


	if (count($tasksarray) > 0)
	{
	    // Show all lines in taskarray (recursive function to go down on tree)
		$j=0; $level=0;
		$html .= $teamview->projectLinesa($j, $id_commande, $tasksarray, $level, true, 0, $tasksrole, $id_projet, 1, $id_projet);
	}
	else
	{
		$html .= '<tr class="oddeven"><td colspan="2" class="opacitymedium" align="center">Aucune sous-tâche</td></tr>';
	}



	// echo $html;
	echo json_encode($html);
}

if ($action == "get_contacts_users_project") {
	$projet_own->fetch($id_projet);
	$html['others'] = '';
	$other = 0;
	$html['contacts'] = '<div class="visiblite"><b>Visibilité :</b></div>';
	if ($projet_own->public == 0) {
		$elements_contacts->fetchAll("DESC", "", "", "", " AND element_id = ".$id_projet." AND fk_c_type_contact in (SELECT rowid from ".MAIN_DB_PREFIX."c_type_contact where element = 'project') ORDER BY rowid ASC");
		if (count($elements_contacts->rows) > 0) {
			for ($i=0; $i < count($elements_contacts->rows) ; $i++) {
				$item = $elements_contacts->rows[$i];
				$user_id = $item->fk_socpeople;
				$tmpuser->fetch($user_id);
				$img = $form->showphoto('userphoto',$tmpuser,100);
				if ($i > 5) {
					$other++;
					$html['others'] .= "- ".$tmpuser->lastname." ".$tmpuser->firstname."\n";
				}else{
					$html['contacts'] .= '<span title="'.$tmpuser->lastname.' '.$tmpuser->firstname.'">'.$img.'</span>';
				}
			}
		}
		if ($other > 0) {
			$html['allcontacts'] = $html['contacts'].'<span class="number_other" title="'.$html['others'].'">'.$other.'</span>';
		}else{
			$html['allcontacts'] = $html['contacts'];
		}
	}else{
		$html['contacts'] .= '<div class="tous"><b>Tout le monde</b></div>';
		$html['allcontacts'] = $html['contacts'];
	}
	// Projet info ----------------------
	if ($projet_own->fk_statut == 2)
		$html['etat'] = "<span class='etat_color cloturer_st'></span> Clôturé";
	elseif ($projet_own->fk_statut == 0)
		$html['etat'] = "<span class='etat_color brouillon_st'></span> Brouillon";
	else
		$html['etat'] = "<span class='etat_color ouvert_st'></span> Ouvert";
	if (!empty($projet_own->fk_soc)) {
		$societe->fetch($projet_own->fk_soc);
		$html['tiers'] = $societe->nom;
	}else{
		$html['tiers'] = "-";
	}
	$debut = "";
	if ($projet_own->dateo) {
		$debut = $projet_own->dateo;
		$debut = explode('-', $debut);
		$debut = $debut[2]."/".$debut[1]."/".$debut[0];
	}
	$fin = "";
	if ($projet_own->datee) {
		$fin = $projet_own->datee;
		$fin = explode('-', $fin);
		$fin = $fin[2]."/".$fin[1]."/".$fin[0];
	}

	$html['dates'] = $debut.' - '.$fin;
	// End Projet info ----------------------
	echo json_encode($html);
}

if ($action == "check_user_permission_projet") {
	if ($user->rights->projet->creer)
		$result = "yes";
	else
		$result = "no";
	echo json_encode($result);
}