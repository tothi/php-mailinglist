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
  // public function get_mailinglist_attribs($list);

  // set mailing list attributes
  // public function set_mailinglist_attribs($list, $attrib);

  // create mailing list
  // public function create_mailinglist($list);

  // delete mailing list
  // public function delete_mailinglist($list);
}

// interface to ezmlm through qmailadmin webapp
// TODO: implement function exit codes, implement missing functions
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
  private $attrib;
  
  // basic contructor with login
  function __construct($url, $init_auth) {
    $this->url = $url;
    $this->auth = array();
    $this->mailinglists = array();
    $this->subscribers = array();
    $this->attrib = array();
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
  public function dump_subscribers() {
    return $this->subscribers;
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
}

?>
