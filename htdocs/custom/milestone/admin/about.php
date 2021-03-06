<?php
/* Copyright (C) 2014-2015 Regis Houssin  <regis.houssin@capnetworks.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		/milestone/admin/about.php
 * 	\ingroup	milestone
 * 	\brief		About Page
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory


// Libraries
require_once "../lib/milestone.lib.php";
require_once "../lib/PHP_Markdown/markdown.php";

// Translations
$langs->load("milestone@milestone");
$langs->load("admin");

// Access control
if (empty($user->admin))
	accessforbidden();

/*
 * View
 */

$wikihelp='EN:Module_Jalon_EN|FR:Module_Jalon_FR';
llxHeader('', $langs->trans("Module1790Name"), $wikihelp, '', 0, 0);

// Subheader
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ModuleSetup"), $linkback, 'milestone@milestone');

print '<br>';

// Configuration header
$head = milestoneadmin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Module1790Name"), 0, 'milestone@milestone');

// About page goes here

print '<br>';

$buffer = file_get_contents(dol_buildpath('/milestone/README.md',0));
print Markdown($buffer);

print '<br>';
print $langs->trans("MilestoneMoreModules").'<br>';
$url='https://www.inodbox.com/';
print '<a href="'.$url.'" target="_blank"><img border="0" width="250" src="'.dol_buildpath('/milestone/img/inodbox.png',1).'"></a>';
print '<br><br><br>';

print '<a target="_blank" href="'.dol_buildpath('/milestone/COPYING',1).'"><img src="'.dol_buildpath('/milestone/img/gplv3.png',1).'"/></a>';

llxFooter();
$db->close();
?>
