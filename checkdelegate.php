<?php
  /**
   * @author Jan
   * @link https://github.com/Oxycoin/oxycoin-checker
   * @license https://github.com/Oxycoin/oxycoin-checker/blob/master/LICENSE
   */

/* PREREQUISITES
____________________ */
  require(dirname(__FILE__).'/config.php');
  require(dirname(__FILE__).'/includes/functions.php');

  echo "___________________________________________________\n";
  echo $date."\n";

/* LOCK FILE
____________________ */
  include dirname(__FILE__).'/includes/lockfile.php';

/* CHECK STATUS
____________________ */
  include dirname(__FILE__).'/includes/checkstatus.php';

/* CHECK IF FORKED
____________________ */
  include dirname(__FILE__).'/includes/checkforked.php';

/* CHECK CONSENSUS
____________________ */
  include dirname(__FILE__).'/includes/consensus.php';

/* CLEANUP LOG FILES
____________________ */
  include dirname(__FILE__).'/includes/cleanup.php';