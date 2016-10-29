<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       product/stock/productlot_list.php
 *		\ingroup    stock
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2016-05-17 12:22
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('/product/stock/class/productlot.class.php');

// Load traductions files requiredby by page
$langs->load("stocks");
$langs->load("productbatch");
$langs->load("other");
$langs->load("users");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');


$search_entity=GETPOST('search_entity','int');
$search_product=GETPOST('search_product','alpha');
$search_batch=GETPOST('search_batch','alpha');
$search_fk_user_creat=GETPOST('search_fk_user_creat','int');
$search_fk_user_modif=GETPOST('search_fk_user_modif','int');
$search_import_key=GETPOST('search_import_key','int');


$search_myfield=GETPOST('search_myfield');
$optioncss = GETPOST('optioncss','alpha');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.batch"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
	//accessforbidden();
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('productbatchlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('productbatch');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    't.ref'=>'Ref',
    't.note_public'=>'NotePublic',
);

// Definition of fields for list
$arrayfields=array(
	//'t.entity'=>array('label'=>$langs->trans("Fieldentity"), 'checked'=>1),
	't.batch'=>array('label'=>$langs->trans("Batch"), 'checked'=>1),
    't.fk_product'=>array('label'=>$langs->trans("Product"), 'checked'=>1),
	't.eatby'=>array('label'=>$langs->trans("EatByDate"), 'checked'=>1),
	't.sellby'=>array('label'=>$langs->trans("SellByDate"), 'checked'=>1),
	//'t.import_key'=>array('label'=>$langs->trans("ImportKey"), 'checked'=>1),
    //'t.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))),
    //'t.fk_user_creat'=>array('label'=>$langs->trans("UserCreationShort"), 'checked'=>0, 'position'=>500),
	//'t.fk_user_modif'=>array('label'=>$langs->trans("UserModificationShort"), 'checked'=>0, 'position'=>500),
    't.datec'=>array('label'=>$langs->trans("DateCreationShort"), 'checked'=>0, 'position'=>500),
    't.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    //'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val) 
   {
       $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
   }
}

// Load object if id or ref is provided as parameter
$object=new Productlot($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
    $result=$object->fetch($id,$ref);
    if ($result < 0) dol_print_error($db);
}



/*
 * Actions 
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $search_entity='';
    $search_product='';
    $search_batch='';
    $search_fk_user_creat='';
    $search_fk_user_modif='';
    $search_import_key='';
	$search_date_creation='';
	$search_date_update='';
	$toselect='';
	$search_array_options=array();
}


if (empty($reshook))
{
    $objectclass='ProductLot';
    $objectlabel='LotSerial';
    $permtoread = $user->rights->stock->read;
    $permtodelete = $user->rights->stock->delete;
    $uploaddir = $conf->stock->dir_output;
    //include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}




/*
 * VIEW
 */

$now=dol_now();

$form=new Form($db);
$productstatic=new Product($db);

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
$title = $langs->trans('LotSerialList');
llxHeader('', $title, $help_url);

// Put here content of your page

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


$sql = "SELECT";
$sql.= " t.rowid,";
$sql.= " t.entity,";
$sql.= " t.fk_product,";
$sql.= " t.batch,";
$sql.= " t.eatby,";
$sql.= " t.sellby,";
$sql.= " t.datec as date_creation,";
$sql.= " t.tms as date_update,";
$sql.= " t.fk_user_creat,";
$sql.= " t.fk_user_modif,";
$sql.= " t.import_key,";
$sql.= " p.fk_product_type as product_type,";
$sql.= " p.ref as product_ref,";
$sql.= " p.label as product_label";
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."product_lot as t";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot_extrafields as ef on (u.rowid = ef.fk_object)";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
$sql.= " WHERE p.rowid = t.fk_product";
//$sql.= " WHERE u.entity IN (".getEntity('mytable',1).")";

if ($search_entity) $sql.= natural_search("entity",$search_entity);
if ($search_product) $sql.= natural_search("p.ref",$search_product);
if ($search_batch) $sql.= natural_search("batch",$search_batch);
if ($search_fk_user_creat) $sql.= natural_search("fk_user_creat",$search_fk_user_creat);
if ($search_fk_user_modif) $sql.= natural_search("fk_user_modif",$search_fk_user_modif);
if ($search_import_key) $sql.= natural_search("import_key",$search_import_key);


if ($sall)          $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield,$sortorder);
//$sql.= $db->plimit($conf->liste_limit+1, $offset);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}	

$sql.= $db->plimit($limit+1, $offset);


