<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/expensereport/index.php
 *  \ingroup    expensereport
 *  \brief      Page list of expenses
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport','','');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.date_create";
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;


/*
 * View
 */

$tripandexpense_static=new ExpenseReport($db);

$childids = $user->getAllChildIds();
$childids[]=$user->id;

//$help_url='EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones';
$help_url='';
llxHeader('',$langs->trans("ListOfFees"),$help_url);


$label=$somme=$nb=array();

$totalnb=$totalsum=0;
$sql = "SELECT tf.code, tf.label, count(de.rowid) as nb, sum(de.total_ht) as km";
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d, ".MAIN_DB_PREFIX."expensereport_det as de, ".MAIN_DB_PREFIX."c_type_fees as tf";
$sql.= " WHERE de.fk_expensereport = d.rowid AND de.fk_c_type_fees = tf.id";
// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous))
{
	$sql.= " AND d.fk_user_author IN (".join(',',$childids).")\n";
}
$sql.= " GROUP BY tf.code, tf.label";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $somme[$objp->code] = $objp->km;
        $nb[$objp->code] = $objp->nb;
        $label[$objp->code] = $objp->label;
        $totalnb += $objp->nb;
        $totalsum += $objp->km;
        $i++;
    }
    $db->free($result);
} else {
    dol_print_error($db);
}


print load_fiche_titre($langs->trans("ExpensesArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("Statistics").'</td>';
print "</tr>\n";

$listoftype=$tripandexpense_static->listOfTypes();
foreach ($listoftype as $code => $label)
{
    $dataseries[]=array('label'=>$label,'data'=>(isset($somme[$code])?(int) $somme[$code]:0));
}

if ($conf->use_javascript_ajax)
{
    print '<tr '.$bc[0].'><td align="center" colspan="4">';
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',320,180,$data,1,'pie',0,'',0);
    print '</td></tr>';
}

print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print '<td align="right" colspan="3">'.price($totalsum,1,$langs,0,0,0,$conf->currency).'</td>';
print '</tr>';

print '</table>';



// Right area
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$max=10;

$langs->load("boxes");

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, d.rowid, d.ref, d.date_debut as dated, d.date_fin as datef, d.date_create as dm, d.total_ht, d.total_ttc, d.fk_statut as fk_status";
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d, ".MAIN_DB_PREFIX."user as u";
if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE u.rowid = d.fk_user_author";
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)) $sql.=' AND d.fk_user_author IN ('.join(',',$childids).')';
//$sql.= " AND d.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND d.fk_user_author = s.rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND d.fk_user_author = ".$socid;
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($max, 0);

$result = $db->query($sql);
if ($result)
{
    $var=false;
    $num = $db->num_rows($result);

    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",min($max,$num)).'</td>';
    print '<td align="right">'.$langs->trans("AmountHT").'</td>';
    print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
    print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
    print '<td>&nbsp;</td>';
    print '</tr>';
    if ($num)
    {
        $total_ttc = $totalam = $total = 0;

        $expensereportstatic=new ExpenseReport($db);
        $userstatic=new User($db);
        while ($i < $num && $i < $max)
        {
            $obj = $db->fetch_object($result);
            $expensereportstatic->id=$obj->rowid;
            $expensereportstatic->ref=$obj->ref;
            $userstatic->id=$obj->uid;
            $userstatic->lastname=$obj->lastname;
            $userstatic->firstname=$obj->firstname;
            print '<tr '.$bc[$var].'>';
            print '<td>'.$expensereportstatic->getNomUrl(1).'</td>';
            print '<td>'.$userstatic->getNomUrl(1).'</td>';
            print '<td align="right">'.price($obj->total_ht).'</td>';
            print '<td align="right">'.price($obj->total_ttc).'</td>';
            print '<td align="right">'.dol_print_date($db->jdate($obj->dm),'day').'</td>';
            print '<td align="right">';
            //print $obj->libelle;
			print $expensereportstatic->LibStatut($obj->fk_status,3);
            print '</td>';
            print '</tr>';
            $var=!$var;
            $i++;
        }

    }
    else
    {
        print '<tr '.$bc[$var].'><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    }
    print '</table><br>';
}
else dol_print_error($db);

print '</div></div></div>';

llxFooter();

$db->close();