<?php
  /**
   * @author Jan
   * @link https://github.com/Oxycoin/oxycoin-checker
   * @license https://github.com/Oxycoin/oxycoin-checker/blob/master/LICENSE
   */

echo "[ FORKING ]\n";
echo "\t\t\tGoing to check for forked status now...\n";

// Set the database to save our counts to
    $db = new SQLite3($database) or die("[ FORKING ] Unable to open database");
 
// Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS $table (
                    id INTEGER PRIMARY KEY,  
                    counter INTEGER,
                    time INTEGER)");

// Let's check if any rows exists in our table
    $check_exists = $db->query("SELECT count(*) AS count FROM $table");
    $row_exists   = $check_exists->fetchArray();
    $numExists    = $row_exists['count'];

    // If no rows exist in our table, add one
    	if($numExists < 1){
        	
        	// Echo something to our log file
        	echo "\t\t\tNo rows exist in our table to update the counter...Adding a row for you.\n";
        	
        	$insert = "INSERT INTO $table (counter, time) VALUES ('0', time())";
        	$db->exec($insert) or die("[ FORKING ] Failed to add row!");
      	
      	}

// Tail oxycoin.log
	$last = tailCustom($oxycoinlog, $linestoread);

// Count how many times the fork message appears in the tail
	$count = substr_count($last, $msg);

// Get counter value from our database
    $check_count 	  = $db->query("SELECT * FROM $table LIMIT 1");
    $row          	= $check_count->fetchArray();
    $counter      	= $row['counter'];

// If counter + current count is greater than $max_count, take action...
    if (($counter + $count) >= $max_count) {

        // If oxy-snapshot directory exists..
        if(file_exists($snapshotDir)){
          echo "\t\t\tHit max_count. I am going to restore from a snapshot.\n";
          if($telegramEnable === true){
            $Tmsg = "Hit max_count on ".gethostname().". I am going to restore from a snapshot.";
            passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
          }

          // Perform snapshot restore
          passthru("cd $pathtoapp && forever stop app.js");
          passthru("cd $snapshotDir && echo y | ./oxy-snapshot.sh restore");
          passthru("cd $pathtoapp && forever start app.js");

          // Reset counters
          echo "\t\t\tFinally, I will reset the counter for you...\n";
          $query = "UPDATE $table SET counter='0', time=time()";
          $db->exec($query) or die("[ FORKING ] Unable to set counter to 0!");
        }else{
          echo "\t\t\tWe hit max_count and want to restore from snapshot.\n
                \t\t\tHowever, path to snapshot directory ($snapshotDir) does not seem to exist.\n
                \t\t\tDid you install oxy-snapshot?\n";
        }

// If counter + current count is not greater than $max_count, add current count to our database...
    } else {

	    $query = "UPDATE $table SET counter=counter+$count, time=time()";
    	$db->exec($query) or die("[ FORKING ] Unable to plus the counter!");

    	echo "\t\t\tCounter ($counter) + current count ($count) is not sufficient to restore from snapshot. Need: $max_count \n";

    	// Check snapshot setting
    	if($createsnapshot === false){
    		echo "\t\t\tSnapshot setting is disabled.\n";
    	}

    	// If counter + current count are smaller than $max_count AND option $createsnapshot is true, create a new snapshot
    	if(($counter + $count) < $max_count && $createsnapshot === true){
    		
    		echo "\t\t\tIt's safe to create a daily snapshot and the setting is enabled.\n";
    		echo "\t\t\tLet's check if a snapshot was already created today...\n";
    		
    		// Check if path to oxy-snapshot exists..
        if(file_exists($snapshotDir)){
          
          $snapshots = glob($snapshotDir.'snapshot/oxycoin_db_test'.date("d-m-Y").'*.snapshot.tar');
          if (!empty($snapshots)) {
        
            echo "\t\t\tA snapshot for today already exists:\n";
              echo "\t\t\t".$snapshots[0]."\n";
            
            echo "\t\t\tGoing to remove snapshots older than $max_snapshots days...\n";
              $files = glob($snapshotDir.'snapshot/oxycoin_db*.snapshot.tar');
              foreach($files as $file){
                if(is_file($file)){
                    if(time() - filemtime($file) >= 60 * 60 * 24 * $max_snapshots){
                      if(unlink($file)){
                        echo "\t\t\tDeleted snapshot $file\n";
                      }
                    }
                }
              }

            echo "\t\t\tDone!\n";
        
          }else{

            echo "\t\t\tNo snapshot exists for today, I will create one for you now!\n";
              
            ob_start();
            $create = passthru("cd $snapshotDir && ./oxy-snapshot.sh create");
            $check_createoutput = ob_get_contents();
            ob_end_clean();

            // If buffer contains "OK snapshot created successfully"
            if(strpos($check_createoutput, 'OK snapshot created successfully') !== false){
            
                echo "\t\t\tDone!\n";
              
              if($telegramEnable === true){
                  $Tmsg = "Created daily snapshot on ".gethostname().".";
                  passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
              }

            }

          }
        }else{
          // Path to oxy-snapshot does not exist..
          echo "\t\t\tYou have oxy-snapshot enabled, but the path to oxy-snapshot does not seem to exist.\n
                \t\t\tDid you install oxy-snapshot?\n";
        }

    	}

    }
