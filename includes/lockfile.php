<?php
	/**
   * @author Jan
   * @link https://github.com/Oxycoin/oxycoin-checker
   * @license https://github.com/Oxycoin/oxycoin-checker/blob/master/LICENSE
   */

// Check if lock file exists
if (file_exists($lockfile)) {

	// Check age of lock file and touch it if older than 10 minutes
	if((time()-filectime($lockfile)) >= 600){
	
		echo $date." - [ LOCKFILE ] Lock file is older than 10 minutes. Going to touch it and continue..\n";
		
		if (!touch($lockfile)){
		  exit("[ LOCKFILE ] Error touching $lockfile\n");
		}

	// If file is younger than 10 minutes, exit!
	}else{
		exit("[ LOCKFILE ] A previous job is still running...\n");
	}

}else{
  // Lock file does not exist, let's touch it
  if (!touch($lockfile)){
    exit("[ LOCKFILE ] Error touching $lockfile\n");
  }
}