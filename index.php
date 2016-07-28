<?php
	$debug=false;
	
	if(file_exists("log/logfile.txt")) unlink("log/logfile.txt");
	function writelog($msg) {
		file_put_contents("log/logfile.txt", date('d M y h:i:sa', time())." ".$msg."\n", FILE_APPEND);
	}
	
	if($debug) writelog("== Log start ==");
	
	if(isset($_COOKIE['viewportwidth'])) $viewportwidth=$_COOKIE['viewportwidth'];
	else $viewportwidth=1920;
	if(isset($_COOKIE['viewportheight'])) $viewportheight=$_COOKIE['viewportheight'];
	else $viewportheight=1080;
	$sqltext="";
	$remove=Array("	","\t","\r\n","\r","\n",chr(13),chr(10),chr(8),chr(9));
	
	function showlogin($message='',$showhead=false){
		if($GLOBALS['debug']) writelog("showlogin(".$message.",".$showhead.")");
		@setcookie('server', '', time()-3600);
		@setcookie('username', '', time()-3600);
		@setcookie('password', '', time()-3600);
		if(!isset($message) || $message==null) $message="";
		if($showhead==true){
			echo "<html>
<head>
	<title>MySQL login</title>
	<style>
		body{
			font-family:Verdana, Geneva, Tahoma, sansserif, sans-serif;
			overflow:hidden;
			background-color: white;
			background-image: url('images/mysql.png');
			background-repeat: no-repeat;
			background-position: center center;
			background-attachment: fixed;
		}
		.login{
			position:absolute;
			text-align:center;
			top:50%;
			height:200px;
			margin-top:-".($GLOBALS['viewportheight']/2)."px; /* negative half of the height */
			vertical-align:middle;
			margin:auto;
			width:99%;
			z-index:1;
			display:block;
			overflow:hidden;
		}
	</style>
	<script type='text/javascript' src='./js/common.js'></script>
</head>
<body onload='redrawscreen();'>";
		}
		echo "<div class='login'>
					".$message."<br>
					<form method='post' id='login'>
						Server: <input type='text' name='server' value='localhost' /><br>
						Username: <input type='text' name='username' /><br>
						Password: <input type='password' name='password' /><br>
						<input type='submit' name='login' value='Login' />
					</form>
					(c) DairyWindow Ltd 2014<br>
					dairywindow@gmail.com
				</div>
			</body>
			</html>";
		die();
	}
	
	if(isset($_POST['login'])){
		if($debug) writelog("login is set and it is: ".$_POST['login']);
		//write cookies
		if(isset($_POST['server']) && $_POST['server']!="" && $_POST['server']!=null) setcookie('server', htmlentities($_POST['server'], ENT_QUOTES), time()+3600);
		else setcookie('server', 'localhost', time()+3600);
		if(isset($_POST['username']) && $_POST['username']!="" && $_POST['username']!=null) setcookie('username', htmlentities($_POST['username'], ENT_QUOTES), time()+3600);
		else showlogin('You did not specify a username',true);
		if(isset($_POST['password']) && $_POST['password']!="" && $_POST['password']!=null) setcookie('password', htmlentities($_POST['password'], ENT_QUOTES), time()+3600);
		else showlogin('You did not specify a password',true);
		
		$location=$_SERVER['PHP_SELF'];
		if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!="" && $_SERVER['QUERY_STRING']!=null) {
			if($_SERVER['QUERY_STRING']!="logout=true") $location.="?".$_SERVER['QUERY_STRING'];
		}
		header('Location: '.$location);
		echo "<a href='".$location."'>Click here to continue</a>";
		die();
	}else{
		if($debug) writelog("login is not set");
		if(isset($_GET['logout'])) showlogin('You have been logged out',true);
		else{
			if(isset($_COOKIE['username'])){
				$server=$_COOKIE['server'];
				$username=$_COOKIE['username'];
				$password=$_COOKIE['password'];
				setcookie('server',$server,time()+3600);
				setcookie('username',$username,time()+3600);
				setcookie('password',$password,time()+3600);
			}else showlogin('Welcome. Please log in',true);
		}
	}
	
	if(isset($_GET['refresh'])) $refresh=true;
	else $refresh=false;
	if($debug){
		if($refresh) writelog("refresh is: True");
		else writelog("refresh is: False");
	}
	
	//heres where the magic happens
	$popuptext=array();
	if(isset($_POST['sqlsubmit']) && isset($_POST['customsql'])){
		if($debug) writelog("sqlsubmit: ".$_POST['sqlsubmit']." and customsql: ".$_POST['customsql']);
		//custom sql
		if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
		mysqli_set_charset($link, "utf8");
		$sql=$_POST['customsql'];
		if(!$GLOBALS['customqry']=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
		$GLOBALS['affected']=mysqli_affected_rows($link);
		//else $popuptext[]="Cell updated successfully";
	}else{
		if(isset($_POST['oldvalue']) && isset($_POST['newvalue'])){ //newvalue is allowed to be null
			if($debug) writelog("oldvalue: ".$POST['oldvalue']." and newvalue:".$_POST['newvalue']);
			//update a value in a table cell
			if($_POST['oldvalue']!=$_POST['newvalue']){
				if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
				mysqli_set_charset($link, "utf8");
				if($_POST['newvalue']=="" || $_POST['newvalue']==NULL) $newvalue="NULL";
				else $newvalue="'".mysqli_real_escape_string($link,$_POST['newvalue'])."'";
				if($_POST['value']=="" || $_POST['value']=="null") $cellvalue="NULL";
				else $cellvalue="'".$_POST['value']."'";
				$sql="UPDATE ".$_POST['table']." SET ".$_POST['column']."=".$newvalue." WHERE ".$_POST['field']."=".$cellvalue;
				if(!$updateqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
				else $popuptext[]="Cell updated successfully";
			}
		}else{
			if(isset($_GET['delrow'])){
				if(!$link=@mysqli_connect($server,$username,$password,$_GET['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
				mysqli_set_charset($link, "utf8");
				$sql="DELETE FROM ".$_GET['table']." WHERE ";
				foreach($_GET as $rowname => $rowvalue) if($rowname!="database" && $rowname!="table" && $rowname!="delrow") $sql.=$rowname."='".$rowvalue."' AND ";
				$sql=substr($sql,0,-5); //remove ' AND '
				if(!$delqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
				else $popuptext[]="Row deleted successfully";
			}else{
				if(isset($_POST['inserttablename']) && $_POST['inserttablename']!="" && $_POST['inserttablename']!=null){
					if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
					mysqli_set_charset($link, "utf8");
					$sql="CREATE TABLE IF NOT EXISTS ".mysqli_real_escape_string($link,$_POST['inserttablename'])." (".substr(mysqli_real_escape_string($link,$_POST['inserttablename']),0,1)."ID int NOT NULL AUTO_INCREMENT, PRIMARY KEY (".substr(mysqli_real_escape_string($link,$_POST['inserttablename']),0,1)."ID))";
					if(isset($_POST['engine']) && $_POST['engine']!="" && $_POST['engine']!=null) $sql.=" ENGINE=".$_POST['engine'];
					if(isset($_POST['collation']) && $_POST['collation']!="" && $_POST['collation']!=null) $sql.=" COLLATE=".$_POST['collation'];
					if(!$createqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
					else $popuptext[]="Table '".mysqli_real_escape_string($link,$_POST['inserttablename'])."' created";
					$refresh=true;
				}else{
					if(isset($_POST['fieldname']) && $_POST['fieldname']!="" && $_POST['fieldname']!=null){
						if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
						mysqli_set_charset($link, "utf8");
						$sql="ALTER TABLE ".$_GET['table']." ADD COLUMN ".mysqli_real_escape_string($link,$_POST['fieldname'])." ".$_POST['coltype'];
						if(isset($_POST['collength']) && $_POST['collength']!="" && $_POST['collength']!=null) $sql.="(".mysqli_real_escape_string($link,$_POST['collength']).")";
						if(isset($_POST['signed']) && $_POST['signed']=="unsigned") $sql.=" UNSIGNED";
						if(isset($_POST['allownull'])){
							if($_POST['allownull']=="No") $sql.=" NOT NULL";
							else $sql.=" NULL";
						}
						if(isset($_POST['default']) && $_POST['default']!="" && $_POST['default']!=null){
							$sql.=" DEFAULT ";
							if(stristr(mysqli_real_escape_string($link,$_POST['default'])," ")) $sql.="'";
							$sql.=mysqli_real_escape_string($link,$_POST['default']);
							if(stristr(mysqli_real_escape_string($link,$_POST['default'])," ")) $sql.="'";
						}
						if(isset($_POST['extra']) && $_POST['extra']=="Auto increment") $sql.=" AUTO_INCREMENT";
						if(isset($_POST['key'])){
							if($_POST['key']=="Primary") $sql.=" PRIMARY KEY";
							if($_POST['key']=="Unique") $sql.=" UNIQUE";
						}
						if(!$insertqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
						else $popuptext[]="Column '".mysqli_real_escape_string($link,$_POST['fieldname'])."' created";
						//$refresh=true;
					}else{
						if(isset($_POST['insertrow']) && $_POST['insertrow']!="" && $_POST['insertrow']!=null){
							if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
							mysqli_set_charset($link, "utf8");
							/*
							INSERT INTO table_name (column1, column2, column3,...)
							VALUES (value1, value2, value3,...)
							*/
							$colstoinsert=array();
							$valstoinsert=array();
							foreach($_POST as $key => $value){
								//echo "key:".$key."; value:".$value."<br>";
								if($key!="database" && $key!="table" && $key!="insertrow"){
									$colstoinsert[]=$key;
									$valstoinsert[]=$value;
								}
							}
							$sql="INSERT INTO ".$_POST['table']." (";
							foreach($colstoinsert as $x) $sql.=$x.", ";
							$sql=substr($sql,0,-2).")";
							//insert into tablename (a, b, c)
							$sql.=" VALUES (";
							foreach($valstoinsert as $y){
								if(strtolower($y)=="current_timestamp") $sql.="CURRENT_TIMESTAMP, ";
								else {
									if(strtolower($y)=="null" || $y=="" || $y==null) $sql.="NULL, ";
									else $sql.="'".str_replace("'","\'",$y)."', ";
								}
							}
							$sql=substr($sql,0,-2).")";
							//insert into tablename (a, b, 3) vacues (1, 2, 3)
							if(!$insertqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
							else $popuptext[]="Row inserted";
						}else{
							if(isset($_POST['dropcolumn']) && $_POST['dropcolumn']!="" && $_POST['dropcolumn']!=null){
								if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
								mysqli_set_charset($link, "utf8");
								$sql="ALTER TABLE ".$_GET['table']." DROP COLUMN ".mysqli_real_escape_string($link,$_POST['column']);
								if(!$dropqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
								else $popuptext[]="Column '".mysqli_real_escape_string($link,$_POST['column'])."' was dropped";
								$refresh=true;
							}else{
								if(isset($_POST['droprow']) && $_POST['droprow']!="" && $_POST['droprow']!=null){
									if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
									mysqli_set_charset($link, "utf8");
									$sql="DELETE FROM ".$_POST['table']." WHERE ";
									foreach($_POST as $key => $value) if($key!="database" && $key!="table" && $key!="droprow" && $value!="") $sql.=$key."='".$value."' AND ";
									$sql=substr($sql,0,-5);
									if(!$dropqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
									else $popuptext[]="Row was dropped";
								}else{
									if(isset($_POST['alterdatabase']) && $_POST['alterdatabase']!="" && $_POST['alterdatabase']!=null){
										if($_POST['olddatabase']!=$_POST['newdatabase']){
											//rename database
											mysqli_set_charset($link, $_POST['newcharset']);
											$sql="CREATE DATABASE IF NOT EXISTS ".mysqli_real_escape_string($link,$_POST['newdatabase'])." CHARACTER SET=".$_POST['newcharset']." COLLATE=".$_POST['newcollation'];
											if(!$createqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
											//get list of tables in old database
											$sql="SHOW TABLES IN ".$_POST['olddatabase'];
											if(!$listqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
											while($listarray=mysqli_fetch_array($listqry)){
												$sql="RENAME TABLE ".$_POST['olddatabase'].".".$listarray[0]." TO ".mysqli_real_escape_string($link,$_POST['newdatabase']).".".$listarray[0];
												if(!$createqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
											}
											mysqli_free_result($listqry);
											if(count($popuptext==0)) $popuptext[]="Database renamed from '".$_POST['olddatabase']."' to '".mysqli_real_escape_string($link,$_POST['newdatabase'])."'";
											$refresh=true;
										}else{
											if($_POST['oldcharset']!=$_POST['newcharset']){
												if(!$link=@mysqli_connect($server,$username,$password,$_POST['olddatabase'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
												mysqli_set_charset($link, $_POST['newcharset']);
												if($_POST['oldcollation']!=$_POST['newcollation']){
													//change charset and collation
													if(!$link=@mysqli_connect($server,$username,$password,$_POST['olddatabase'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
													$sql="ALTER DATABASE ".$_POST['olddatabase']." CHARACTER SET=".$_POST['newcharset']." COLLATE=".$_POST['newcollation'];
													if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
													else $popuptext[]="Character set and collation changed for '".$_POST['olddatabase']."'";
												}else{
													//change charset only
													if(!$link=@mysqli_connect($server,$username,$password,$_POST['olddatabase'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
													$sql="ALTER DATABASE ".$_POST['olddatabase']." CHARACTER SET=".$_POST['newcharset'];
													if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
													else $popuptext[]="Character set changed for '".$_POST['olddatabase']."'";
												}
											}else{
												if($_POST['oldcollation']!=$_POST['newcollation']){
													//change collation only
													if(!$link=@mysqli_connect($server,$username,$password,$_POST['olddatabase'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
													mysqli_set_charset($link, "utf8");
													$sql="ALTER DATABASE ".$_POST['olddatabase']." COLLATE=".$_POST['newcollation'];
													if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
													else $popuptext[]="Collation changed for '".$_POST['olddatabase']."'";
												}else{
													//form submitted with no values changed
												}
											}
										}
									}else{
										if(isset($_POST['altertablego']) && $_POST['altertablego']!="" && $_POST['altertablego']!=null){ //alter blego??? No, alter table go
											if(isset($_POST['newtable']) && $_POST['oldtable']!=$_POST['newtable']){
												if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
												mysqli_set_charset($link, "utf8");
												$sql="RENAME TABLE ".$_POST['oldtable']." TO ".mysqli_real_escape_string($link,$_POST['newtable']);
												if(!$renameqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
												$popuptext[]="Table renamed from '".$_POST['oldtable']."' to '".mysqli_real_escape_string($link,$_POST['newtable'])."'";
												$refresh=true;
											}else{
												if(isset($_POST['newengine']) && $_POST['oldengine']!=$_POST['newengine']){
													if(isset($_POST['newcollation']) && $_POST['oldcollation']!=$_POST['newcollation']){
														//change engine and collation
														if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
														$sql="ALTER TABLE ".$_POST['oldtable']." ENGINE=".$_POST['newengine']." COLLATE=".$_POST['newcollation'];
														if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
														else $popuptext[]="Engine and collation changed for '".$_POST['oldtable']."'";
													}else{
														//change engine only
														if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
														$sql="ALTER TABLE ".$_POST['oldtable']." ENGINE=".$_POST['newengine'];
														if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
														else $popuptext[]="Engine changed for '".$_POST['oldtable']."'";
													}
												}else{
													if(isset($_POST['newcollation']) && $_POST['oldcollation']!=$_POST['newcollation']){
														//change collation only
														if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
														$sql="ALTER TABLE ".$_POST['oldtable']." COLLATE=".$_POST['newcollation'];
														if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
														else $popuptext[]="Collation changed for '".$_POST['oldtable']."'";
													}else{
														if(isset($_POST['autoincrement']) && $_POST['oldautoincrement']!=$_POST['autoincrement']){
															if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
															$sql="ALTER TABLE ".$_POST['oldtable']." AUTO_INCREMENT=".$_POST['autoincrement'];
															if(!$alterqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
															else $popuptext[]="Auto increment value changed for '".$_POST['oldtable']."'";
														}else{
															//form submitted with no values changed
														}
													}
												}
											}
										}else{
											if(isset($_POST['updatecol']) && $_POST['updatecol']!="" && $_POST['updatecol']!=null){
												if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
												mysqli_set_charset($link, "utf8");
												if($_POST['oldkey']!=$_POST['newkey'] && $_POST['newkey']==""){
													//if its unique, use ALTER TABLE mytable DROP INDEX column_name
													if($_POST['oldkey']=="Unique") $sql="ALTER TABLE ".$_POST['table']." DROP INDEX ".$_POST['oldcolumn'];
													//if its primary, use ALTER TABLE mytable DROP PRIMARY KEY
													else if($_POST['oldkey']=="Primary") $sql="ALTER TABLE ".$_POST['table']." DROP PRIMARY KEY";
												}else{
													if(($_POST['oldcolumn']!=$_POST['newcolumn']) || ($_POST['olddatatype']!=$_POST['newdatatype']) || ($_POST['newcollength']!=$_POST['oldcollength']) || ($_POST['newallownull']!=$_POST['oldallownull'])){
														//ALTER TABLE table_name CHANGE COLUMN oldcolumn newcolumn definition
														$sql="ALTER TABLE ".$_POST['table']." CHANGE COLUMN ".$_POST['oldcolumn']." ".mysqli_real_escape_string($link,$_POST['newcolumn'])." ".$_POST['newdatatype'];
														if($_POST['newcollength']!="") $sql.="(".mysqli_real_escape_string($link,$_POST['newcollength']).")";
														if(isset($_POST['signed']) && $_POST['signed']=="Unsigned") $sql.=" UNSIGNED";
														if(isset($_POST['newallownull']) && $_POST['newallownull']=="No") $sql.=" NOT NULL";
														if(isset($_POST['default']) && ($_POST['default']==strtolower("current_timestamp") || $_POST['default']==strtolower("current timestamp"))) $sql.=" DEFAULT CURRENT_TIMESTAMP";
													}else{
														//ALTER TABLE table_name MODIFY COLUMN column_name [datatype] [UNSIGNED] [NULL | NOT NULL] [DEFAULT default_value] {[AUTO_INCREMENT] [UNIQUE KEY | PRIMARY KEY] | [ON UPDATE CURRENT_TIMESTAMP]}
														$sql="ALTER TABLE ".$_POST['table']." MODIFY COLUMN ".$_POST['oldcolumn']." ";
														//if(isset($_POST['default']) && ($_POST['default']==strtolower("current_timestamp") || $_POST['default']==strtolower("current timestamp"))) $sql.=$_POST['oldcolumn']." ";
														$sql.=$_POST['newdatatype'];
														if($_POST['newcollength']!="" && $_POST['newcollength']!=NULL) $sql.="(".$_POST['newcollength'].")";
														else if($_POST['newdatatype']!='date' && $_POST['newdatatype']!='datetime') $sql.="()";
														if($_POST['signed']=="Unsigned") $sql.=" UNSIGNED";
														if($_POST['newallownull']=="Yes") $sql.=" NULL";
														if($_POST['newallownull']=="No") $sql.=" NOT NULL";
														if(isset($_POST['default']) && $_POST['default']!="" && $_POST['default']!=null){
															$sql.=" DEFAULT ";
															if(($_POST['default']==strtolower("current_timestamp") || $_POST['default']==strtolower("current timestamp"))) $sql.="CURRENT_TIMESTAMP";
															else{
																if(strtolower($_POST['default'])=="null") $sql.="NULL";
																else $sql.="'".mysqli_real_escape_string($link,$_POST['default'])."'";
															}
														}
														if($_POST['extra']=="Auto increment") $sql.=" AUTO_INCREMENT";
														if($_POST['oldkey']!=$_POST['newkey'] && $_POST['newkey']=="Unique") $sql.=" UNIQUE KEY";
														else if($_POST['oldkey']!=$_POST['newkey'] && $_POST['newkey']=="Primary") $sql.=" PRIMARY KEY";
														if($_POST['newdatatype']=="timestamp" && $_POST['extra']==strtolower("on update current timestamp")) $sql.=" ON UPDATE CURRENT_TIMESTAMP";
													}
												}
												if(!$alterqry=mysqli_query($link,$sql)){
													switch(mysqli_errno($link)){
														case 1067:
															if(($_POST['default']==strtolower("current_timestamp") || $_POST['default']==strtolower("current timestamp"))) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link)."<br>Make sure the column type is TIMESTAMP (datetime won't work)";
															break;
														case 1138:
															$popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link)."<br>One of the values in an existing column is NULL";
															break;
														case 1366:
															$popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link)."<br>One of the values in an existing column is the wrong datatype";
															break;
														default:
															$popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
													}
												}else $popuptext[]="Column '".$_POST['newcolumn']."' in '".$_POST['table']."' updated";
											}else{
												if(isset($_POST['droptablego']) && $_POST['droptablego']!="" && $_POST['droptablego']!=null){
													if(!$link=@mysqli_connect($server,$username,$password,$_POST['database'])) showlogin("Error ".mysqli_connect_errno().": ".mysqli_connect_error());
													mysqli_set_charset($link, "utf8");
													$sql="DROP TABLE ".mysqli_real_escape_string($link,$_POST['table']);
													if(!$dropqry=mysqli_query($link,$sql)) $popuptext[]="ERROR ".mysqli_errno($link).': '.mysqli_error($link);
													else $popuptext[]="Table '".mysqli_real_escape_string($link,$_POST['table'])."' was dropped";
													$refresh=true;
												}else{
													
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	if(isset($sql) && $sql!=""){
		$GLOBALS['sqltext']=$sql;
		if($debug){
			echo "<!-- SQL: ".$sql." -->";
			writelog("sql: ".$sql);
		}
	}
	if($debug) writelog("showing logo");
?>
<!DOCTYPE html>
<!-- <?php echo "Page last updated: ".date("d F, Y H:i:s", filemtime(__FILE__)); ?>
                                                                                                    
                                                                                                    
                                           ...,,,,,,,,...                                           
                            .:+=    .,,,,,:::::::::::::::::::,,.                                    
                        .=ii;.  .,,,::::::;;;;;;;==;;;;;;;;::::::::. ,;+;.                          
                      ,ii=.  .,,::::;;==++iittttIIIttttii++==;;;;:::::, :+=                         
                     :iYt .,,,:::;=+ittIYYYYti+=;;;;=+itIIIItii+=;;;;:::::ii                        
                    ,+tX;,,:::;=+tIIYIi;.                  .;ittti+=;;;;;:+I;                       
                    :+IVi::;=+iIYYt:                            :iti+==;;;+Ii.                      
                    :+tVV;=+tIYt:                                  :ii+==;iYi;:.                    
                   .:+tIVYtIYi.                                      .++=+IV+;;;,                   
                  ,,:;itIIVt,                         .....           .;+YXt;;;;=:                  
                 ,:::;+ttIIIY+;,                       .....    ,;;;++iYXXt=;;;;==;                 
                ,:::;+tIItIIIYItti+=i==+=;;=,                    ,ttIXVXt.,==;;;==+;                
               ,:::;+tII. .+ttIYtiIYiiIt+itYI........            .:iIt:    .;;;;;=++:               
              .:::;=iII,     .:=+tIiitI+itIVt,....,,:::,..     ....:+itII,  .;;;;==++.              
              ,::;=+tI;    ;IYVt=:,..:++iIYi:,,:+ii+++++==:..:+iiii=+IYI:    :;;;;=+i;              
              :::;=iIt     .tRBMMMV;:;;=+i+:,:=i++++i,   ,=:;+++..,+ti:      .;;;;=+ii.             
             .:::;+iI=       ;YRMWWWI++++;:::;++====;.IBBYI=;==::MMVV,        ::;;;+it,             
             ,::;;+iI: ........,=tYYti+;;;;::;===;;;+=###BMi:::;:B#Wt=.       ,::;;+it;             
             ,::;;+it:.......,,;+iiii=;;;;;;;;;;;::::;iXRRi;;;;::;;;;;:.      ,::;;+it;             
             ,::;;+it+tt=,,,,;+==+=====;;;;;;;;;;;:,,,,:;=;;;;;;;;;;;;::,,;+i=;;:;;+it;             
             ,::;;=+iVWW#Wt,=i+++============;;;;;;;=tVXXXXXXXVYI+=;;=tYVYIt+=iYVt=+tI:             
             .:;;;=+itM###M=ii++++++++============iYRXXXXVVVVYYYYVVVVVVVYIIIIIYYVXXttI.             
              ::;;;=+iX###I+iii++++II++++++====+itRRXXXVVVYYYYIIIIIIIIIIItttttIYYVXRIi              
              .;;;;;=+iIi;=iiii+++B###I+++++++iiIRXXXVVVVYYYYVYItttiiiiiiiiiiiR#MVXRR,              
               :;;;;;===;;+iiiii++R###Mi+++++iitRXXXVVVYYYVW##MBXtii+++++++++iW#WRVRRi              
                ;;;;;;====iiiiiii+iR##R+++++iiiVRXXXVVVYYYW####MBVi++++++++++iM#WRVRBY              
                .;;;;;;;;=itttiiii+++i++++++iiiRXXXVVVVYYVW####MBXti++++++iiitYRRVXRBY              
                 .===;;;;;;iIttttiiii+++++++iiiXRXXVVVVYYYR###WMRYtiiiiitttttIIYVXRBBi              
                   ;===;;;;;;iIIItttiii+++++iiiVRXXXVVVVYYYVRRRXYtttttIIYYYYYVVXRBBMt               
                    :++==;;;;;;tYYIIItttiiiiiiitRRXXXVVVVYYYYIIIIIIIIYVVXXXXRRBBMBY.                
                     .=++==;;;;::=tYYYYYYIIIIIIIYRRRRXXXVVVVVYYYYYVVVXRMMMMMMY;,                    
                       ,+i++==;;;:::;=iYVVVVXXXItVRBBBRRRXXXXXXXRRRBWWWWMMMMi                       
                         .+ii++==;;;:::::::::,,....,+VBBBBBBBBBBBBMMMMMMBY;                         
                            :itti++==;;;:::::::::::::::;=ittti+=+itIYVI:                            
                               :itttii+++==;;;;;;;;;;;;;===+iitIIYYt:                               
                                  .;iIIIIttttiiiiiiiittttIIYYYYt:                                   
                                        ,:=iIIIYYYYYYYIIi=:,                                        
                                                                                                    
                                 (c) http://www.DairyWindow.com 2014                                
                                        dairywindow@gmail.com                                       
                                                                                                    
               List Collapse script provided for free by http://www.howtocreate.co.uk               
                          Customers are not charged for use of this script.                         
                                                                                                    
-->
<html>
<head>
	<script type="text/javascript">
		var before=(new Date()).getTime();
	</script>
	<title>MySQL<?php echo " - ".str_replace('www.','',$_SERVER['SERVER_NAME']); ?></title>
	<script type="text/javascript" src="./js/common.js"></script>
	<script type="text/javascript" src="./js/listcollapse.js"></script>
	<script type="text/javascript" src="./js/cookie.js"></script>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<style>
		body{
			font-family:Verdana, Geneva, Tahoma, sans-serif;
			overflow:hidden;
		}
		a{
			text-decoration:none;
			color:black;
		}
		a:hover{
			text-decoration:underline;
			color:blue;
		}
		img{
			vertical-align:middle;
			border:0px;
		}
		form{
			display:inline-block;
		}
		hr{
			vertical-align:top;
			margin:0px;
		}
		select, option {
			text-align:center;
		}
		.logincontainer{
			position:absolute;
			top:0px;
			height:<?php echo ($GLOBALS['viewportheight']); ?>px;
			width:99%;
			display:block;
			background-color:white;
			vertical-align:middle;
			overflow:hidden;
		}
		.login{
			position:absolute;
			text-align:center;
			top:50%;
			height:<?php echo ($GLOBALS['viewportheight']); ?>px;
			margin-top:-<?php echo ($GLOBALS['viewportheight']/2); ?>px; /* negative half of the height */
			vertical-align:middle;
			margin:auto;
			width:99%;
			z-index:1;
			display:block;
			background-color:white;
			overflow:hidden;
		}
		.logout{
			position:absolute;
			right:10px;
			top:10px;
			width:100px;
			margin:auto;
			z-index:1;
			text-align:right;
		}
		.sqltext{
			top:<?php echo ($GLOBALS['viewportheight']-250); ?>px;
			left:10px;
			width:245px;
			height:245px;
			overflow:hidden;
			position:absolute;
			margin:auto;
			background-color:Lavender;
			text-align:center;
		}
		.operations{
			width:<?php echo ($GLOBALS['viewportwidth']-265); ?>px;
			overflow:hidden;
			position:absolute;
			top:5px;
			left:260px;
			height:30px;
			margin:auto;
			background-color:pink;
		}
		.main{
			width:<?php echo ($GLOBALS['viewportwidth']-265); ?>px;
			overflow:hidden;
			position:absolute;
			top:5px;
			left:260px;
			height:<?php echo ($GLOBALS['viewportheight']-10); ?>px;
			margin:auto;
			background-color:skyblue;
		}
		.alterdatabase{
			width:<?php echo ($GLOBALS['viewportwidth']-265); ?>px;
			overflow:auto;
			position:absolute;
			top:40px;
			left:260px;
			height:<?php echo ($GLOBALS['viewportheight']-50); ?>px;
			margin:auto;
			background-color:SkyBlue;
		}
		.altertable{
			width:<?php echo ($GLOBALS['viewportwidth']-265); ?>px;
			overflow:auto;
			position:absolute;
			top:40px;
			left:260px;
			height:<?php echo ($GLOBALS['viewportheight']-60)/2; ?>px;
			margin:auto;
			background-color:SkyBlue;
		}
		.results{
			width:<?php echo ($GLOBALS['viewportwidth']-265); ?>px;
			overflow:auto;
			position:absolute;
			top:<?php echo (($GLOBALS['viewportheight']-60)/2)+50; ?>px;
			left:260px;
			height:<?php echo ($GLOBALS['viewportheight']-60)/2; ?>px;
			margin:auto;
			background-color:LightGreen;
		}
		div.menu { width:250px; height:<?php echo ($GLOBALS['viewportheight']-260); ?>px; display:inline-block; overflow:scroll; background-color:#e6e6e6; }
		#someID { border-left:8px; border-right:4px; margin-bottom:0px; }
		div.menu ul ul { margin-left:10px; }
		div.menu ul, div.menu li { padding:0px; margin:0px; list-style-type:none; font-weight:bold; }
		div.menu li a { margin-left:0px; padding:3px; border-top:2px; text-decoration:none; width:100%; height:100%; font-weight:normal; display:inline-block; color:black; }
		div.menu li { display:inline; white-space:nowrap; overflow:hidden; font-weight:normal; } /* fix for IE blank line bug */
		div.menu ul > li { display:list-item; }
		div.menu li > a { width:auto; height:auto; color:black; }
		div.menu li li a { color:black; }
		div.menu li li li a { color:black; }
		div.menu li a:hover { color:blue; text-decoration:underline; }
		div.menu li li a:hover { color:blue; }
		div.menu li li li a:hover { color:blue; }
		div.menu li a.samePage { color:black; font-weight:bold; }
		
		.login{
			position:absolute;
			text-align:center;
			top:50%;
			height:<?php echo ($GLOBALS['viewportheight']-100); ?>px;
			margin-top:-<?php echo ($GLOBALS['viewportheight']-100/2); ?>px; /* negative half of the height */
			vertical-align:middle;
			margin:auto;
			width:99%;
			z-index:1;
			display:block;
			background-color:white;
			overflow:hidden;
		}
		
		.input-text{
			margin:0px;
			border:#ccc solid 1px;
			padding:0px;
			background:transparent;
			text-align:center;
			width:150px;
		}
		.tr-white{
			background-color:PapayaWhip;
			white-space:nowrap;
			margin:0px;
			padding:0px;
			height:28px;
			max-height:28px;
			vertical-align:middle;
			width:<?php echo $_COOKIE['viewportwidth']-250; ?>px;
		}
		.tr-green{
			background-color:LightGreen;
			white-space:nowrap; margin:0px;
			padding:0px;
			height:28px;
			max-height:28px;
			vertical-align:middle;
			width:<?php echo $_COOKIE['viewportwidth']-250; ?>px;
		}
		.tr-blue {
			background-color:SkyBlue;
			white-space:nowrap;
			margin:0px;
			padding:0px;
			height:28px;
			max-height:28px;
			vertical-align:middle;
			width:<?php echo $_COOKIE['viewportwidth']-250; ?>px;
		}
		.tr-results-white{
			background-color:PapayaWhip;
			white-space:nowrap;
			margin:0px;
			padding:0px;
			height:28px;
			max-height:28px;
			vertical-align:middle;
			width:<?php if(isset($_COOKIE['resultwidth']) && $_COOKIE['resultwidth']!="" && $_COOKIE['resultwidth']!=null && $_COOKIE['resultwidth']>0) echo $_COOKIE['resultwidth']; else echo "2000"; ?>px;
		}
		.tr-results-green{
			background-color:LightGreen;
			white-space:nowrap;
			margin:0px;
			padding:0px;
			height:28px;
			max-height:28px;
			vertical-align:middle;
			width:<?php if(isset($_COOKIE['resultwidth']) && $_COOKIE['resultwidth']!="" && $_COOKIE['resultwidth']!=null && $_COOKIE['resultwidth']>0) echo $_COOKIE['resultwidth']; else echo "2000"; ?>px;
		}
		.table-center{
			background:transparent;
			text-align:center;
			white-space:nowrap;
			width:150px;
			display:inline-block;
			overflow:hidden;
			vertical-align:middle;
		}
		.table-left  {
			background:transparent;
			text-align:left;
			white-space:nowrap;
			width:150px;
			display:inline-block;
			overflow:hidden;
			vertical-align:middle;
		}
		.table-head-center{
			background:transparent;
			text-align:center;
			font-weight:bold;
			white-space:nowrap;
			width:150px;
			display:inline-block;
			overflow:hidden;
			vertical-align:middle;
		}
		.table-head-center a{
			text-decoration:none;
			color:black;
		}
		.table-head-center a:hover{
			text-decoration:underline;
			color:blue;
		}
		.table-head-left{
			background:transparent;
			text-align:left;
			font-weight:bold;
			white-space:nowrap;
			width:150px;
			display:inline-block;
			overflow:hidden;
			vertical-align:middle;
		}
		.popuptext{
			top:<?php echo ($GLOBALS['viewportheight']/2)-10; ?>px;
			left:<?php echo ($GLOBALS['viewportwidth']/2)-200; ?>px;
			padding:5px;
			margin:10px;
			display:inline-block;
			position:absolute;
			background-color:PaleGoldenRod;
			text-align:center;
			box-shadow:5px 5px 5px black;
		}
		.popuptexterror{
			top:<?php echo ($GLOBALS['viewportheight']/2)-10; ?>px;
			left:<?php echo ($GLOBALS['viewportwidth']/2)-200; ?>px;
			padding:5px;
			display:inline-block;
			position:absolute;
			background-color:Red;
			text-align:center;
			box-shadow:5px 5px 5px black;
		}
		
		.link{ text-decoration:none; color:black; }
		.link :hover{ text-decoration:underline; color:blue; cursor:pointer; }
		.greenbutton{
			background-color:green;
			padding:1px;
			display:inline-block;
			margin:0px;
			text-align:center;
		}
		.redbutton{
			background-color:red;
			padding:1px;
			display:inline-block;
			margin:0px;
			text-align:center;
		}
	</style>
	<link rel="shortcut icon" href="favicon.ico" />
</head>
<body onresize="redrawscreen();">
<div class='logout'><a href='?logout=true'>Logout</a></div>
<div class='popuptext' id='dropquestion' style='z-index:1;display:none;' > </div>
<?php
	if(isset($sql) && $sql!="" && $sql!=null) $currentsql=$sql;
	//if($debug) writelog("sql: ".$sql);
	if(isset($popuptext) && count($popuptext)>0){
		$msgnum=0;
		foreach($popuptext as $text){
			if(strtoupper(substr($text,0,5))=="ERROR") $class='popuptexterror';
			else $class='popuptext';
			
			echo "<div class='".$class."' id='popuptext".$msgnum."' style='z-index:".(100-$msgnum).";' >".$text."<br></div>";
			if($debug) writelog("text: ".$text);
			$msgnum++;
		}
	}
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\/
	echo "<div class='menu'>";
	echo str_replace('www.','',$_SERVER['SERVER_NAME'])."<br>";
	foreach($popuptext as $message){
		if(strtolower(substr($message,0,5))=="error") echo "<a href='http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html' targer='_blank'><span style='color:red;font-weight:bold;'>".$message."</span></a><br>";
		if($debug) writelog("message: ".$message);
	}
	//echo "<span style='color:red;font-weight:bold;'>Red bold text</span><br>";
	
	$file='menu/Delete me if you want ['.$server."-".$username.'].htm';
	if($debug) writelog("file: ".$file);
	if(!file_exists($file) || $refresh){ //create menu
		if($debug) writelog("file does not exist");
		if(file_exists($file)) @unlink($file); //try to delete file, if it doesn't work, no matter. will get overwritten anyway
		file_put_contents($file, "<ul id='someID'>", FILE_APPEND);
		
		$exclude=array(
			"information_schema",
			"performance_schema",
			"mysql"
		);
		
		if(!$link=@mysqli_connect($server,$username,$password)){
			showlogin('Error '.mysqli_connect_errno().': '.mysqli_connect_error());
		}else{
			mysqli_set_charset($link, "utf8");
			$sql="SHOW DATABASES";
			if($debug) writelog("sql: ".$sql);
			if(!$databaseqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
			else{
				if(count($databaseqry)>0){
					if($debug) writelog("databaseqry count: ".count($databaseqry));
					while($databasearray=mysqli_fetch_array($databaseqry)){
						$database=$databasearray[0];
						if(!in_array($database, $exclude)){ //only show user created databases
							if($debug) writelog("database: ".$database);
							file_put_contents($file, " <li><a href='?database=".$database."' >".$database."</a>", FILE_APPEND);
							if(!$link=@mysqli_connect($server,$username,$password,$database)){
								mysqli_set_charset($link, "utf8");
								file_put_contents($file, "Error ".mysqli_connect_errno().": ".mysqli_connect_error()."", FILE_APPEND);
							}else{
								if($debug) writelog("connected to: ".$database);
								$sql="SHOW TABLES IN ".$database;
								if($debug) writelog("sql: ".$sql);
								if(!$tableqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
								else{
									if(count($tableqry)>0){
										file_put_contents($file, "  <ul>", FILE_APPEND);
										while($tablearray=mysqli_fetch_array($tableqry)){
											$table=$tablearray[0];
											
											$sqli="SELECT COUNT(*) FROM ".$table;
											if($debug) writelog("sqli: ".$sqli);
											$countqry=mysqli_query($link,$sqli);
											$countarray=mysqli_fetch_array($countqry);
											if($debug) writelog("rows: ".$countarray[0]);
											mysqli_free_result($countqry);
											
											file_put_contents($file, "   <li><a href='?database=".$database."&table=".$table."'>".$table." (".$countarray[0]." rows)</a>", FILE_APPEND);
											$sql="SHOW COLUMNS FROM ".$table." IN ".$database;
											if($debug) writelog("sql: ".$sql);
											if(!$columnqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
											else{
												if(count($columnqry)>0){
													file_put_contents($file, "  <ul>", FILE_APPEND);
													while($columnarray=mysqli_fetch_array($columnqry)){
														$column=$columnarray[0];
														file_put_contents($file, "  <li>", FILE_APPEND);
														//$sql="SELECT COLUMN_TYPE, COLUMN_KEY, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME='".$table."' AND COLUMN_NAME='".$column."'"; //this is the bit that makes the page take ages to load
														$sql="SHOW FIELDS FROM ".$table." WHERE Field='".$column."'";
														//if($debug) writelog("sql: ".$sql);
														
														if(!$coltypeqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
														else{
															set_time_limit(0);
															$coltypearray=mysqli_fetch_array($coltypeqry);
															$coltype=$coltypearray['Type'];
															//if($debug) writelog("coltype: ".$coltype);
															if(strtoupper($coltypearray['Key'])=="PRI") $colkey=true;
															else $colkey=false;
															mysqli_free_result($coltypeqry);
															file_put_contents($file, "<span title='".$coltype."'>", FILE_APPEND);
															$datatype=explode('(',$coltype);
															switch(strtolower($datatype[0])){
																case "bit":
																case "tinyint":
																case "smallint":
																case "mediumint":
																case "int":
																case "integer":
																case "bigint":
																case "decimal":
																case "dec":
																case "float":
																case "double":
																	file_put_contents($file, "<img src='images/number.png' title='Number' />", FILE_APPEND);
																	break;
																case "binary":
																case "varbinary":
																	file_put_contents($file, "<img src='images/text.png' title='Text' />", FILE_APPEND);
																	break;
																case "char":
																case "varchar":
																case "text":
																case "tinytext":
																case "fulltext":
																case "mediumtext":
																case "longtext":
																case "tinyblob":
																case "blob":
																case "mediumblob":
																case "longblob":
																	file_put_contents($file, "<img src='images/textsearch.png' title='Searchable text' />", FILE_APPEND);
																	break;
																case "date":
																case "time":
																case "datetime":
																case "timestamp":
																case "year":
																	file_put_contents($file, "<img src='images/date.png' title='Date' />", FILE_APPEND);
																	break;
																default:
																	file_put_contents($file, "<img src='images/unknown.png' title='Unknown' />", FILE_APPEND);
															}
														}
														file_put_contents($file, "&nbsp;".$column, FILE_APPEND);
														if($colkey){
															file_put_contents($file, "<img src='images/key.png' title='Key' />", FILE_APPEND);
														}
														file_put_contents($file, "</span></li>", FILE_APPEND);
													}
													mysqli_free_result($columnqry);
													file_put_contents($file, "</ul>", FILE_APPEND);
												}else{
													file_put_contents($file, "<ul><li>No columns in ".$table."</li></ul>", FILE_APPEND);
													if($debug) writelog("no cols in ".$table);
												}
											}
											file_put_contents($file, "</li>", FILE_APPEND);
										}
										mysqli_free_result($tableqry);
										file_put_contents($file, "</ul>", FILE_APPEND);
									}else{
										file_put_contents($file, "<ul><li>No tables in ".$database."</li></ul>", FILE_APPEND);
										if($debug) writelog("no tables in ".$database);
									}
								}
							}
							file_put_contents($file, "</li>", FILE_APPEND);
						}
					}
					mysqli_free_result($databaseqry);
				}else{
					file_put_contents($file, "No databases in ".$server."<br>", FILE_APPEND);
					if($debug) writelog("no databases in ".$server);
				}
			}
		}
		file_put_contents($file, "</ul>", FILE_APPEND);
		if($debug) writelog("menu written to: ".$file);
	}
	
	if($debug) writelog("file exists. reading ".$file);
	$filecontents=file_get_contents($file);
	echo $filecontents;

	if($debug) writelog("menu done");
	
	echo "<span onclick='showloading();' class='link'>
		<form method='get' id='loadingform'>";
	foreach($_GET as $key => $value) if($key!="refresh") echo "<input type='hidden' name='$key' value='$value' />";
	echo "<input type='hidden' name='refresh' value='true' />
			<img src='images/refresh.png' />&nbsp;Refresh list
		</form>
	</span>";
	//if(isset($currentsql) && $currentsql!="" && $currentsql!=null) echo "<div style='font-family:courier,monospace;'><br>".$currentsql."</div>";
	
	if($debug){
		if(isset($_GET) && $_GET!="" && $_GET!=null){
			echo "<pre>GET:";
			print_r($_GET);
			echo "</pre>";
		}
		if(isset($_POST) && $_POST!="" && $_POST!=null){
			echo "<pre>POST:";
			print_r($_POST);
			echo "</pre>";
		}
		if(isset($_COOKIE) && $_COOKIE!="" && $_COOKIE!=null){
			echo "<pre>COOKIE:";
			print_r($_COOKIE);
			echo "</pre>";
		}
		if(isset($popuptext) && $popuptext!="" && $popuptext!=null){
			echo "<pre>popuptext:";
			print_r($popuptext);
			echo "</pre>";
		}
	}
	echo "
		</div>
		<script type=\"text/javascript\">
			selfLink('someID','samePage',true);
		</script>
	";
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\/
	if(!isset($GLOBALS['sqltext']) || $GLOBALS['sqltext']=="" || $GLOBALS['sqltext']==null){
		if(!isset($_POST['customsql']) || $_POST['customsql']=="" || $_POST['customsql']==null){
			if(isset($_GET['database']) && $_GET['database']!="" && $_GET['database']!=null){
				if(isset($_GET['table']) && $_GET['table']!="" && $_GET['table']!=null) $GLOBALS['sqltext']="SELECT * FROM ".$_GET['database'].'.'.$_GET['table']; //both database and table are set
				else $GLOBALS['sqltext']="SHOW TABLES IN ".$_GET['database']; //database is set, table is not set
			}else $GLOBALS['sqltext']=""; //database is not set
		}else $GLOBALS['sqltext']=$_POST['customsql'];
	}
	
	if(isset($_GET['database']) && $_GET['database']!="" && $_GET['database']!=null) $database=$_GET['database'];
	else $database="";
	
	$GLOBALS['sqltext']=preg_replace("/(\r|\n|\r|\s|\t)/", " ", $GLOBALS['sqltext']);
	echo "<div class='sqltext'>
		<form method='post'>
			<input type='hidden' name='database' value='".$database."' />
			<textarea rows='13' cols='27' name='customsql' id='customsql' style='background-color:Lavender;width:238px;height:212px;font-family:monospace;'>".$GLOBALS['sqltext']."</textarea>
			<br/><input type='submit' name='sqlsubmit' />
		</form>
	</div>";
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\/
	if(isset($GLOBALS['customqry']) && isset($_POST['sqlsubmit']) && isset($_POST['customsql']) && $_POST['customsql']!="" && $_POST['customsql']!=null){
		echo "<div class='results' id='results' style='top:10px;width:".($_COOKIE['viewportwidth']-270)."px;height:".($_COOKIE['viewportheight']-15)."px;'>";
		if(isset($_GET['database']) && $_GET['database']!="" && $_GET['database']!=null){
			$database=$_GET['database'];
			if(!$link=@mysqli_connect($server,$username,$password,$database)) $popuptext[]='Error '.mysqli_connect_errno().': '.mysqli_connect_error();
			else{
				mysqli_set_charset($link, "utf8");
				if(strtolower(substr($_POST['customsql'],0,6))=="select" || strtolower(substr($_POST['customsql'],0,4))=="show"){
					$rows=0;
					$rows=@mysqli_num_rows($GLOBALS['customqry']);
					echo "<span style='display:inline-block;height:28px;vertical-align:middle;' id='rowsreturned'>".$rows;
					if($rows==1) echo " row "; else echo " rows ";
					echo "returned</span><br/>";
					//http://www.anyexample.com/programming/php/php_mysql_example__display_table_as_html.xml
					echo "<table border='0' cellspacing='0' cellpadding='5' >";
					$colour="white";
					// printing table headers
					
					$_POST['customsql']=preg_replace("/(\r||\r|\s+|\t+)/", " ", $_POST['customsql']); //removes all whitespace. This is so the table can have headings
					
					$fresult=mysqli_query($link, mysqli_real_escape_string($link,str_replace($remove,'',$_POST['customsql'])));
					/* Get field information for all fields */
					if(!is_bool($fresult)){
						echo "	<tr class='tr-$colour'>";
						while ($finfo=mysqli_fetch_field($fresult)) echo "<th><span style='white-space:nowrap;'>".$finfo->name."</span></th>";
						echo "	</tr>";
					}
					//mysqli_free_result($fresult);
					$colour="green";
					
					// printing table rows
					while($frow=@mysqli_fetch_row($GLOBALS['customqry'])){
						echo "	<tr class='tr-$colour' >";
						// $row is array... foreach( .. ) puts every element
						// of $row to $cell variable
						foreach($frow as $cell){
							if($cell==null || $cell=="") echo "		<td class='tr-$colour' style='font-style:italic;text-align:center;color:grey;'>Null</td>";
							else{
								if(strlen($cell)>100) echo "		<td class='tr-$colour' title='".$cell."' style='white-space:nowrap;text-align:center;'>".substr($cell,0,100)."&hellip;</td>";
								else echo "		<td class='tr-$colour' style='white-space:nowrap;text-align:center;'>".$cell."</td>";
							}
						}
						echo "	</tr>";
						$colour=($colour == "white" ? "green" : "white");
					}
					echo "</table>";
					if($colour!="green") echo "<hr/>";
				}else{
					if($GLOBALS['affected']<0) echo "<span class='tick-text'>There was an error running the query.</span><br/>";
					else echo "<span class='tick-text'>The query completed successfully. ".$GLOBALS['affected']." row(s) were affected.</span><br/>";
				}
			}
		}else echo "No database selected. Choose one from the list on the left";
		echo "</div>";
	}else{	
		if(isset($_GET['database']) && $_GET['database']!="" && $_GET['database']!=null && isset($_GET['table']) && $_GET['table']!="" && $_GET['table']!=null){
			$database=$_GET['database'];
			if(!$link=@mysqli_connect($server,$username,$password,$database)) $popuptext[]='Error '.mysqli_connect_errno().': '.mysqli_connect_error();
			else{
				mysqli_set_charset($link, "utf8");
				$table=mysqli_real_escape_string($link,$_GET['table']);
				$sql="SELECT ENGINE, TABLE_COLLATION FROM information_schema.tables WHERE TABLE_NAME='".$table."' AND TABLE_SCHEMA='".$database."'";
				if(!$tableqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
				else{
					if(mysqli_num_rows($tableqry)>0){
						echo "<div class='operations'>
								<form method='post' id='tableop'>
									<input type='hidden' name='oldtable' value='".$table."'/>
									Table name: <input type='text' name='newtable' value='".$table."' required />";
						$tablearray=mysqli_fetch_array($tableqry);
						mysqli_free_result($tableqry);
						echo "<input type='hidden' name='oldengine' value='".$tablearray['ENGINE']."'/>
								<a href='http://dev.mysql.com/doc/refman/5.0/en/storage-engines.html' target='_blank'>Engine:</a>&nbsp;<select name='newengine'>";
						$sql="SHOW ENGINES";
						if(!$engineqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
						else{
							while($enginearray=mysqli_fetch_array($engineqry)){
								echo "<option value='".$enginearray['Engine']."' ";
								if(strtoupper($enginearray['Engine'])==strtoupper($tablearray['ENGINE'])) echo "selected";
								echo ">".$enginearray['Engine']."</option>";
							}
							mysqli_free_result($engineqry);
							echo "</select>
								<input type='hidden' name='oldcollation' value='".$tablearray['TABLE_COLLATION']."'/>
								Collation: <select name='newcollation'>";
							$sql="SHOW COLLATION";
							if(!$collationqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
							else{
								while($collationarray=mysqli_fetch_array($collationqry)){
									echo "<option value='".$collationarray['Collation']."' ";
									if(strtoupper($collationarray['Collation'])==strtoupper($tablearray['TABLE_COLLATION'])) echo "selected";
									echo ">".$collationarray['Collation']."</option>";
								}
								mysqli_free_result($collationqry);
								echo "</select>";
							}
						}
						
						$sql="SHOW TABLE STATUS WHERE Name='".$table."'";
						if(!$engineqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
						else{
							$statusarray=mysqli_fetch_array($engineqry);
							echo "<input type='hidden' name='oldautoincrement' value='".$statusarray['Auto_increment']."' />Auto increment: <input type='text' name='autoincrement' value='".$statusarray['Auto_increment']."' style='width:80px;' />";
							mysqli_free_result($engineqry);
						}
						
						echo "<input type='hidden' name='database' value='$database'/>
								<span class='greenbutton'>
									<input type='submit' name='altertablego' value='Save changes' />
								</span>
							</form>
							<form method='post' class='redbutton'>
								<input type='hidden' name='database' value='$database'/>
								<input type='hidden' name='table' value='$table'/>
								<input type='submit' name='droptablego' value='Drop table'/>
							</form>
							</div>";
					
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\/
						echo "<div class='altertable'>";
						$sql="SHOW COLUMNS FROM ".$table;
						if(!$alterqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
						else{
							echo "<div class='tr-blue'>
									<span class='table-head-center' style='width:300px;'>Field name</span>
									<span class='table-head-center'><a href='http://dev.mysql.com/doc/refman/5.0/en/data-types.html' target='_blank'>Type</a></span>
									<span class='table-head-center'>Length</span>
									<span class='table-head-center'>Signed</span>
									<span class='table-head-center'>Allow null</span>
									<span class='table-head-center'>Key</span>
									<span class='table-head-center'>Default</span>
									<span class='table-head-center'>Extra</span>
								</div>";
							$colour="white";
							$default=Array();
							$colnum=0;
							while($alterarray=mysqli_fetch_array($alterqry)){
								$type=explode('(',$alterarray['Type']);
								if(isset($sign[1]) && $sign[1]!="" && $sign[1]!=null) $signed=ucfirst(strtolower(trim($sign[1])));
								else $signed="";
								echo "<div class='tr-$colour'>
										<form method='post' id='updatecolform".$colnum."' >
											<span class='table-center' style='width:300px;' section='fieldname'>
												<input type='hidden' name='database' value='$database' />
												<input type='hidden' name='table' value='$table' />
												<input type='hidden' name='oldcolumn' value='".$alterarray['Field']."' />
												<input type='text' class='input-text' name='newcolumn' value='".$alterarray['Field']."' style='width:300px;' />
											</span>
											<span class='table-center' section='type'>
												<input type='hidden' name='olddatatype' value='".$type[0]."' />
												<select name='newdatatype' id='newdatatype' class='input-text' >";
													echo "<option value='int'"; if(strtolower($type[0])=="int") echo " selected"; echo ">Int</option>";
													echo "<option value='varchar'"; if(strtolower($type[0])=="varchar") echo " selected"; echo ">Varchar</option>";
													echo "<option value='decimal'"; if(strtolower($type[0])=="decimal") echo " selected"; echo ">Decimal</option>";
													echo "<option value='datetime'"; if(strtolower($type[0])=="datetime") echo " selected"; echo ">Datetime</option>";
													echo "<optgroup label='Blobs'>";
													echo "	<option value='binary'"; if(strtolower($type[0])=="binary") echo " selected"; echo ">Binary</option>";
													echo "	<option value='blob'"; if(strtolower($type[0])=="blob") echo " selected"; echo ">Blob</option>";
													echo "	<option value='tinyblob'"; if(strtolower($type[0])=="tinyblob") echo " selected"; echo ">Tinyblob</option>";
													echo "	<option value='mediumblob'"; if(strtolower($type[0])=="mediumblob") echo " selected"; echo ">Mediumblob</option>";
													echo "	<option value='longblob'"; if(strtolower($type[0])=="longblob") echo " selected"; echo ">Longblob</option>";
													echo "	<option value='varbinary'"; if(strtolower($type[0])=="varbinary") echo " selected"; echo ">Varbinary</option>";
													echo "</optgroup><optgroup label='Dates'>";
													echo "	<option value='date'"; if(strtolower($type[0])=="date") echo " selected"; echo ">Date</option>";
													echo "	<option value='datetime'>Datetime</option>";
													echo "	<option value='time'"; if(strtolower($type[0])=="time") echo " selected"; echo ">Time</option>";
													echo "	<option value='timestamp'"; if(strtolower($type[0])=="timestamp") echo " selected"; echo ">Timestamp</option>";
													echo "	<option value='year'"; if(strtolower($type[0])=="year") echo " selected"; echo ">Year</option>";
													echo "</optgroup><optgroup label='Numbers'>";
													echo "	<option value='bit'"; if(strtolower($type[0])=="bit") echo " selected"; echo ">Bit</option>";
													echo "	<option value='int'>Int</option>";
													echo "	<option value='tinyint'"; if(strtolower($type[0])=="tinyint") echo " selected"; echo ">Tinyint</option>";
													echo "	<option value='smallint'"; if(strtolower($type[0])=="smallint") echo " selected"; echo ">Smallint</option>";
													echo "	<option value='mediumint'"; if(strtolower($type[0])=="mediumint") echo " selected"; echo ">Mediumint</option>";
													echo "	<option value='bigint'"; if(strtolower($type[0])=="bigint") echo " selected"; echo ">Bigint</option>";
													echo "	<option value='decimal'>Decimal</option>";
													echo "	<option value='double'"; if(strtolower($type[0])=="double") echo " selected"; echo ">Double</option>";
													echo "	<option value='float'"; if(strtolower($type[0])=="float") echo " selected"; echo ">Float</option>";
													echo "</optgroup><optgroup label='Text'>";
													echo "	<option value='char'"; if(strtolower($type[0])=="char") echo " selected"; echo ">Char</option>";
													echo "	<option value='varchar'>Varchar</option>";
													echo "	<option value='text'"; if(strtolower($type[0])=="text") echo " selected"; echo ">Text</option>";
													echo "	<option value='tinytext'"; if(strtolower($type[0])=="tinytext") echo " selected"; echo ">Tinytext</option>";
													echo "	<option value='mediumtext'"; if(strtolower($type[0])=="mediumtext") echo " selected"; echo ">Mediumtext</option>";
													echo "	<option value='longtext'"; if(strtolower($type[0])=="longtext") echo " selected"; echo ">Longtext</option>";
													echo "	<option value='set'"; if(strtolower($type[0])=="set") echo " selected"; echo ">Set</option>";
													echo "	<option value='enum'"; if(strtolower($type[0])=="enum") echo " selected"; echo ">Enum</option>";
													echo "</optgroup><optgroup label='Shapes'>";
													echo "	<option value='curve'"; if(strtolower($type[0])=="curve") echo " selected"; echo ">Curve</option>";
													echo "	<option value='geometry'"; if(strtolower($type[0])=="geometry") echo " selected"; echo ">Geometry</option>";
													echo "	<option value='geometrycollection'"; if(strtolower($type[0])=="geometrycollection") echo " selected"; echo ">Geometrycollection</option>";
													echo "	<option value='point'"; if(strtolower($type[0])=="point") echo " selected"; echo ">Point</option>";
													echo "	<option value='line'"; if(strtolower($type[0])=="line") echo " selected"; echo ">Line</option>";
													echo "	<option value='linearring'"; if(strtolower($type[0])=="linearring") echo " selected"; echo ">Linearring</option>";
													echo "	<option value='linestring'"; if(strtolower($type[0])=="linestring") echo " selected"; echo ">Linestring</option>";
													echo "	<option value='multicurve'"; if(strtolower($type[0])=="multicurve") echo " selected"; echo ">Multicurve</option>";
													echo "	<option value='multilinestring'"; if(strtolower($type[0])=="multilinestring") echo " selected"; echo ">Multilinestring</option>";
													echo "	<option value='multipoint'"; if(strtolower($type[0])=="multipoint") echo " selected"; echo ">Multipoint</option>";
													echo "	<option value='multipolygon'"; if(strtolower($type[0])=="multipolygon") echo " selected"; echo ">Multipolygon</option>";
													echo "	<option value='multisurface'"; if(strtolower($type[0])=="multisurface") echo " selected"; echo ">Multisurface</option>";
													echo "	<option value='polygon'"; if(strtolower($type[0])=="polygon") echo " selected"; echo ">Polygon</option>";
													echo "	<option value='surface'"; if(strtolower($type[0])=="surface") echo " selected"; echo ">Surface</option>";
													echo "</optgroup>
												</select>
											</span>
											<span class='table-center' section='length'>
												<input type='text' class='input-text' name='newcollength' id='newcollength' value='";
								if(isset($type[1]) && $type[1]!="" && $type[1]!=null){
									$sign=explode(')',$type[1]);
									$collength=ucfirst(strtolower($sign[0]));
									echo $collength;
								}else $collength="";
								echo "' />
											<input type='hidden' name='oldcollength' value='".$collength."' />
										</span>
										<span class='table-center' section='signed'>
											<select name='signed' id='signed' class='input-text' >
												<option value='signed'>&nbsp;</option>
												<option value='signed'>Signed</option>
												<option value='unsigned' "; if($signed=="Unsigned") echo "selected "; echo ">Unsigned</option>
											</select>
										</span>
										<span class='table-center' section='allownull'>
											<input type='hidden' name='oldallownull' value='";
								$allownull=ucfirst(strtolower($alterarray['Null']));
								if($allownull=="Yes"){
									echo "Yes";
									$requiredtext[$alterarray['Field']]="";
								}else{
									echo "No";
									$requiredtext[$alterarray['Field']]="required";
								}
								echo "' />
										<select name='newallownull' id='allownull' class='input-text' >
											<option value='Yes' "; if($allownull=="Yes") echo "selected "; echo ">Yes</option>
											<option value='No' "; if($allownull=="No") echo "selected "; echo ">No</option>
										</select>
									</span>&nbsp;";
								$key=ucfirst(strtolower($alterarray['Key']));
								if($key=="Pri") $key="Primary";
								if($key=="Uni") $key="Unique";
								echo "<span class='table-center' section='key'>
										<input type='hidden' name='oldkey' value='$key' />
										<select name='newkey' id='newkey' class='input-text' >
											<option value=''>&nbsp;</option>
											<option value='Primary' "; if($key=="Primary") echo "selected "; echo ">Primary</option>
											<option value='Unique' "; if($key=="Unique") echo "selected "; echo ">Unique</option>
										</select>
									</span>
									<span class='table-center'>
										<input type='text' name='default' id='default' class='input-text' value='".$alterarray['Default']."' />
									</span>&nbsp;";
								$extra=ucfirst(strtolower(str_replace('_',' ',$alterarray['Extra'])));
								echo "<span class='table-center' section='extra'>
												<select name='extra' class='input-text' >
													<option value=''>&nbsp;</option>
													<option value='Auto increment' "; if(strtolower($extra)=="auto increment") echo "selected "; echo ">Auto increment</option>
													<option value='On update current timestamp' "; if($extra=="On update current timestamp") echo "selected "; echo ">On update current timestamp</option>
												</select>
											</span>
											<span class='table-center' section='buttons'>
												<span class='greenbutton'>
													<input type='hidden' name='updatecol' value='true' />
													<input type='button' name='updatecol".$alterarray['Field']."' id='updatecol".$alterarray['Field']."' value='Update column' onclick=\"sendform('updatecolform".$colnum."','updatecol".$alterarray['Field']."');\" />
												</span>
											</span>
										</form>
										<span class='table-left' style='width:96px;max-width:96px;'>
											<form method='post' id='dropcolform".$colnum."'>
												<input type='hidden' name='database' value='$database' />
												<input type='hidden' name='table' value='$table' />
												<input type='hidden' name='column' value='".$alterarray['Field']."' />
												<input type='hidden' name='dropcolumn' value='true' />
												<span class='redbutton'>
													<input type='button' name='dropcol".$alterarray['Field']."' id='dropcol".$alterarray['Field']."' value='Drop column' onclick=\"dropcolq('dropcolform".$colnum."','dropcol".$alterarray['Field']."');\" />
												</span>
											</form>
										</span>
									</div>";
								if($alterarray['Default']!="" && $alterarray['Default']!=NULL) $defaultforresults[$alterarray['Field']]=$alterarray['Default'];
								else $defaultforresults[$alterarray['Field']]="NULL";
								$typeforresults[$alterarray['Field']]=$type[0];
								if(strtolower($extra)=="auto increment") $autoincrement[$alterarray['Field']]="True";
								else $autoincrement[$alterarray['Field']]="False";
								
								$colour=($colour == "white" ? "blue" : "white");
								$colnum++;
							}
							mysqli_free_result($alterqry);
							echo "<div class='tr-$colour'>
									<form method='post' id='inserttablecolumn'>
										<input type='hidden' name='database' value='$database'/>
										<span class='table-center' style='width:300px;'><input type='text' name='fieldname' class='input-text' style='width:100%' placeholder='New column' required /></span>
										<span class='table-center'>
											<script>
												function setdefaultvalue(){
													var coltype=document.getElementById('coltype');
													var collength=document.getElementById('collength');
													if(collength.value == '' || collength.value == Null){
														switch(coltype.value){
															case 'int':
																collength.value='11';
																break;
															case 'float':
															case 'decimal':
															case 'double':
																collength.value='11,1';
																break;
															case 'char':
															case 'varchar':
																collength.value='45';
																break;
														}
													}
												}
											</script>
											<select name='coltype' id='coltype' class='input-text' required onchange='setdefaultvalue();'>
												<option value=''>&nbsp;</option>
												<option value='int'>Int</option>
												<option value='varchar'>Varchar</option>
												<option value='decimal'>Decimal</option>
												<option value='datetime'>Datetime</option>
												<optgroup label='Blobs'>
													<option value='binary'>Binary</option>
													<option value='blob'>Blob</option>
													<option value='tinyblob'>Tinyblob</option>
													<option value='mediumblob'>Mediumblob</option>
													<option value='longblob'>Longblob</option>
													<option value='varbinary'>Varbinary</option>
												</optgroup><optgroup label='Dates'>
													<option value='date'>Date</option>
													<option value='datetime'>Datetime</option>
													<option value='time'>Time</option>
													<option value='timestamp'>Timestamp</option>
													<option value='year'>Year</option>
												</optgroup><optgroup label='Numbers'>
													<option value='bit'>Bit</option>
													<option value='int'>Int</option>
													<option value='tinyint'>Tinyint</option>
													<option value='smallint'>Smallint</option>
													<option value='mediumint'>Mediumint</option>
													<option value='bigint'>Bigint</option>
													<option value='decimal'>Decimal</option>
													<option value='double'>Double</option>
													<option value='float'>Float</option>
												</optgroup><optgroup label='Text'>
													<option value='char'>Char</option>
													<option value='varchar'>Varchar</option>
													<option value='text'>Text</option>
													<option value='tinytext'>Tinytext</option>
													<option value='mediumtext'>Mediumtext</option>
													<option value='longtext'>Longtext</option>
													<option value='set'>Set</option>
													<option value='enum'>Enum</option>
												</optgroup><optgroup label='Shapes'>
													<option value='curve'>Curve</option>
													<option value='geometry'>Geometry</option>
													<option value='geometrycollection'>Geometrycollection</option>
													<option value='point'>Point</option>
													<option value='line'>Line</option>
													<option value='linearring'>Linearring</option>
													<option value='linestring'>Linestring</option>
													<option value='multicurve'>Multicurve</option>
													<option value='multilinestring'>Multilinestring</option>
													<option value='multipoint'>Multipoint</option>
													<option value='multipolygon'>Multipolygon</option>
													<option value='multisurface'>Multisurface</option>
													<option value='polygon'>Polygon</option>
													<option value='surface'>Surface</option>
												</optgroup>
											</select>
										</span>
										<span class='table-center'><input type='number' name='collength' id='collength' class='input-text' /></span>
										<span class='table-center'>
											<select name='signed' class='input-text'>
												<option value='signed'>&nbsp;</option>
												<option value='signed'>Signed</option>
												<option value='unsigned'>Unsigned</option>
											</select>
										</span>
										<span class='table-center'><select name='allownull' class='input-text'>
											<option value='' >&nbsp;</option>
											<option value='Yes' >Yes</option>
											<option value='No' >No</option>
										</select></span>
										<span class='table-center'>
											<select name='key' class='input-text'>
												<option value=''>&nbsp;</option>
												<option value='Primary'>Primary</option>
												<option value='Unique'>Unique</option>
											</select>
										</span>
										<span class='table-center'><input type='text' name='default' class='input-text' title='You can use \"current_timestamp\" as well' /></span>
										<span class='table-center'>
											<select name='extra' class='input-text'>
												<option value=''>&nbsp;</option>
												<option value='Auto increment'>Auto increment</option>
												<option value='On update current timestamp'>On update current timestamp</option>
											</select>
										</span>
										<span class='table-center' >
											<span class='greenbutton'>
												<input type='button' name='insertalter' id='insertalter' value='Insert column' onclick=\"sendform('inserttablecolumn','insertalter');\" />
											</span>
										</span>
									</form>
								</div>";
							if($colour!="white") echo "<hr>";
						}
							
						echo "</div>";
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
						echo "<div class='results' id='results'>";
						//http://www.anyexample.com/programming/php/php_mysql_example__display_table_as_html.xml
						
						$field=array();
						$firstfieldname="";
						$sqli="SELECT COUNT(*) FROM ".$table;
						$countqry=mysqli_query($link,$sqli);
						$countarray=mysqli_fetch_array($countqry);
						mysqli_free_result($countqry);
						
						if(isset($_GET['max']) && $_GET['max']!='' && $_GET['max']!=null) $max=$_GET['max'];
						else $max=$countarray[0];
						
						if(isset($_GET['min']) && $_GET['min']!='' && $_GET['min']!=null) $min=$_GET['min'];
						else{
							$min=$max-100;
							if($min<1) $min=1;
						}
						
						echo "<div class='tr-results-green'>
								<form method='get'>
									<input type='hidden' name='database' value='$database' >
									<input type='hidden' name='table' value='$table' >
									Showing rows&nbsp;<input type='number' name='min' class='input-text' value='$min' min='1' max='".$countarray[0]."' style='width:50px;' />&nbsp;to&nbsp;<input type='number' name='max' class='input-text' value='".$max."' style='width:50px;' min='1' max='".$countarray[0]."' />&nbsp;of&nbsp;".$countarray[0]."&nbsp;<input type='submit' name='' value='Refresh'>&nbsp;<a href='#bottom'>Go to bottom</a>
								</form>
							</div>
							<div class='tr-results-white'>";
						
						$alterqry=mysqli_query($link,$sql);
						while ($finfo=mysqli_fetch_array($alterqry)) {
							//show headers
							echo "	<span class='table-head-center' title='".$finfo['Type']."' ><a href='?database=$database&table=$table&min=$min&max=$max&sort=".$finfo['Field']."&dir=";
							if(isset($_GET['sort']) && $finfo['Field']==$_GET['sort']){
								if(isset($_GET['dir']) && $_GET['dir']=="ASC") echo "DESC";
								else echo "ASC";
							}else echo "ASC";
							echo "'>".$finfo['Field']." ";
							if(isset($_GET['dir']) && isset($_GET['sort']) && $_GET['sort']==$finfo['Field']){
								if($_GET['dir']=="DESC") echo "<img src='images/za.png' title='desc' />";
								else echo "<img src='images/az.png' title='asc' />";
							}
							echo "</a></span>";
							if($firstfieldname=="") $firstfieldname=$finfo['Field']; //assumes the first field is unique!
							$field[]=$finfo['Field'];
						}
						mysqli_free_result($alterqry);
						echo "</div>";
						$colour="green";
						
						$limit=($max-$min)+1;
						$offset=$min-1;
						if($offset<0) $offset=0;
						
						$sql="SELECT * FROM ".$table;
						if(isset($_GET['sort'])) $sql.=" ORDER BY ".$_GET['sort']." ".$_GET['dir'];
						$sql.=" LIMIT ".$limit." OFFSET ".$offset;
						
						if($qry=mysqli_query($link,$sql)){
							while($row=mysqli_fetch_row($qry)){
								$delrow=Array('item','value');
								echo "	<div class='tr-results-$colour' >";
								$fieldnum=0;
								foreach($row as $cell){
									echo "<span class='table-center' "; if(strlen($cell>25)) echo "title='".$cell."' "; echo ">
											<form method='post' id='newvalue' >
												<input type='hidden' name='database' value='".$database."' />
												<input type='hidden' name='table' value='".$table."' />
												<input type='hidden' name='column' value='".$field[$fieldnum]."' />
												<input type='hidden' name='oldvalue' value=\"".$cell."\" />
												<input name='newvalue' class='input-text' type='text' value=\"".$cell."\" onkeypress=\"keypress(event,'newvalue');\" />
												<input type='hidden' name='field' value='".$firstfieldname."' />
												<input type='hidden' name='value' value='".$row[0]."' />
											</form>
										</span>&nbsp;";
									$delrow['item'][$fieldnum]=$field[$fieldnum];
									$delrow['value'][$fieldnum]=$cell;
									$fieldnum++;
								}
								
								echo "<span class='table-center'>
									<form method='post'>
										<span class='redbutton'>
											<input type='button' name='droprowmaybe' value='Drop row' onclick=\"droprowq('".$delrow['item'][0]."','".$delrow['value'][0]."','".$delrow['item'][1]."','".$delrow['value'][1]."');\" />
										</span>
									</form>
									<form method='post' id='droprowform".$delrow['item'][0].$delrow['value'][0]."' style='display:none;'>
										<input type='hidden' name='database' value='$database' />
										<input type='hidden' name='table' value='$table' />";
									for($i=0; $i<$fieldnum; $i++) echo "<input type='hidden' name='".$delrow['item'][$i]."' value='".$delrow['value'][$i]."' />";
								echo "<input type='hidden' name='droprow' value='Drop row' /></form></span></div>";
								$colour=($colour == "white" ? "green" : "white");
							}
							mysqli_free_result($qry);
							//add new
							echo "<div class='tr-results-$colour'>
								<form method='post' name='insertfield' id='insertfield'>
									<input type='hidden' name='database' value='$database' />
									<input type='hidden' name='table' value='$table' />";
							foreach($field as $fieldname){
								switch(strtolower($typeforresults[$fieldname])){
									case "bit":
									case "tinyint":
									case "smallint":
									case "mediumint":
									case "int":
									case "integer":
									case "bigint":
									case "decimal":
									case "dec":
									case "float":
									case "double":
										$resultdatatype="number";
										$auto_increment=0;
										$sql="SHOW COLUMNS FROM $table WHERE Field='".$fieldname."'";
										if(!$extraqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
										else{
											$extraarray=mysqli_fetch_array($extraqry);
											if($extraarray['Extra']=="auto_increment"){
												$sql="SHOW TABLE STATUS WHERE Name='".$table."'";
												if(!$aiqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
												else{
													$aiarray=mysqli_fetch_array($aiqry);
													$auto_increment=$aiarray['Auto_increment'];
													mysqli_free_result($aiqry);
												}
											}
											mysqli_free_result($extraqry);
										}
										break;
									case "date":
									case "year":
									case "time":
									case "timestamp":
									case "datetime":
										$resultdatatype="datetime"; //Need to wait for proper HTML5/CSS2 support for this to work properly (http://www.w3schools.com/html/html5_form_input_types.asp)
										break;
									default:
										$auto_increment="";
										$resultdatatype="text";
								}
								
								if(isset($defaultforresults[$fieldname]) && $defaultforresults[$fieldname]!="" && $defaultforresults[$fieldname]!=null && strtolower($defaultforresults[$fieldname])!="null"){
									echo "<span class='table-center' ><input type='".$resultdatatype."' name='".$fieldname."' class='input-text' placeholder='New row' ".$requiredtext[$fieldname]." value='".$defaultforresults[$fieldname]."' /></span>&nbsp;";
								}else{
									if($autoincrement[$fieldname]=="True") echo "<span class='table-center' ><input type='".$resultdatatype."' name='".$fieldname."' class='input-text' placeholder='New row' ".$requiredtext[$fieldname]." value='$auto_increment' /></span>&nbsp;";
									else{
										if($resultdatatype=="number") echo "<span class='table-center' ><input type='".$resultdatatype."' name='".$fieldname."' class='input-text' placeholder='New row' ".$requiredtext[$fieldname]." value='0' /></span>&nbsp;";
										else echo "<span class='table-center' ><input type='".$resultdatatype."' name='".$fieldname."' class='input-text' placeholder='New row' ".$requiredtext[$fieldname]." value='' /></span>&nbsp;";
									}
								}
								
							}
							echo "<span class='table-center' style='position:relative;left:-5px;'>
											<span class='greenbutton'>
												<input type='submit' name='insertrow' id='insertrow' value='Insert row' />
											</span>
										</span>
									</form>
								</div><a id='bottom' name='bottom'></a>";
							if($colour!="white") echo "<hr>";
						}else showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link));
					}else echo "<div class='alterdatabase'>The table named '$table' does not exist</div>";
				}
			}
		}else{
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\/
			if(isset($_GET['database']) && $_GET['database']!="" && $_GET['database']!=null){
				//database only
				$database=$_GET['database'];
				if(!$link=@mysqli_connect($server,$username,$password,$database)) $popuptext='Error '.mysqli_connect_errno().': '.mysqli_connect_error();
				else{
					mysqli_set_charset($link, "utf8");
					$sql="SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$database."'";
					if(!$charqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
					else{
						if(mysqli_num_rows($charqry)>0){
							echo "<div class='operations'>";
							$chararray=mysqli_fetch_array($charqry);
							$charset=$chararray['DEFAULT_CHARACTER_SET_NAME'];
							$collation=$chararray['DEFAULT_COLLATION_NAME'];
							
							echo "<form method='post' id='databaseop'>
									<input type='hidden' name='olddatabase' value='".$database."'/>
									Database name: <input type='text' name='newdatabase' value='".$database."'/>
									<input type='hidden' name='oldcharset' value='".$charset."'/>
									Character set: <select name='newcharset'>";
							$sql="SHOW CHARACTER SET";
							if(!$charqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
							else{
								while($chararray=mysqli_fetch_array($charqry)){
									echo "<option value='".$chararray['Charset']."' ";
									if(strtoupper($chararray['Charset'])==strtoupper($charset)) echo " selected";
									echo ">".$chararray['Charset']." (".$chararray['Description'].")</option>";
								}
								mysqli_free_result($charqry);
								echo "</select>
									<input type='hidden' name='oldcollation' value='".$collation."'/>
									Collation: <select name='newcollation'>";
								$sql="SHOW COLLATION";
								if(!$collationqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
								else{
									while($collationarray=mysqli_fetch_array($collationqry)){
										echo "<option value='".$collationarray['Collation']."' ";
										if(strtoupper($collationarray['Collation'])==strtoupper($collation)){
											echo " selected";
											$defaultcollation=$collation;
										}
										echo ">".$collationarray['Collation']."</option>";
									}
									mysqli_free_result($collationqry);
									echo "</select>
											<input type='submit' name='alterdatabase' value='Save changes'/>
										</form>";
								}
							}
							
							echo "</div>";
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
							echo "<div class='alterdatabase'>";
							$sql="SHOW TABLES IN ".$database;
							echo "<div class='tr-blue'>
									<span class='table-head-center'>Table name</span>
									<span class='table-head-center'><a href='http://dev.mysql.com/doc/refman/5.0/en/storage-engines.html' target='_blank'>Engine</a></span>
									<span class='table-head-center'>Collation</span>
									<span class='table-head-center'>Rows</span>
								</div>";
							$colour="white";
							if(!$tableqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
							else{
								while($tablearray=mysqli_fetch_array($tableqry)){
									$table=$tablearray[0];
									echo "<div class='tr-$colour'><span class='table-left' style='width:150px;display:inline-block;' ";
									if(strlen($table>25)) echo "title='".$table."' ";
									echo "><a href='?database=".$database."&table=".$table."'>".$table."</a></span>";
									$sql="SELECT ENGINE, TABLE_COLLATION FROM information_schema.tables WHERE TABLE_NAME='".$table."' AND TABLE_SCHEMA='".$database."'";
									if($tableinfoqry=mysqli_query($link,$sql)){
										$tinfoarray=mysqli_fetch_array($tableinfoqry);
										echo "<span class='table-center'>".$tinfoarray['ENGINE']."</span>
											<span class='table-center'>".$tinfoarray['TABLE_COLLATION']."</span>
											<span class='table-center'>";
										$sqli="SELECT COUNT(*) FROM ".$table;
										$countqry=mysqli_query($link,$sqli);
										$countarray=mysqli_fetch_array($countqry);
										echo $countarray[0]." records</span>";
										mysqli_free_result($tableinfoqry);
									}
									echo "</div>";
									
									$colour=($colour == "white" ? "blue" : "white");
								}
								mysqli_free_result($tableqry);
								echo "<div class='tr-$colour'><form method='post' id='createtableform'>
									<input type='hidden' name='database' value='".$database."' />
									<span class='table-left'><input type='text' name='inserttablename' class='input-text' style='text-align:left;' placeholder='New table' required /></span>
									<span class='table-left'><select name='engine' class='input-text'><option value=''>&nbsp;</option>";
								$sql="SHOW ENGINES";
								if(!$engineqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
								else{
									while($enginearray=mysqli_fetch_array($engineqry)) echo "<option value='".$enginearray['Engine']."'>".$enginearray['Engine']."</option>";
									mysqli_free_result($engineqry);
									echo "</select></span>
										<span class='table-left'>
											<select name='collation' class='input-text'>
												<option value=''>&nbsp;</option>";
									$sql="SHOW COLLATION";
									if(!$collationqry=mysqli_query($link,$sql)) showlogin('Error '.mysqli_errno($link).': '.mysqli_error($link)."<br>Query:".$sql);
									else{
										while($collationarray=mysqli_fetch_array($collationqry)){
											echo "<option value='".$collationarray['Collation']."' ";
											if($collationarray['Collation']==$defaultcollation) echo "selected ";
											echo ">".$collationarray['Collation']."</option>";
										}
										mysqli_free_result($collationqry);
										echo "</select></span>";
									}
								}
								echo "<span class='table-center'><input type='button' name='createtable' id='createtable' value='Create table' onclick=\"sendform('createtableform','createtable');\" /></span>
									</form></div>";
							}
							if($colour!="white") echo "<hr>";
							echo "</div>";
						}else echo "<div class='operations'>Database does not exist</div>";
					}
				}
			}else{
				if(!$link=@mysqli_connect($server,$username,$password)) $popuptext='Error '.mysqli_connect_errno().': '.mysqli_connect_error();
				else{
					mysqli_set_charset($link, "utf8");
					$charset=mysqli_get_charset($link);
					echo "<div class='main'>
						<div class='tr-blue' ><span class='table-head-left'>Host info</span><span>".mysqli_get_host_info($link)."</span></div>
						<div class='tr-white'><span class='table-head-left'>Server info</span><span>".mysqli_get_server_info($link)."</span></div>
						<div class='tr-blue' ><span class='table-head-left'>Client info</span><span>".mysqli_get_client_info($link)."</span></div>
						<div class='tr-white'><span class='table-head-left'>Client version</span><span>".mysqli_get_client_version($link)."</span></div>
						<div class='tr-blue' ><span class='table-head-left'>Protocol version</span><span>".mysqli_get_proto_info($link)."</span></div>
						<div class='tr-white'><span class='table-head-left'>Character set</span><span>".$charset->charset."</span></div>
						<div class='tr-blue' ><span class='table-head-left'>Collation</span><span>".$charset->collation."</span></div>
						<div class='tr-white'><span class='table-head-left'>Thread ID</span><span>".mysqli_thread_id($link)."</span></div>
					</div>";
				}
			}
		}
	}
	
?>
<script type="text/javascript">
    var after=(new Date()).getTime();
    var sec=(after-before)/1000;
    var p=document.getElementById("rowsreturned");
	if(typeof p != 'undefined' && p != null) p.innerHTML=p.innerHTML + " in " + sec + " seconds";
</script>
</body>
</html>