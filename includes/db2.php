<?PHP

include_once(__DIR__ . '/../../env.php');
$_mysql = new MySQL(_MYSQL_HOST, _MYSQL_USER, _MYSQL_PASS, _MYSQL_DB);

class MySQL {

    private $link;

    function __construct($_MYSQL_HOST, $_MYSQL_USER, $_MYSQL_PASS, $_MYSQL_DB) {
        $this->_MYSQL_HOST = $_MYSQL_HOST;
        $this->_MYSQL_USER = $_MYSQL_USER;
        $this->_MYSQL_PASS = $_MYSQL_PASS;
        $this->_MYSQL_DB = $_MYSQL_DB;
        $this->open();
    }

    function open() {
        if (!isset($DB_CONNECTED_12030)) {
            $this->link = mysql_connect($this->_MYSQL_HOST, $this->_MYSQL_USER, $this->_MYSQL_PASS);
            mysql_select_db($this->_MYSQL_DB);
            $DB_CONNETED_12030 = true;
        }
    }

    function get_row($query) {
        $this->open();
        $this->last_query = $query;
        $result = mysql_query($query) or die($query . "<BR>" . mysql_error());
        if ($row = mysql_fetch_assoc($result))
            return $row;
        else
            return false;
    }

    function get_rows($query) {
        $this->open();

        $result = mysql_query($query) or die(mysql_error());

        while ($this_row = mysql_fetch_assoc($result))
            $row[] = $this_row;
        return $row;
    }

    function insert_from_array($table, $array) {
        $this->open();
        $arr_fields = $this->get_rows("SHOW FIELDS FROM $table");
        foreach ($arr_fields as $fields)
            $field_names[$fields["Field"]] = "SET";

        foreach ($array as $fn => $fv)
            if ($fv != "" && $fn != "" && $field_names["$fn"] == "SET") {
                $fv = mysql_escape_string($fv);
                $cnames[] = "$fn";
                $cvalues[] = "'$fv'";
            }
        $cnames = join($cnames, ",");
        $cvalues = join($cvalues, ",");
        $_query = "INSERT INTO $table ($cnames) VALUES ($cvalues)";

        $id = $this->insert_get_id($_query);
        #print "$_query ::: $id <BR>\n";
        return $id;
    }

    function update_from_array($table, $arrayw, $arrayv) {
        $this->open();
        $arr_fields = $this->get_rows("SHOW FIELDS FROM $table");
        foreach ($arr_fields as $fields)
            $field_names[$fields["Field"]] = "SET";

        foreach ($arrayw as $fn => $fv)
            if ($fv != "" && $fn != "" && $field_names["$fn"] == "SET") {
                $fv = mysql_escape_string($fv);
                $w[] = "$fn='$fv'";
            }
        foreach ($arrayv as $fn => $fv)
            if ($fv != "" && $fn != "" && $field_names["$fn"] == "SET") {
                $v[] = "$fn='$fv'";
            }
        $where = join($w, " AND ");
        $values = join($v, ",");

        $_query = "UPDATE $table SET $values WHERE $where";
        #print "$_query<BR>\n";
        $this->insert_get_id($_query);
        return $id;
    }

    function query($query) {
        $this->error = "";
        $this->last_query = $query;
        $result = mysql_query("$query");
        if (mysql_errno() != 0) {
            $this->error = mysql_error();
            return false;
        }
        return $result;
    }

    function clear() {
        $this->error = 0;
    }

    function insert_get_id($query) {
        $this->query("$query");
        if (!$this->error) {
            //return $this->get_value("SELECT LAST_INSERT_ID();");
            return mysql_insert_id($this->link);
        } else
            die("ERROR executing $query<BR>" . $this->error . ":" . mysql_error());

        $mysql->error = mysql_error();
        print "$_mysql->last_query<BR>\n";

        return false;
    }

    function get_value($sql, $col = 0) {
        $this_row = $this->get_row("$sql");
        foreach ($this_row as $field)
            return $field;
    }

}

