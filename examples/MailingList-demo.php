<?php

require_once 'MailingList.php';  // edit to correct path...

// Ezmlm demo
echo "<pre>\n";
echo "--- BEGIN Mailing List interface demo ---\n\n";

$login_url    = "*** URL to qmailadmin web if";
$login_user   = "*** username for logging in, e.g. postmaster";
$login_domain = "*** domain of the ezmlm list";
$login_passwd = "*** pass to log in as $user (e.g. postmaster)";

// create object (with login success checking)
echo "creating Ezmlm object...";
try {
  $myMailingList = new Ezmlm($login_url,
			     array('username'=>$login_user,
				   'domain'=>$login_domain,
				   'password'=>$login_passwd));
} catch(Exception $e) {
  echo $e->getMessage();
  exit(1);
}
echo "logged in successful.\n\n";

// read mailing lists
echo "getting mailing lists...";
$myMailingList->get_mailinglists();
echo "done.\n\n";

// dump read mailing lists
echo "--- BEGIN dump of mailing lists ---\n";
var_dump($myMailingList->dump_mailinglists());
echo "--- END dump of mailing lists ---\n\n";

// creating new list for testing
$list = 'test2';
$listowner = 'listowner@example.com';
echo "creating list '" . $list . "' with owner '" . $listowner . "'...";
$myMailingList->create_mailinglist($list, $listowner);
echo "done.\n\n";

// dump read mailing lists
echo "--- BEGIN dump of mailing lists ---\n";
var_dump($myMailingList->dump_mailinglists());
echo "--- END dump of mailing lists ---\n\n";

// read subscribers of a list
echo "reading list '" . $list . "'...";
$myMailingList->get_subscribers($list);
echo "done.\n\n";

// dump subscribers of a list
echo "--- BEGIN dump of subscribers of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->dump_subscribers($list));
echo "--- END dump of subscribers ---\n\n";

// get full subscriber database
//$myMailingList->get_subscribers_db();

// dump subscribers
//var_dump($myMailingList->dump_subscribers());

// add subscriber to a list
$user = 'subscriber@example.com';
echo "adding user '" . $user . "' to list '" . $list . "'...";
$myMailingList->add_subscriber($list, $user);
echo "done.\n\n";

// dump subscribers of a list
echo "--- BEGIN dump of subscribers of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->dump_subscribers($list));
echo "--- END dump of subscribers ---\n\n";

// delete subscriber from a list
echo "deleting user '" . $user . "' from list '" . $list . "'...";
$myMailingList->del_subscriber($list, $user);
echo "done.\n\n";

// dump subscribers of a list
echo "--- BEGIN dump of subscribers ---\n";
var_dump($myMailingList->dump_subscribers($list));
echo "--- END dump of subscribers ---\n\n";


// read moderators of a list
echo "reading list '" . $list . "'...";
$myMailingList->get_moderators($list);
echo "done.\n\n";

// dump moderators of a list
echo "--- BEGIN dump of moderators of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->dump_moderators($list));
echo "--- END dump of moderators ---\n\n";

// get full moderator database
//$myMailingList->get_moderators_db();

// dump moderators
//var_dump($myMailingList->dump_moderators());

// add moderator to a list
$user = 'moderator@example.com';
echo "adding user '" . $user . "' to list '" . $list . "'...";
$myMailingList->add_moderator($list, $user);
echo "done.\n\n";

// dump moderators of a list
echo "--- BEGIN dump of moderators of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->dump_moderators($list));
echo "--- END dump of moderators ---\n\n";

// delete moderator from a list
echo "deleting user '" . $user . "' from list '" . $list . "'...";
$myMailingList->del_moderator($list, $user);
echo "done.\n\n";

// dump moderators of a list
echo "--- BEGIN dump of moderators ---\n";
var_dump($myMailingList->dump_moderators($list));
echo "--- END dump of moderators ---\n\n";

// get attributes of a list
echo "getting attributes of mailinglist `" . $list . "`...";
$myMailingList->get_mailinglist_attribs($list);
echo "done.\n\n";

// dump attributes of list
echo "--- BEGIN dump of attributes of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->attribs[$list]);
echo "--- END dump of attributes ---\n\n";

// modify attribute of list
echo "setting list '" . $list . "' to private...";
$myMailingList->attribs[$list]['opt8']='P';
$myMailingList->update_mailinglist_attribs($list);
echo "done.\n\n";


// prefix doesn't work even in qmailadmin
// TODO: find a fix
$prefix="helloworld";
echo "setting prefix '" . $prefix . "'...";
$myMailingList->attribs[$list]['prefix']=$prefix;
$myMailingList->update_mailinglist_attribs($list);
echo "done.\n\n";

// dump attributes of list
echo "--- BEGIN dump of attributes of mailinglist `" . $list . "` ---\n";
var_dump($myMailingList->attribs[$list]);
echo "--- END dump of attributes ---\n\n";

// delete testing list
echo "deleting list '". $list . "'...";
$myMailingList->delete_mailinglist($list);
echo "done.\n\n";

// dump read mailing lists
echo "--- BEGIN dump of mailing lists ---\n";
var_dump($myMailingList->dump_mailinglists());
echo "--- END dump of mailing lists ---\n\n";


// destroying object => logging out
echo "destroying Ezmlm object...";
unset($myMailingList);
echo "logged out.\n\n";

echo "--- END Mailing List interface demo ---\n\n";

?>