dol_syslog($script_file, LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    $arrayofselected=is_array($toselect)?$toselect:array();
    
    $params='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
    if ($search_entity != '') $params.= '&amp;search_entity='.urlencode($search_entity);
    if ($search_product != '') $params.= '&amp;search_product='.urlencode($search_product);
    if ($search_batch != '') $params.= '&amp;search_batch='.urlencode($search_batch);
    if ($search_fk_user_creat != '') $params.= '&amp;search_fk_user_creat='.urlencode($search_fk_user_creat);
    if ($search_fk_user_modif != '') $params.= '&amp;search_fk_user_modif='.urlencode($search_fk_user_modif);
    if ($search_import_key != '') $params.= '&amp;search_import_key='.urlencode($search_import_key);
    if ($optioncss != '') $param.='&optioncss='.$optioncss;
    // Add $param from extra fields
    foreach ($search_array_options as $key => $val)
    {
        $crit=$val;
        $tmpkey=preg_replace('/search_options_/','',$key);
        if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    } 

    $arrayofmassactions =  array(
        //'presend'=>$langs->trans("SendByMail"),
        //'builddoc'=>$langs->trans("PDFMerge"),
    );
    //if ($user->rights->stock->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
    if ($massaction == 'presend') $arrayofmassactions=array();
    $massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);
	
	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    /*$moreforfilter = '';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
    $moreforfilter.= '</div>';*/
    
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;
    
    if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
	    print '</div>';
	}

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    // Fields title
    print '<tr class="liste_titre">';
    if (! empty($arrayfields['t.entity']['checked']))     print_liste_field_titre($arrayfields['t.entity']['label'],$_SERVER['PHP_SELF'],'t.entity','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.batch']['checked']))      print_liste_field_titre($arrayfields['t.batch']['label'],$_SERVER['PHP_SELF'],'t.batch','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.fk_product']['checked'])) print_liste_field_titre($arrayfields['t.fk_product']['label'],$_SERVER['PHP_SELF'],'t.fk_product','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.eatby']['checked']))      print_liste_field_titre($arrayfields['t.eatby']['label'],$_SERVER['PHP_SELF'],'t.eatby','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.sellby']['checked']))      print_liste_field_titre($arrayfields['t.sellby']['label'],$_SERVER['PHP_SELF'],'t.sellby','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.fk_user_creat']['checked'])) print_liste_field_titre($arrayfields['t.fk_user_creat']['label'],$_SERVER['PHP_SELF'],'t.fk_user_creat','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.fk_user_modif']['checked'])) print_liste_field_titre($arrayfields['t.fk_user_modif']['label'],$_SERVER['PHP_SELF'],'t.fk_user_modif','',$params,'',$sortfield,$sortorder);
    if (! empty($arrayfields['t.import_key']['checked']))    print_liste_field_titre($arrayfields['t.import_key']['label'],$_SERVER['PHP_SELF'],'t.import_key','',$params,'',$sortfield,$sortorder);
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val) 
	   {
           if (! empty($arrayfields["ef.".$key]['checked'])) 
           {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
           }
	   }
	}
    // Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (! empty($arrayfields['t.datec']['checked']))  print_liste_field_titre($arrayfields['t.datec']['label'],$_SERVER["PHP_SELF"],"t.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['t.tms']['checked']))    print_liste_field_titre($arrayfields['t.tms']['label'],$_SERVER["PHP_SELF"],"t.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	//if (! empty($arrayfields['t.status']['checked'])) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"t.status","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
    print '</tr>'."\n";

    // Fields title search
	print '<tr class="liste_titre">';
    if (! empty($arrayfields['t.entity']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_entity" value="'.$search_entity.'" size="8"></td>';
    if (! empty($arrayfields['t.batch']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_batch" value="'.$search_batch.'" size="8"></td>';
    if (! empty($arrayfields['t.fk_product']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_product" value="'.$search_product.'" size="8"></td>';
    if (! empty($arrayfields['t.eatby']['checked'])) print '<td class="liste_titre"></td>';
    if (! empty($arrayfields['t.sellby']['checked'])) print '<td class="liste_titre"></td>';
    if (! empty($arrayfields['t.fk_user_creat']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_fk_user_creat" value="'.$search_fk_user_creat.'" size="10"></td>';
    if (! empty($arrayfields['t.fk_user_modif']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_fk_user_modif" value="'.$search_fk_user_modif.'" size="10"></td>';
    if (! empty($arrayfields['t.import_key']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_import_key" value="'.$search_import_key.'" size="10"></td>';
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
        foreach($extrafields->attribute_label as $key => $val) 
        {
            if (! empty($arrayfields["ef.".$key]['checked']))
            {
                $align=$extrafields->getAlignFlag($key);
                $typeofextrafield=$extrafields->attribute_type[$key];
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
            	if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
				{
				    $crit=$val;
    				$tmpkey=preg_replace('/search_options_/','',$key);
    				$searchclass='';
    				if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
    				if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
    				print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
				}
                print '</td>';
            }
        }
	}
    // Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['t.datec']['checked']))
    {
        // Date creation
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (! empty($arrayfields['t.tms']['checked']))
    {
        // Date modification
        print '<td class="liste_titre">';
        print '</td>';
    }
    /*if (! empty($arrayfields['u.statut']['checked']))
    {
        // Status
        print '<td class="liste_titre" align="center">';
        print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
        print '</td>';
    }*/
    // Action column
	print '<td class="liste_titre" align="right">';
	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
    print $searchpitco;
    print '</td>';
	print '</tr>'."\n";
        
    
	$i=0;
	$var=true;
	$totalarray=array();
    while ($i < min($num, $limit))
    {
        $obj = $db->fetch_object($resql);
        if ($obj)
        {
            $var = !$var;
            
            // You can use here results
            print '<tr '.$bc[$var].'>';
            if (! empty($arrayfields['t.entity']['checked'])) 
            {
                print '<td>'.$obj->entity.'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.batch']['checked'])) 
            {
                print '<td>'.$obj->batch.'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.fk_product']['checked'])) 
            {
                $productstatic->id=$obj->fk_product;
                $productstatic->type=$obj->product_type;
                $productstatic->ref=$obj->product_ref;
                $productstatic->label=$obj->product_label;
                print '<td>'.$productstatic->getNomUrl(1).'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.eatby']['checked'])) 
            {
                print '<td>'.dol_print_date($db->jdate($obj->eatby), 'day').'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.sellby']['checked'])) 
            {
                print '<td>'.dol_print_date($db->jdate($obj->sellby), 'day').'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.fk_user_creat']['checked'])) 
            {
                print '<td>'.$obj->fk_user_creat.'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.fk_user_modif']['checked'])) 
            {
                print '<td>'.$obj->fk_user_modif.'</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['t.import_key']['checked']))
            {
                print '<td>'.$obj->import_key.'</td>';
                if (! $i) $totalarray['nbfield']++;
            }            
            // Extra fields
    		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
    		{
    		   foreach($extrafields->attribute_label as $key => $val) 
    		   {
    				if (! empty($arrayfields["ef.".$key]['checked'])) 
    				{
    					print '<td';
    					$align=$extrafields->getAlignFlag($key);
    					if ($align) print ' align="'.$align.'"';
    					print '>';
    					$tmpkey='options_'.$key;
    					print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
    					print '</td>';
    		            if (! $i) $totalarray['nbfield']++;
    				}
    		   }
    		}
            // Fields from hook
    	    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
    		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
        	// Date creation
            if (! empty($arrayfields['t.datec']['checked']))
            {
                print '<td align="center">';
                print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            // Date modification
            if (! empty($arrayfields['t.tms']['checked']))
            {
                print '<td align="center">';
                print dol_print_date($db->jdate($obj->date_update), 'dayhour');
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
            }
            // Status
            /*
            if (! empty($arrayfields['u.statut']['checked']))
            {
    		  $userstatic->statut=$obj->statut;
              print '<td align="center">'.$userstatic->getLibStatut(3).'</td>';
            }*/

            // Action column
	        print '<td class="nowrap" align="center">';
    	    if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	        {
    	        $selected=0;
    			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
    			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
	        }
    	    print '</td>';
            if (! $i) $totalarray['nbfield']++;

            print '</tr>';
        }
        $i++;
    }

    // Show total line
    if (isset($totalarray['totalhtfield']))
    {
        print '<tr class="liste_total">';
        $i=0;
        while ($i < $totalarray['nbfield'])
        {
            $i++;
            if ($i == 1)
            {
                if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
                else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
            }
            elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
            elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
            elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
            else print '<td></td>';
        }
        print '</tr>';
    }
    
    $db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";

	print '</form>'."\n";
	
	/*
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
	{
	    // Show list of available documents
	    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	    $urlsource.=str_replace('&amp;','&',$param);
	
	    $filedir=$diroutputmassaction;
	    $genallowed=$user->rights->facture->lire;
	    $delallowed=$user->rights->facture->lire;
	
	    print '<br><a name="show_files"></a>';
	    $paramwithoutshowfiles=preg_replace('/show_files=1&?/','',$param);
	    $title=$langs->trans("MassFilesArea").' <a href="'.$_SERVER["PHP_SELF"].'?'.$paramwithoutshowfiles.'">('.$langs->trans("Hide").')</a>';
	
	    print $formfile->showdocuments('massfilesarea_orders','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
	}
	else
	{
	    print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
	}
	*/
}
else
{
    $error++;
    dol_print_error($db);
}


// End of page
llxFooter();
$db->close();
