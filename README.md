# php-mailinglist
php interface to mailing lists through web interface (e.g. ezmlm, qmailadmin)

## status
current status is beta testing.

* Ezmlm functions through QmailAdmin (>90%) are implemented:
 * read mailing lists
 * read subscribers of a list
 * add subscriber to a list
 * delete subscriber from a list
 * read moderators of a list
 * add moderator to a list
 * delete moderator from a list
 * read mailing list attributes
 * modify mailing list attributes
 * create new mailing list
 * delete mailing list

* Known issues:
 * 'prefix' option not working (as in qmailadmin)
