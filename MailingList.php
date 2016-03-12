<?php

// https://pear.php.net/package/HTTP_Request2
require_once 'HTTP/Request2.php';

// mailing list general interface
// TODO: add function exit codes as interface constants 
interface MailingList
{
  // read lists into class instance
  public function get_mailinglists();
  
  // get list of subscribers on mailing list $list
  public function get_subscribers($list);

  // add subscriber $user to mailing list $list
  public function add_subscriber($list, $user);

  // delete subscriber $user from mailing list $list
  public function del_subscriber($list, $user);

  // get mailing list attributes
  public function get_mailinglist_attribs($list);

  // update mailing list attributes
  public function update_mailinglist_attribs($list);

  // create mailing list
  public function create_mailinglist($list, $listowner);

  // delete mailing list
  public function delete_mailinglist($list);
}

// interface to ezmlm through qmailadmin webapp
// TODO: implement function exit codes
//       secure class parameters / input objects
// WARNING: no parameter / input validation implemented,
//          the class trusts anything
class Ezmlm implements MailingList
{
  // ezmlm admin webapp url
  private $url;

  // auth data with authorization token
  private $auth;

  // mailing lists
  private $mailinglists;

  // multidim array of subscribers
  private $subscribers;

  // multidim array of attributes
  // publicly accessible for ease of use
  public $attribs;

  // default attribs for new list as in qmailadmin
  const DEFAULT_ATTRIBS = array('prefix'=>'',
				'opt1'=>'Mu',
				'replyto'=>'1',
				'replyaddr'=>'',
				'opt6'=>'q',
				'opt11'=>'H',
				'opt13'=>'J',
				'opt14'=>'a',
				'opt15'=>'b',
				'sql1'=>'localhost',
				'sql2'=>'3306',
				'sql3'=>'',
				'sql4'=>'',
				'sql5'=>'',
				'sql6'=>'ezmlm');
  
  // basic contructor with login
  function __construct($url, $init_auth) {
    $this->url = $url;
    $this->auth = array();
    $this->mailinglists = array();
    $this->subscribers = array();
    $this->attribs = array();
    $this->login($init_auth);
    if (empty($this->auth['time'])) {
      throw new Exception('login failed');
    }
  }

  // destructor with logout
  function __destruct() {
    $this->logout();
  }
  
  private function logout() {
    // call logout
    $req = new HTTP_Request2($this->url."/com/logout");
    $req->setMethod(HTTP_Request2::METHOD_GET);
    $req->getUrl()->setQueryVariables($this->auth);
    $resp = $req->send();
  }

  // login: mandatory before using other operations
  private function login($init_auth) {
    // make login request
    $req = new HTTP_Request2($this->url);
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->addPostParameter($init_auth);
    $resp = $req->send();

    // parse response to extract auth token
    $dom = new DOMDocument();
    @$dom->loadHTML($resp->getBody());
    parse_str(parse_url($dom->getElementsByTagName('a')[0]->getAttribute('href'),
			PHP_URL_QUERY), $arr);
    // auth data
    $this->auth['user'] = $init_auth['username'];
    $this->auth['dom'] = $init_auth['domain'];
    if (isset($arr['time'])) {
      $this->auth['time'] = $arr['time'];
    }
  }

  // read mailinglists into class
  public function get_mailinglists() {
    // get mailing lists
    $req = new HTTP_Request2($this->url."/com/showmailinglists");
    $req->setMethod(HTTP_Request2::METHOD_GET);
    $req->getUrl()->setQueryVariables($this->auth);
    $resp = $req->send();

    // parse response to extract mailing lists
    $dom = new DOMDocument();
    @$dom->loadHTML($resp->getBody());

    $tds = $dom->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('td');

    $this->mailinglists = array();
    foreach ($tds as $td) {
      if ($td->getAttribute('align') == 'left') {
	$this->mailinglists[] = $td->textContent;
      }
    }
  }

  // read subscribers
  public function get_subscribers($list) {
    // get subscribers of given list
    $req = new HTTP_Request2($this->url."/com/showlistusers");
    $req->setMethod(HTTP_Request2::METHOD_GET);
    $req->getUrl()->setQueryVariables(array_merge($this->auth,
						  array('modu'=>$list)));
    $resp = $req->send();

    // parse response to extract subscribers
    $dom = new DOMDocument();
    @$dom->loadHTML($resp->getBody());

    $tds = $dom->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('table')[0]
      ->getElementsByTagName('td');

    $this->subscribers[$list] = array();
    foreach ($tds as $td) {
      if ($td->getAttribute('align') == 'left') {
	$this->subscribers[$list][] = $td->textContent;
      }
    }
  }

  // get whole subscriber database (a LOTS OF queries, might take MUCH long)
  public function get_subscribers_db() {
    // delete subscriber db
    $this->subscribers = array();

    // get mailing lists
    $this->get_mailinglists();

    // get subscribers for each of the mailing lists
    foreach($this->mailinglists as $ml) {
      $this->get_subscribers($ml);
    }
  }

  // dump array of mailinglists
  public function dump_mailinglists() {
    return $this->mailinglists;
  }

