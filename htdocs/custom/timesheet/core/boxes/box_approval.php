<?php
/* Copyright (C) 2016 delcroip <patrick@pmpd.eu>
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
 *	\file       htdocs/core/boxes/box_approval.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
$path=dirname(dirname(dirname(__FILE__)));
set_include_path($path);
require_once 'core/lib/timesheet.lib.php';
global $dolibarr_main_url_root_alt;
$res=0;


/**
 * Class to manage the box to show last invoices
 */
class box_approval extends ModeleBoxes
{
	var $boxcode="nbTsToApprove";
	var $boximg="timesheet";
	var $boxlabel="BoxApproval";
	var $depends = array("timesheet");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;




        $userid=  is_object($user)?$user->id:$user;
		$text =$langs->trans('Timesheet');
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text)
		);
                
        if ($user->rights->timesheet->approval) {
                        $sql = 'SELECT';
           $subordinate=implode(',',  getSubordinates($db, $userid,2));
           if($subordinate=='')$subordinate=0;
           $tasks=implode(',', array_keys(getTasks($db, $userid)));
           if($tasks=='')$tasks=0;
           // $sql.=' COUNT(t.rowid) as nb,';
            $sql.=' COUNT(DISTINCT t.rowid) as nbtsk, count(DISTINCT fk_project_task_timesheet) as nbtm ,t.recipient';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as t';
            $sql.= ' WHERE t.status IN ('.SUBMITTED.','.UNDERAPPROVAL.','.CHALLENGED.') AND ((t.recipient='.TEAM; 
            $sql.= ' AND t.fk_userid in ('.$subordinate.'))';//fixme should check subordinate and project
            $sql.= ' OR (t.recipient='.PROJECT.' and fk_projet_task in ('.$tasks.')))';
            $sql.= '  GROUP BY t.recipient ';
            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                while ($num>0){
                    $obj = $db->fetch_object($result);
                    if($obj->recipient=='project'){
                        $nbPrj=$obj->nbtsk;
                    }else if($obj->recipient=='team'){
                        $nbTm=$obj->nbtm;
                    }
                    $num--;
                    }

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans('team').': ',
                        'text2'=> $langs->trans('nbTsToApprove'),
                        'asis' => 1,
                    );

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="right"',
                        'text' => $nbTm,
                        'asis' => 1,
                    );
                    $this->info_box_contents[1][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans('project').': ',
                        'text2'=> $langs->trans('nbTsToApprove'),
                        'asis' => 1,
                    );

                    $this->info_box_contents[1][] = array(
                        'td' => 'align="right"',
                        'text' => $nbPrj,
                        'asis' => 1,
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }

        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
        }
    }

	/**
	 *  Method to show box
	 *
	 *  @param  array   $head       Array with properties of box title
	 *  @param  array   $contents   Array with properties of box lines
	 *  @return void
	 */
	function showBox($head = null, $contents = null,$nooutput = 0)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}
