<?php
ini_set('auto_detect_line_endings', true);

require_once("Icommerce/Utils/ImportUtils.php");

// Trim each element in the array..
function TrimAll($cols, $thesize, $enc_from, $enc_to) {
	if (!$cols) {
		return $cols;
	}
	foreach ($cols as $key => $val) {
		if ($enc_from && $enc_to) {
			$cols[$key] = iconv($enc_from, $enc_to, trim($val));
		} else {
			$cols[$key] = trim($val);
		}
	}
	for ($i = sizeof($cols); $i < $thesize; $i++) {
		$cols[$i] = "";
	}
	return $cols;
}

class Icommerce_CsvReader implements Iterator {
	protected $_file;
	protected $_fp;
	protected $_cols;
	protected $_line_no = -1;
	protected $_line;
	protected $_has_file_headers = true;
	protected $_delim, $_enc;
	protected $_enc_from, $_enc_to;
	protected $_raw_mode; // Iterate with integer only column indeces
	function __construct($file, $columns = null, $delim = ",", $enc = '"', $encfrom = null, $encto = null, $use_explode = null, $raw_mode = false) {
		$this->_file     = $file;
		$this->_delim    = $delim;
		$this->_enc      = $enc;
		$this->_enc_from = $encfrom;
		$this->_enc_to   = $encto;
		$this->_raw_mode = $raw_mode;
		$this->_fp       = fopen($file, "r");
		if ($this->_fp) {
			if ($columns === null) {
				if ($this->_fp) {
					//$line = fgetcsv( $this->_fp, 0, $this->_delim, $this->_enc );
					$line        = csvSplit(fgets($this->_fp), $this->_delim, $this->_enc);
					$this->_cols = TrimAll($line, 0, $this->_enc_from, $this->_enc_to);
					$this->_line_no++;
				}
			} else {
				$this->_cols             = $columns;
				$this->_has_file_headers = false;
			}
			// Read first element
			$this->next();
		}
	}

	function __destruct() {
		if ($this->_fp) {
			fclose($this->_fp);
		}
	}

	function setUseExplode($do_use = true) {
		// Not used anymore
		return $this;
	}

	function setRawMode($raw) {
		$this->_raw_mode = $raw;
	}

	function lacksColumns($cols) {
		$no_exist = array();
		foreach ($cols as $col) {
			$ix = array_search($col, $this->_cols);
			//if( !array_key_exists($col,$this->_cols) )
			if ($ix === FALSE) {
				$no_exist[] = $col;
			}
		}
		return count($no_exist) ? $no_exist : null;
	}

	function hasColumn($col) {
		$ix = array_search($col, $this->_cols);
		return $ix !== null && $ix !== false;
	}

	function getColumns() {
		return $this->_cols;
	}

	function current() {
		if (!$this->_raw_mode) {
			// Make a hash for the known columns
			$a = array();
			foreach ($this->_cols as $ix => $e) {
				$a[$e] = $this->_line[$ix];
			}
			return $a;
		} else {
			return $this->_line;
		}
	}

	function key() {
		return $this->_line_no;
	}

	function next() {
		while (!feof($this->_fp)) {
            $s = fgets($this->_fp);
            if ($s) {
                $line = csvSplit($s, $this->_delim, $this->_enc);
            } else {
                $line = array();
            }
			$this->_line_no++;
			if (count($line) !== 1 || trim($line[0]) !== "") {
				break;
			}
		}
		// Detect empty lines
		/*if( !$this->_use_explode ){
			   do {
				   $line = fgetcsv( $this->_fp, 0, $this->_delim, $this->_enc );
				   $this->_line_no++;
			   } while( count($line)==1 && trim($line[0])=="" && !feof($this->_fp) );
		   }
		   else{
			   // # NOTE: this does not work with separators inside enclosed strings
			   do {
				   $s = trim( fgets( $this->_fp ) );
				   $line = explode( $this->_delim, $s );
				   $this->_line_no++;
				   $ts = trim($line[0]);
			   } while( count($line)==1 && trim($line[0])=="" && !feof($this->_fp) );
			   // Trim off enclosing quotes
			   $encenc = $this->_enc . $this->_enc;
			   foreach( $line as $ix => $v ){
				   while( ($l = strlen($v))>1 && $v[0]==$this->_enc && $v[0]==$v[$l-1] ){
					   $v = substr($v,1,-1);
				   }
				   while( substr($v,0,2)==$encenc ){
					   $v = substr($v,2);
				   }
				   while( substr($v,-2)==$encenc ){
					   $v = substr($v,0,-2);
				   }
				   $line[$ix] = $v;
			   }
		   }*/
		$this->_line = TrimAll(isset($line) ? $line : null, sizeof($this->_cols), $this->_enc_from, $this->_enc_to);
	}

	function rewind() {
		if ($this->_fp) {
			fclose($this->_fp);
			$this->_fp      = fopen($this->_file, "r");
			$this->_line_no = -1;
			if ($this->_has_file_headers) {
				$this->_line_no++;
				// Discard column headers
				fgetcsv($this->_fp);
			}
			// Read first element
			$this->next();
		}
	}

	function valid() {
		return /*$this->_fp && !feof($this->_fp) &&*/ $this->_line !== false && $this->_line != null;
	}
}
