<?php

// using ldap bind
$ldaprdn  = 'sa_lth-typo3';     // ldap rdn or dn
$ldappass = 'iO5JJJxnJhzFC!!!!';  // associated password

// connect to ldap server
$ldapconn = ldap_connect("ldaps://uw.lu.se")
    or die("Could not connect to LDAP server.");

if ($ldapconn) {

    // binding to ldap server
    $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

    // verify binding
    if ($ldapbind) {
        echo "LDAP bind successful...";
    } else {
        echo "LDAP bind failed...";
    }

}