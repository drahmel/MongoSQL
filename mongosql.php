<?php 
/**
 * @version		$Id: mongosql.php 20806 2011-02-21 19:44:59Z drahmel $
 * @package		mongosql
 * @copyright	Copyright (C) 2011 Dan Rahmel All rights reserved.
 * @license		Apache license
 */

defined('SYSPATH') or die('No direct script access.');
 
class Controller_Mongosql extends Controller {

	public function action_index()
	{
		define('NL','<br/>');
		$sql = "SELECT * from mytable where id = 10 ORDER BY id DESC";
		$sql = "SELECT a,b from mytable where id = 10 ORDER BY id DESC";
		$mongoSelect = self::convertSelect($sql);
		$sqlInsert = "INSERT INTO mytable (id,a,b) VALUES (10,".rand(1,5).",".rand(10,100).")";
		$mongoInsert = self::convertInsert($sqlInsert);	
		$this->response->body($mongoSelect.'<hr/>'.$mongoInsert);
	}
	public static function processKeywords($sql,&$keywords)
	{
		$lastpos = 0;
		$regex = '';
		foreach($keywords as $keyword => &$info) {
			$c = strpos($sql,$keyword,$lastpos);
			if($c!==false) {
				//echo $keyword.'='.$c.NL;
				$endpos = $c + strlen($keyword);
				$lastpos = $endpos;
				$info['pos'] = $endpos;
				$kw = trim($keyword);
				$kw = str_replace('(','\(',$kw);
				$regex .= "{$kw}(.*)"; 
			} else {
				unset($keywords[$keyword]);
			}
		}		
		$regex = '/'.$regex.'/';
		$matches = array();
		preg_match_all($regex,$sql,$matches);
		$pointer = 1;
		foreach($keywords as $keyword => &$info) {
			if(isset($info['pos'])) {
				$info['val'] = $matches[$pointer][0];
				$pointer++;
			}
		}
	}
	public static function convertInsert($sql) 
	{
		$sql = strtolower($sql);
		$sql = str_replace('insert into','insert',$sql);
		$keywords = array('insert'=>array(),
			'('=>array(),
			'values'=>array()
		);
		self::processKeywords($sql,$keywords);
		echo '<pre>'.print_r($keywords,true).'</pre>';
		$collection = trim($keywords['insert']['val']);
		$colStr = trim(str_replace(array('(',')'),'',$keywords['(']['val']));
		$cols = explode(',',$colStr);
		$valueStr = trim(str_replace(array('(',')'),'',$keywords['values']['val']));
		$values = explode(',',$valueStr);
		$assoc = array();
		foreach($cols as $key => $col) {
			$assoc[] = "$col:{$values[$key]}";
		}
		$assocStr = implode(',',$assoc);
		$mongo = "db.$collection.insert({ $assocStr })";
		return $mongo;
	}
	public static function convertSelect($sql) 
	{
		$sql = strtolower($sql);
		$outStr = '';
		$keywords = array('select'=>array(),
			'from'=>array(),
			'where'=>array(),
			'order by'=>array()
		);
		self::processKeywords($sql,$keywords);
		$regex = '';
		foreach($keywords as $keyword => &$info) {
			if(isset($info['pos'])) {
				$kw = trim($keyword);
				$regex .= "{$kw}(.*)"; 
			}
		}
		$regex = '/'.$regex.'/';
		$matches = array();
		preg_match_all($regex,$sql,$matches);
		$pointer = 1;
		foreach($keywords as $keyword => &$info) {
			if(isset($info['pos'])) {
				$info['val'] = $matches[$pointer][0];
				$pointer++;
			}
		}
		$outStr .= $sql.NL;
		//echo '<pre>'.print_r($keywords,true).'</pre>';
		$findStr = '';
		$colStr = '';
		$collection = trim($keywords['from']['val']);
		$cols = trim($keywords['select']['val']);
		
		if($cols=='*') {
			$colStr = '';
		} else {
			$colList = explode(',',$cols);
			foreach($colList as $key => &$col) {
				$col = trim($col);
				if(!empty($col)) {
					$col = "{$col}:1";
				} else {
					unset($colList[$key]);
				}
			}
			$colStr = '{' . implode(',',$colList) . '}';
		}
		if(isset($keywords['where']['val']) && !empty($keywords['where']['val'])) {
			$where = trim($keywords['where']['val']);
			if(strpos($where,'=')!==false) {
				$equalParts = explode('=',$where);
				if(is_numeric($equalParts[0])) {
					$var = trim($equalParts[1]);
					$val = trim($equalParts[0]);
				} else {
					$var = trim($equalParts[0]);
					$val = trim($equalParts[1]);					
				}
				$findStr = "$var:$val";
			}
		}
		if(!empty($findStr) && empty($colStr)) {
			$mongo = "db.{$collection}.find( { $findStr } )";
		} elseif(!empty($colStr)) {
			$mongo = "db.{$collection}.find({ $findStr },$colStr)";
		} else {
			$mongo = "db.{$collection}.find()";
		}
		if(isset($keywords['order by']['val']) && !empty($keywords['order by']['val'])) {
			$orderParts = explode(' ',trim($keywords['order by']['val']));
			$orderCol = $orderParts[0];
			//print_r($orderParts);
			if(isset($orderParts[1]) && strpos($orderParts[1],'desc')!==false) {
				$orderType = -1;
			} else {
				$orderType = 1;
			}
			$mongo .= ".sort({ $orderCol : $rderType })";
		}
		return $mongo;
	}

}

?>
