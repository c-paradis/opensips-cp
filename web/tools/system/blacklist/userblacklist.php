<?php
/*
 * $Id$
 * Copyright (C) 2016 PARADIS Corentin
 *
 * This file is part of opensips-cp, a free Web Control Panel Application for 
 * OpenSIPS SIP server.
 *
 * opensips-cp is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * opensips-cp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require("template/header.php");
require_once("lib/functions.inc.php");
require("lib/db_connect.php");
require("lib/blacklist.main.js");

global $config;

$action="";
if (isset($_GET['action']))
	$action = $_GET['action'];

$current_page="current_page_userblacklist";

if (isset($_GET['page'])) $_SESSION[$current_page]=$_GET['page'];
else if (!isset($_SESSION[$current_page])) $_SESSION[$current_page]=1;
$page = $_SESSION[$current_page];

##############
# start add  #
##############

if($action == "add"){
	if(!$_SESSION['read_only']){
		require("template/".$page_id.".add.php");
		require('template/footer.php');
		exit();
	}else{
		$error = "User with Read-Only Rights";
	}
}

##############
# end add    #
##############


#####################
# start add_verify  #
#####################

if($action == "add_verify"){
	if(!$_SESSION['read_only']){
		$username = $_POST['username'];
		$domain = $_POST['domain'];
		$prefix = $_POST['prefix'];

		
		if(strlen($domain) > 64) $error="Entered domain name too long (" . strlen($domain) . " chars, 64 max authorized) : ". htmlspecialchars($domain);
		else{
			$whitelist = isset($_POST['whitelisted']) ? '1' : '0';
			if(empty($prefix)) $error = "You have to specify a prefix !";
			else{
				if(empty($username)) $error = "You have to specify a username !";
				else{
					if(empty($domain)) $error = "You have to specify a domain !";
					else{
						$verif = verif_entries($username, $prefix, $domain, $whitelist, $action);
						if($verif[0]){
							$sql = "INSERT INTO userblacklist (id, username, domain, prefix, whitelist) VALUES ('', :username, :domain, :prefix, :whitelist)";
							$resultset = $link->prepare($sql);

							$resultset->execute(array(
								"username"=>$username,
								"domain"=>$domain,
								"prefix"=>$prefix,
								"whitelist"=>$whitelist
								));
							$resultset->free();
							$log = $verif[1];
						}else{
							$error = $verif[1];
						}
					}
				}
			}
		}
	}else{
		$error = "User with Read-Only Rights";
	}
}

#####################
# end add_verify    #
#####################


#################
# start delete  #
#################

if($action == "delete"){

	if(!$_SESSION['read_only']){
		$id = $_GET['id'];
		$sql = "SELECT * FROM userblacklist WHERE id='$id'";
		$resultset = $link->query($sql);

		if(PEAR::isError($resultset)) {
			die('Failed to issue query, error message : ' . $resultset->getMessage());
		}

		if ( $resultset->numRows() == 0 ) $error="This entry doesn't exist !";
		else {
			$resultset->free();
			
			$sql = "DELETE FROM userblacklist WHERE id=:id";
			$resultset = $link->prepare($sql);

			$resultset->execute(array(
				"id"=>$id
				));
			$resultset->free();
			$log = "Entry successfully deleted !<hr/>";
		}
		
	}else{
		$error = "User with Read-Only Rights";
	}
}

#################
# end delete    #
#################


################
# start search #
################
if ($action=="dp_act")
{
	$_SESSION[$current_page]=1;
	extract($_POST);
	if ($show_all=="Show All") {
		$_SESSION['lst_u_prefix']="";
		$_SESSION['lst_u_whitelist']="";
		$_SESSION['lst_u_username']="";
		$_SESSION['lst_u_domain']="";
	} else if($search=="Search"){
		$_SESSION['lst_u_prefix']=$_POST['lst_prefix'];
		$_SESSION['lst_u_username']=$_POST['lst_username'];
		$_SESSION['lst_u_whitelist']= isset($_POST['lst_whitelist']) ? $_POST['lst_whitelist'] : "";
		$_SESSION['lst_u_domain']=$_POST['lst_domain'];
	} 
}
##############
# end search #
##############


##############
# start edit #
##############
if ($action=="edit")
{
	if(!$_SESSION['read_only']){
		$id = $_GET['id'];
		$sql = "SELECT * FROM userblacklist WHERE id='$id'";
		$resultset = $link->query($sql);

		if(PEAR::isError($resultset)) {
			die('Failed to issue query, error message : ' . $resultset->getMessage());
		}

		if ( $resultset->numRows() == 0 ) $error="This entry doesn't exist !";
		else {
			$entry = $resultset->fetchRow();

			$resultset->free();

			require("template/".$page_id.".edit.php");
			require("template/footer.php");
			exit();
		}
	}else{
		$error = "User with Read-Only Rights";
	}
}
##############
# end edit   #
##############


#################
# start modify  #
#################

if($action == "modify"){
	if(!$_SESSION['read_only']){
		$id = $_GET['id'];
		$prefix = $_POST['prefix'];
		$domain = $_POST['domain'];
		$username = $_POST['username'];
		$whitelist = isset($_POST['whitelisted']) ? '1' : '0';
		
		if(strlen($domain) > 64) $error="Entered domain name too long (" . strlen($domain) . " chars, 64 max authorized) : ". htmlspecialchars($domain);
		else{
			if(!isset($error)){
				$sql = "SELECT * FROM userblacklist WHERE id='$id'";
				$resultset = $link->query($sql);

				if(PEAR::isError($resultset)) {
					die('Failed to issue query, error message : ' . $resultset->getMessage());
				}

				if ( $resultset->numRows() == 0 ) $error = "This entry doesn't exist !";
				else {
					$resultset->free();
					if(empty($prefix)) $error = "You have to specify a prefix !";
					else{
						if(empty($username)) $error = "You have to specify a username !";
						else{
							if(empty($domain)) $error = "You have to specify a domain !";
							else{
								$sql = "SELECT * FROM userblacklist WHERE username='$username' AND prefix='$prefix' AND domain='$domain' AND id!='$id'";
								$resultset = $link->query($sql);

								if(PEAR::isError($resultset)) {
									die('Failed to issue query, error message : ' . $resultset->getMessage());
								}

								if ( $resultset->numRows() == 1 ) $error="This entry already exists, no need to change it !";
								else {
									$resultset->free();
									$sql = "UPDATE userblacklist SET prefix=:prefix, username=:username, domain=:domain, whitelist=:whitelist WHERE id=:id";
									$resultset = $link->prepare($sql);

									$resultset->execute(array(
										"id"=>$id,
										"prefix"=>$prefix,
										"username"=>$username,
										"domain"=>$domain,
										"whitelist"=>$whitelist
										));
									$resultset->free();
									$log = $prefix . " successfully " . ($whitelist ? "whitelisted" : "blacklisted") . " for " . $username  . (($domain != "*") ? "@" . $domain : "") . "<hr/>";
								}
							}
						}
					}
				}
			}
		}
	}else{
		$error = "User with Read-Only Rights";
	}
}

#################
# end modify    #
#################


##############
# start main #
##############

require("template/".$page_id.".main.php");
require("template/footer.php");
exit();

##############
# end main   #
##############
?>
