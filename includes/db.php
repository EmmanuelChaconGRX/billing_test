<?php
include_once(__DIR__.'/../../env.php');
if(!isset($DB_FUNCTIONS) || $DB_FUNCTIONS=="")
{
	$DB_FUNCTIONS="LOADED";

	//connect to db
	$my_host = _MYSQL_HOST;
	$my_user = _MYSQL_USER;
	$my_pass = _MYSQL_PASS;
	$my_name = _MYSQL_DB;

	$link = mysql_connect("$my_host","$my_user","$my_pass") or die("Could not connectx");
	mysql_select_db($my_name);	

#	MS SQL
	$ms_db_host = MSSQL_HOST;
#	$ms_db_host = "datatrans-dr";
	$ms_db_user = MSSQL_USER;
	$ms_db_pass = MSSQL_PASS;
	$ms_db_name = MSSQL_DB_A;

#	$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die ("Unable to connect!");
#	mssql_select_db($ms_db_name);

	function mysql_value($sql)
	{
		$result = mysql_query($sql) or die(mysql_error() . "\n\n<BR>\n$sql");
		if (mysql_num_rows($result)>0)
			if($row=mysql_fetch_row($result))
				return $row[0];

		return false;		
	}

	function mysql_row($sql)
	{
		$result = mysql_query($sql) or die(mysql_error() . "\n\n<BR>\n$sql");
		if (mysql_num_rows($result)>0)
			if($row=mysql_fetch_array($result))
				return $row;

		return false;		
	}
	
	function mysql_rows($sql)
	{
		$result = mysql_query($sql) or die(mysql_error() . "\n\n<BR>\n$sql");
		if (mysql_num_rows($result)>0)
			while($row=mysql_fetch_array($result))
				$rows[]=$row;
		
		return $rows;		
	}
	
	function mysql_value_concat($sql,$glue="|")
	{
		$result = mysql_query($sql) or die(mysql_error() . "\n\n<BR>\n$sql");
		if (mysql_num_rows($result)>0)
		{
			while($row=mysql_fetch_array($result))
				$concat_arr[] = $row[0];
			return implode($glue,$concat_arr);
		}
	}

#	mysql_save_row("form_837_claim",$pk,$data);
	function mysql_save_row($table,$PK,$data_arr)
	{
		global $DEBUGGING;
		$pk_field = mysql_get_pk_name($table) or die("invalid pk for table $table");
#		print "$table :: $pk_field :: $PK<BR>\n";
		foreach($data_arr as $field)
			if(!$PK){
				$pos = strpos($field,"=");
				$fields[] = substr($field,0,$pos);
				$values[] = substr($field,$pos+1);
				//list($fields[],$values[])=split("=",$field);
			}
			else
				$fields[]=$field;
		
		if(!$PK)
		{
			$fields = join($fields,",");
			$values = join($values,",");
			$query = "INSERT INTO $table ($fields) VALUES($values)";
		}else
		{
			$fields = join($fields,",");
			$query  = "UPDATE $table SET $fields WHERE $pk_field=$PK";
		}
		if($DEBUGGING) print "<B>debug:::mysql_query:</B>$query<BR>\n";
		mysql_query($query) or die("<B>ERROR Xing:</B><BR>$query<BR><B>".mysql_error());
	}
	
	function mysql_get_pk_name($table)
	{
		if($table=="webedi_tp" || $table=="webedi_lp")
                        return "userid";

		$tmp = mysql_query("SHOW COLUMNS FROM $table") or die("Invalid table $table");
		while($col = mysql_fetch_array($tmp))
			if(substr($col["Field"],0,3)=="PK_")
				return $col["Field"];
		return false;
	}

#	function ob_clean()
#	{
#		ob_end_clean();
#	}
	
#	function floatval($x)
#	{
#		return doubleval($x);
#	}
        
        
}
