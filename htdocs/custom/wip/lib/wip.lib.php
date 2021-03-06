<?php
/* Copyright (C) 2019 Peter Roberts
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
 * \file    wip/lib/wip.lib.php
 * \ingroup wip
 * \brief   Library files with common functions for WIP
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function wipAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("wip@wip");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/wip/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/wip/admin/advanced_setup.php", 1);
	$head[$h][1] = $langs->trans("Advanced");
	$head[$h][2] = 'advanced';
	$h++;
	$head[$h][0] = dol_buildpath("/wip/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@wip:/wip/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@wip:/wip/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'wip');

	return $head;
}

/**
 *  Show character in circle
 *
 * @param 	string 	$character			Character to be formatted
 * @return	formated character
 */
function wip_awesome_character($character)
{
print '
<!-- Create an icon wrapped by the fa-stack class -->
<span class="fa-stack">
    <!-- The icon that will wrap the number -->
    <span class="fa fa-circle-o fa-stack-2x"></span>
    <!-- a strong element with the custom content, in this case a number -->
    <strong class="fa-stack-1x">
	';
print $character;   
print '
    </strong>
</span>
';
}