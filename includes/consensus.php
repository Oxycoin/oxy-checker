<?php
  /**
   * @author Jan
   * @link https://github.com/Oxycoin/oxycoin-checker
   * @license https://github.com/Oxycoin/oxycoin-checker/blob/master/LICENSE
   */

echo "[ CONSENSUS ]\n";

  echo "\t\t\tChecking if you enabled the consensus check...";
  if($consensusEnable === true && !empty($secret)){
    echo "yes!\n";

    // Get publicKey of the first secret to use in forging checks
    $public = checkPublic($apiHost, $secret[0]);

    // Check if we are the master node
    if($master === false){
      // If we land here, we are the slave
      echo "\t\t\tWe are a slave\n";
      
      // Check if the master is online
      echo "\t\t\tChecking if master is online...";
      
      $find = array("http://","https://");
      $up = ping(str_replace($find,"",$masternode), $masterport);
      if($up){
        // Master is online. Do nothing..
        echo "yes!\n";

        // Check if we are forging
        echo "\t\t\tChecking if we (slave) are forging...";
        $forging = checkForging($slavenode.":".$slaveport, $public);
        
        // If we are forging..
        if($forging == "true"){
          echo "yes!\n";
        }else{
          echo "no!\n";
        }

      }else{
        // Master is offline. Let's check if we are forging, if not; enable it. 
        echo "no!\n";

        echo "\t\t\tLet's check if we (slave) are forging...";
        $forging = checkForging($slavenode.":".$slaveport, $public);
        
        // If we are forging..
        if($forging == "true"){
          echo "yes!\n";
          
          // If Telegram is enabled, send a message that the master seems offline
          if($telegramEnable === true){
              $Tmsg = gethostname().": Master node seems offline. Slave is forging though..";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
          }

          echo "\t\t\tChecking our consensus..\n";

          // Perform a consensus check..
          // Check consensus on slave node
          $consensusSlave = @file_get_contents($slavenode.":".$slaveport."/api/loader/status/sync");
          if($consensusSlave === FALSE){
            $consensusSlave = 0;
          }else{
            $consensusSlave = json_decode($consensusSlave, true);
            $consensusSlave = $consensusSlave['consensus'];
          }
          echo "\t\t\tConsensus slave: $consensusSlave %\n";
          
          // If consensus on the slave is below threshold as well, send a telegram message and restart node!
          if($consensusSlave <= $threshold){
            echo "\t\t\tThreshold on slave node reached! Telegram: No healthy server online. Going to restart the node for you..\n";
            
            // Send Telegram
            if($telegramEnable === true){
              $Tmsg = gethostname().": No healthy server online. Going to restart Oxy-node for you..";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
            }

            // Restart node
            echo "\t\t\tStopping all forever processes...\n";
              passthru("forever stopall");
            echo "\t\t\tStarting forever proces...\n";
              passthru("cd $pathtoapp && forever start app.js");
              
          }else{
            // All is fine. Do nothing..
            echo "\t\t\tConsensus is fine!\n";
          }
        }else{
          // Enable forging for each secret on the slave
          echo "\t\t\tWe are not forging! Let's enable it..\n";
          
          // If Telegram is enabled, send a message that the master seems offline
          if($telegramEnable === true){
              $Tmsg = gethostname().": Master node seems offline. Slave will enable forging now..";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
          }

          foreach($secret as $sec){
            echo "\t\t\tEnabling forging on slave for secret: $sec\n";
            enableForging($slavenode.":".$slaveport, $sec);
          }
        }
      }
    }else{
      // If we land here, we are the master
      echo "\t\t\tWe are the master\n";
        
      // Check if we are forging
      $forging = checkForging($masternode.":".$masterport, $public);

      // If we are forging..
      if($forging == "true"){
        echo "\t\t\tMaster node is forging.\n";

        // Forging on the slave should be/stay disabled for every secret until we perform a consensus check.
        // This way we ensure that forging is only disabled on nodes the master chooses.
        foreach($secret as $sec){
          echo "\t\t\tDisabling forging on slave for secret: $sec\n";
          disableForging($slavenode.":".$slaveport, $sec);
        }

        // Check consensus on master node
        $consensusMaster = @file_get_contents($masternode.":".$masterport."/api/loader/status/sync");
        if($consensusMaster === FALSE){
          $consensusMaster = 0;
        }else{
          $consensusMaster = json_decode($consensusMaster, true);
          $consensusMaster = $consensusMaster['consensus'];
        }
        echo "\t\t\tConsensus master: $consensusMaster %\n";
        
        // If consensus is the same as or lower than the set threshold..
        if($consensusMaster <= $threshold){
          echo "\t\t\tThreshold on master node reached! Going to check the slave node..\n";

          // If Telegram is enabled, send a message that the master seems offline and we are going to take over forging (is possible)
          if($telegramEnable === true){
              $Tmsg = gethostname().": Threshold reached on master node. Going to enable forging on the slave.";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
          }
        
          // Check consensus on slave node
          $consensusSlave = @file_get_contents($slavenode.":".$slaveport."/api/loader/status/sync");
          if($consensusSlave === FALSE){
            $consensusSlave = 0;
          }else{
            $consensusSlave = json_decode($consensusSlave, true);
            $consensusSlave = $consensusSlave['consensus'];
          }
          echo "\t\t\tConsensus slave: $consensusSlave %\n";
          
          // If consensus on the slave is below threshold as well, send a telegram message and restart node!
          if($consensusSlave <= $threshold){
            echo "\t\t\tThreshold on slave node reached! Telegram: No healthy server online. Going to restart the node for you..\n";
            
            // Send Telegram
            if($telegramEnable === true){
              $Tmsg = gethostname().": No healthy server online. Going to restart Oxy-node for you..";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
            }

            // Restart node
            echo "\t\t\tStopping all forever processes...\n";
              passthru("forever stopall");
            echo "\t\t\tStarting forever proces...\n";
              passthru("cd $pathtoapp && forever start app.js");
          }else{
            echo "\t\t\tConsensus on slave is sufficient enough to switch to..\n";
            
            echo "\t\t\tEnabling forging on slave..\n";
            foreach($secret as $sec){
              echo "\t\t\tEnabling forging on slave for secret: $sec\n";
              enableForging($slavenode.":".$slaveport, $sec);
            }

            echo "\t\t\tDisabling forging on master..\n";
            foreach($secret as $sec){
              echo "\t\t\tDisabling forging on master for secret: $sec\n";
              disableForging($masternode.":".$masterport, $sec);
            }
          }
        }else{
          // Master consensus is high enough to continue forging
          echo "\t\t\tThreshold on master node not reached. Everything is okay.\n";
        }

      // If we are not forging..
      }else{
        echo "\t\t\tMaster node is not forging. Checking if slave is forging..\n";

        // Check if the slave is forging
        $forging = checkForging($slavenode.":".$slaveport, $public);
        // If slave is forging..
        if($forging == "true"){
          echo "\t\t\tSlave is forging. Going to check it's consensus..\n";

          // Check consensus on slave node
          $consensusSlave = @file_get_contents($slavenode.":".$slaveport."/api/loader/status/sync");
          if($consensusSlave === FALSE){
            $consensusSlave = 0;
          }else{
            $consensusSlave = json_decode($consensusSlave, true);
            $consensusSlave = $consensusSlave['consensus'];
          }
          echo "\t\t\tConsensus slave: $consensusSlave %\n";

          // If consensus is the same as or lower than the set threshold..
          if($consensusSlave <= $threshold){
            echo "\t\t\tConsensus slave reached the threshold. Checking consensus master node..\n";

            // Check consensus on master node
            $consensusMaster = @file_get_contents($masternode.":".$masterport."/api/loader/status/sync");
            if($consensusMaster === FALSE){
              $consensusMaster = 0;
            }else{
              $consensusMaster = json_decode($consensusMaster, true);
              $consensusMaster = $consensusMaster['consensus'];
            }
            echo "\t\t\tConsensus master: $consensusMaster %\n";
            
            // If consensus is the same as or lower than the set threshold..
            if($consensusMaster <= $threshold){
              echo "\t\t\tThreshold on master node reached as well! Restarting node..\n";

              // Restart node
              echo "\t\t\tStopping all forever processes...\n";
                passthru("forever stopall");
              echo "\t\t\tStarting forever proces...\n";
                passthru("cd $pathtoapp && forever start app.js");

            }else{
              // Consensus is sufficient on master. Enabling forging to master
              echo "\t\t\tConsensus on master is sufficient. Enabling forging on master..\n";

              // Enable forging on master
              echo "\t\t\tEnabling forging on master..\n";
              foreach($secret as $sec){
                echo "\t\t\tEnabling forging on master for secret: $sec\n";
                enableForging($masternode.":".$masterport, $sec);
              }

              // Disable forging on slave
              echo "\t\t\tDisabling forging on slave..\n";
              foreach($secret as $sec){
                echo "\t\t\tDisabling forging on slave for secret: $sec\n";
                disableForging($slavenode.":".$slaveport, $sec);
              }

            }

          }else{
            // Consensus slave is sufficient. Doing nothing..
            echo "\t\t\tConsensus on slave is sufficient. Doing nothing..\n";
          }
        }else{
          // Slave is also not forging! Compare consensus on both nodes and enable forging on node with highest consensus..
          echo "\t\t\tSlave is not forging as well! Going to compare consensus and enable forging on best node..\n";

          // If Telegram is enabled, send a message that master and slave are both not forging and we're going to enable it on the best node
          if($telegramEnable === true){
              $Tmsg = gethostname().": Master and Slave are both not forging! Going to enable forging on the best node.";
              passthru("curl -s -d 'chat_id=$telegramId&text=$Tmsg' $telegramSendMessage >/dev/null");
          }

          // Check consensus on master node
          $consensusMaster = @file_get_contents($masternode.":".$masterport."/api/loader/status/sync");
          if($consensusMaster === FALSE){
            $consensusMaster = 0;
          }else{
            $consensusMaster = json_decode($consensusMaster, true);
            $consensusMaster = $consensusMaster['consensus'];
          }
          echo "\t\t\tConsensus master: $consensusMaster %\n";

          // Check consensus on slave node
          $consensusSlave = @file_get_contents($slavenode.":".$slaveport."/api/loader/status/sync");
          if($consensusSlave === FALSE){
            $consensusSlave = 0;
          }else{
            $consensusSlave = json_decode($consensusSlave, true);
            $consensusSlave = $consensusSlave['consensus'];
          }
          echo "\t\t\tConsensus slave: $consensusSlave %\n";

          // COMPARE CONSENSUS
          if($consensusMaster > $consensusSlave){
            // Enable forging on master
            foreach($secret as $sec){
              echo "\t\t\tEnabling forging on master for secret: $sec\n";
              enableForging($masternode.":".$masterport, $sec);
            }
          }else{
            // Enabling forging on slave
            foreach($secret as $sec){
              echo "\t\t\tEnabling forging on slave for secret: $sec\n";
              enableForging($slavenode.":".$slaveport, $sec);
            }
          } // END: COMPARE CONSENSUS

        } // END: SLAVE FORGING IS FALSE

      } // END: MASTER FORGING IS FALSE
    
    } // END: WE ARE THE MASTER

  }else{
    echo "no! (or no secret)\n";
  } // END: ENABLED CONSENSUS CHECK?