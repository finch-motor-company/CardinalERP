<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 
 
class factures extends Commonobject{ 
	public $errors = array();
	public $id;
	public $rowid;
	public $dateo;
	public $datee;
	public $fk_soc;
	public $fk_statut;
	public $total_ttc;
	public $date_lim_reglement;
	public $close_code;
	public $public;

	public function __construct(DoliDBMysqli $db){ 
		$this->db = $db;
		return 1;
	}
	

	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT * FROM ";
		$sql .= MAIN_DB_PREFIX ."facture";
		if (!empty($filter)) {
			$sql .= " WHERE 1>0 ".$filter;
		}
		
		// echo $sql;
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		if (!empty($limit)) {
			if($offset==1)
				$sql .= " limit ".$limit;
			else
				$sql .= " limit ".$offset.",".$limit;				
		}

		// echo $sql;
		$this->rows = array();
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
				$line->id 	= $obj->rowid;
				$line->rowid 	= $obj->rowid;
				$line->facnumber 	= $obj->facnumber;
				$line->fk_soc 	= $obj->fk_soc;
				$line->fk_statut = $obj->fk_statut;
				$line->total_ttc = $obj->total_ttc;
				$line->date_lim_reglement = $obj->date_lim_reglement;
				$line->close_code = $obj->close_code;
				$this->rows[] 	= $line;
			}
			
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}

	}

	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid = ' . $id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);
				$this->id 		= $obj->rowid;
				$this->rowid 		= $obj->rowid;
				$this->facnumber 		= $obj->facnumber;
				$this->fk_soc 		= $obj->fk_soc;
				$this->fk_statut 	= $obj->fk_statut;
				$this->total_ttc 	= $obj->total_ttc;
				$this->date_lim_reglement 	= $obj->date_lim_reglement;
				$this->close_code 	= $obj->close_code;
			}

			$this->db->free($resql);

			if ($numrows) {
				return 1 ;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	function getNomUrl($withpicto=0, $option='', $get_params='', $notooltip=0, $save_lastsearch_value=-1)
	{
		global $langs, $conf, $user;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result='';
		$label='';
		$url='';

		if ($user->rights->facture->lire)
		{
			$label = '<u>' . $langs->trans("ShowFacture") . '</u>';
			if (! empty($this->facnumber))
				$label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->facnumber;
			if (! empty($this->ref_client))
				$label.= '<br><b>'.$langs->trans('RefCustomer').':</b> '.$this->ref_client;
			if (! empty($this->total_ht))
				$label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_tva))
				$label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			if (! empty($this->total_ttc))
				$label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			if ($option == '') {
				$url = DOL_URL_ROOT.'/compta/facture/card.php?id='.$this->id. $get_params;
			}
			if ($option == 'compta') {  // deprecated
				$url = DOL_URL_ROOT.'/compta/facture/card.php?id='.$this->id. $get_params;
			}
			if ($option == 'expedition') {
				$url = DOL_URL_ROOT.'/expedition/facture.php?id='.$this->id. $get_params;
			}
			if ($option == 'document') {
				$url = DOL_URL_ROOT.'/compta/facture/document.php?id='.$this->id. $get_params;
			}

			if ($option != 'nolink')
			{
				// Add param to save lastsearch_values or not
				$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
				if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
				if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
			}
		}

		$linkclose='';
		if (empty($notooltip) && $user->rights->facture->lire)
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowFacture");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), $this->picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->facnumber;
		$result .= $linkend;

		return $result;
	}
	public function select_all_clients($selected=0,$name='select_',$showempty=1,$val="rowid",$opt="label",$id=''){
	    global $conf;
	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;
	    if ($conf->use_javascript_ajax)
	    {
	        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	        $comboenhancement = ajax_combobox('select_'.$id);
	        $moreforfilter.=$comboenhancement;
	        $nodatarole=($comboenhancement?' data-role="none"':'');
	    }
	
	    $moreforfilter.='<select class="flat" id="select_'.$id.'" name="'.$name.'" '.$nodatarole.'>';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

    	$sql = "SELECT ".$val.",".$opt." FROM ".MAIN_DB_PREFIX."societe where client = 1 OR client = 3";

    	$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->$val.'" data-ref="'.$obj->$opt.'"';
	            if ($obj->$val == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$obj->$opt.'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
	    return $moreforfilter;
	}

	public function update($id, array $data,$echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;

		$sql = 'UPDATE ' . MAIN_DB_PREFIX .'facture SET ';

		if (count($data) && is_array($data))
			foreach ($data as $key => $val) {
				$val = is_numeric($val) ? $val : '"'. $val .'"';
				$sql .= '`'. $key. '` = '. $val .',';
			}

		$sql  = substr($sql, 0, -1);
		$sql .= ' WHERE rowid = ' . $id;

		// $this->db->begin();

		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
			return -1;
		} 
		return 1;
	}
} 

?>