  // dump list of subscribers
  public function dump_subscribers($list) {
    return $this->subscribers[$list];
  }

  public function add_subscriber($list, $user) {
    // add subscriber with a simple POST query
    // WARNING: ezmlm sends an alert email to the subscribed user
    $req = new HTTP_Request2($this->url."/com/addlistusernow");
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->getUrl()->setQueryVariables($this->auth);
    $req->addPostParameter(array('modu'=>$list, 'newu'=>$user));
    $resp = $req->send();

    // refreshing list in class instance
    $this->get_subscribers($list);
  }

  public function del_subscriber($list, $user) {
    // delete subscriber with a simple POST query
    // WARNING: ezmlm sends an alert email to the deleted user
    $req = new HTTP_Request2($this->url."/com/dellistusernow");
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->getUrl()->setQueryVariables($this->auth);
    $req->addPostParameter(array('modu'=>$list, 'newu'=>$user));
    $resp = $req->send();

    // refreshing list in class instance
    $this->get_subscribers($list);
  }

  public function get_mailinglist_attribs($list) {
    // get mailinglist attribs by opening modify function
    $req = new HTTP_Request2($this->url."/com/modmailinglist");
    $req->setMethod(HTTP_Request2::METHOD_GET);
    $req->getUrl()->setQueryVariables(array_merge($this->auth,
						  array('modu'=>$list)));
    $resp = $req->send();

    // parse response to extract attributes
    $dom = new DOMDocument();
    @$dom->loadHTML($resp->getBody());

    foreach ($dom->getElementsByTagName('input') as $t) {
      switch ($t->getAttribute('name')) {
      case "opt1":
	// posting messages:
	//   MU: anyone
	//   Mu: only subscribers, others bounce
	//   mu: only subscribers, others go to moderator for approval
	//   mUo: only moderators, others bounce
	//   mUO: only moderators, others go to moderator for approval
	if ($t->getAttribute('checked')) {
	  $this->attribs[$list]['opt1'] = $t->getAttribute('value');
	}
	break;
      case "replyto":
	// replyto default value:
	//   1: original sender
	//   2: entire list
	//   3: address specified in 'replyaddr'
	if ($t->getAttribute('checked')) {
	  $this->attribs[$list]['replyto'] = $t->getAttribute('value');
	}
	break;
      case "listowner":
      case "prefix":
      case "replyaddr":
      case "sql1":
      case "sql2":
      case "sql3":
      case "sql4":
      case "sql5":
      case "sql6":
      case "newu":
	$this->attribs[$list][$t->getAttribute('name')] =
	  $t->getAttribute('value');
        break;
      default:
	// opt4="t": include trailer at end of messages
	// opt5="d": set up digest version of the list
	// opt6="q": service requests sent to listname-request
	// opt7="r": allow remote admin by moderators
	// opt8="P": private list
	// opt9="l": remote admins can view subscribers
	// opt10="n": remote admins can edit text files
	// opt11="H": subscription require confirmation by subscriber
	// opt12="s": subscription require approval of a moderator
	// opt13="J": unsubscription require confirmation by subscriber
	// opt14="a": archive list messages
	// opt16="i": index archive
	if ($t->getAttribute('checked')) {
	  $this->attribs[$list][$t->getAttribute('name')] =
	    $t->getAttribute('value');
	} else {
	  $this->attribs[$list][$t->getAttribute('name')] = "";
	}
      }
    }

    foreach ($dom->getElementsByTagName('select')[0]->
	     getElementsByTagName('option') as $t) {
      // opt15="BG": archive retrieval is open to anyone
      // opt15="Bg": limited to subscribers
      // opt15="b": limited to moderators
      if ($t->getAttribute('selected')) {
	$this->attribs[$list]['opt15'] = $t->getAttribute('value');
      }
      
    }
  }

  public function update_mailinglist_attribs($list) {
    $req = new HTTP_Request2($this->url."/com/modmailinglistnow");
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->getUrl()->setQueryVariables($this->auth);
    $this->attribs[$list]['newu'] = $list;   // for safety
    $req->addPostParameter($this->attribs[$list]);
    $resp = $req->send();
  }

  public function create_mailinglist($list, $listowner) {
    $req = new HTTP_Request2($this->url."/com/addmailinglistnow");
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->getUrl()->setQueryVariables($this->auth);
    $this->attribs[$list] = $this::DEFAULT_ATTRIBS;
    $this->attribs[$list]['newu'] = $list;
    $this->attribs[$list]['listowner'] = $listowner;
    $req->addPostParameter($this->attribs[$list]);
    $resp = $req->send();

    // refresh lists in class
    $this->get_mailinglists();
  }

  public function delete_mailinglist($list) {
    $req = new HTTP_Request2($this->url."/com/delmailinglistnow");
    $req->setMethod(HTTP_Request2::METHOD_POST);
    $req->getUrl()->setQueryVariables($this->auth);
    $req->addPostParameter(array('modu'=>$list));
    $resp = $req->send();
    
    // refresh lists in class
    $this->get_mailinglists();
  }
  
}

?>
