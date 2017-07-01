<?php
	/**
   * @author Jan
   * @link https://github.com/Oxycoin/oxycoin-checker
   * @license https://github.com/Oxycoin/oxycoin-checker/blob/master/LICENSE
   */

// PING function..
function ping($host,$port=80,$timeout=3) {
    $fsock = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!is_resource($fsock)) {
        return FALSE;
    } else {
        return TRUE;
    }
}
	
// Tail function
function tailCustom($filepath, $lines = 1, $adaptive = true) {

	// Current date
	$date = date("Y-m-d H:i:s");

	// Open file
	$f = @fopen($filepath, "rb");
	//if ($f === false) return false;
	if ($f === false) return "\t\t\tUnable to open file!\n";

	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

	// Jump to last character
	fseek($f, -1, SEEK_END);

	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';

	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {

		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);

		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);

		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;

		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");

	}

	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {

		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);

	}

	// Close file and return
	fclose($f);
	return trim($output);

}

// Log rotation function
function rotateLog($logfile, $max_logfiles=3, $logsize=10485760){

	// Current date
	$date = date("Y-m-d H:i:s");
	
	if(file_exists($logfile)){
		
		// Check if log file is bigger than $logsize
		if(filesize($logfile) >= $logsize){
			echo $date." - [ LOGFILES ] Log file exceeds size: $logsize. Let me rotate that for you...\n";
			$rotate = passthru("gzip -c $logfile > $logfile.".time().".gz && rm $logfile");
			if($rotate){
				echo "\t\t\tLog file rotated.\n";
			}
		}else{
			echo "\t\t\tLog size has not reached the limit yet. (".filesize($logfile)."/$logsize)\n";
		}

		// Clean up old log files
		echo "\t\t\tCleaning up old log files...\n";
			$logfiles = glob($logfile."*");
		  	foreach($logfiles as $file){
		    	if(is_file($file)){
		      		if(time() - filemtime($file) >= 60 * 60 * 24 * $max_logfiles){
		        		if(unlink($file)){
		        			echo "\t\t\tDeleted log file $file\n";
		        		}
		      		}
		    	}
		  	}

	}else{
		echo "\t\t\tCannot find a log file to rotate..\n";
	}
}

// Check publicKey
function checkPublic($server, $secret){
	ob_start();
	$check_public = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/accounts/open");
	$check_public = ob_get_contents();
	ob_end_clean();	

	// If status is not OK...
	if(strpos($check_public, "success") === false){
		return "error";
	}else{
		$check = json_decode($check_public, true);
		return $check['account']['publicKey'];
	}
}

// Check forging
function checkForging($server, $publicKey){
	ob_start();
	$check_forging = passthru("curl -s --connect-timeout 10 -XGET $server/api/delegates/forging/status?publicKey=$publicKey");
	$check_forging = ob_get_contents();
	ob_end_clean();	

	// If status is not OK...
	if(strpos($check_forging, "success") === false){
		return "error";
	}else{
		$check = json_decode($check_forging, true);
		if($check['enabled']){
			return "true";
		}else{
			return "false";
		}
	}
}

// Disable forging
function disableForging($server, $secret){
	ob_start();
	$check_status = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/delegates/forging/disable");
	$check_output = ob_get_contents();
	ob_end_clean();	

	// If status is not OK...
	if(strpos($check_output, "success") === false){
		return "error";
	}else{
		return "disabled";
	}
}

// Enable forging
function enableForging($server, $secret){
	ob_start();
	$check_status = passthru("curl -s --connect-timeout 10 -d 'secret=$secret' $server/api/delegates/forging/enable > /dev/null");
	$check_output = ob_get_contents();
	ob_end_clean();	

	// If status is not OK...
	if(strpos($check_output, "success") === false){
		return "error";
	}else{
		return "enabled";
	}
}
