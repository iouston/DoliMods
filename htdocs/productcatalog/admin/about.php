<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 * \file    admin/about.php
 * \ingroup productcatalog
 * \brief   About page of module ProductCatalog.
 *
 * ProductCatalogDescription.
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && file_exists("../../../../../main.inc.php")) $res=@include("../../../../../main.inc.php");
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");


// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/productcatalog.lib.php';

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("productcatalog@productcatalog");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$page_name = "ProductCatalogAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = productcatalogAdminPrepareHead();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("ProductCatalogName"),
	0,
	'productcatalog@productcatalog'
);

// About page goes here
echo $langs->trans("ProductCatalogAboutPage");

echo '<br>';

require_once '../core/modulebuilder/productcatalog/core/modules/modProductCatalog.class.php';
$tmpmodule = new ProductCatalog($db);
print $tmpmodule->getDescLong();

// Page end
dol_fiche_end();
llxFooter();
$db->close();
