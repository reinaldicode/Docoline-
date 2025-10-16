<?php
//**************************************
//     Page load dropdown results     //
//**************************************
function getTierOne()
{
	include "koneksi.php";
	$result = mysqli_query($link, "SELECT DISTINCT group_dev FROM device") 
	or die(mysql_error());

	  while($tier = mysqli_fetch_array( $result )) 
  
		{
		   echo '<option value="'.$tier['group_dev'].'">'.$tier['group_dev'].'</option>';
		}

}

//**************************************
//     First selection results     //
//**************************************
if (isset($_GET['func']) && $_GET['func'] == "section") { 
    if (isset($_GET['drop_var'])) {
        section($_GET['drop_var']); 
    }
}

function section($drop_var)
{  
    //include_once('koneksi.php');
	include "koneksi.php";
	//include "connt_db.php";
	$result = mysqli_query($link, "SELECT id_device,name,kode FROM device WHERE group_dev='$drop_var'") 
	or die(mysqli_error());
	
	echo '<select name="device" id="device" class="form-control">
	      <option value="-" selected="selected"> --- Select Device --- </option>
	      <option value="General production" > General production </option>
	      <option value="General PC" > General PC </option>
	      ';

		   while($drop_2 = mysqli_fetch_array( $result )) 
			{
			  echo '<option value="'.$drop_2['name'].'">'.$drop_2['name'].'</option>';
			}
	
	echo '</select> ';
    //echo '<input type="submit" name="submit" value="Submit" />';
}



if (isset($_GET['func']) && $_GET['func'] == "device") { 
    if (isset($_GET['drop_var2'])) {
        device($_GET['drop_var2']); 
    }
}


function device($drop_var2)
{  
    //include_once('koneksi.php');
	include "koneksi.php";
	//include "connt_db.php";
	$result2 = mysqli_query($link, "SELECT distinct Process FROM docu WHERE device = '$drop_var2' and process<>'0' and process<>'' and doc_type='WI' order by process") 
	or die(mysqli_error());
	//echo $result2;
	echo '
	<select name="proc" id="proc" class="form-control">
	      <option value="-"  selected="selected"> --- Select Process --- </option>';
	      echo '<option value="General Process">General Process</option>';
		   while($drop_3 = mysqli_fetch_array( $result2 )) 
			{
			  echo '<option value="'.$drop_3['Process'].'">'.$drop_3['Process'].'</option>';
			}
	
	echo '</select> ';
    //echo '<input type="submit" name="submit" value="Submit" />';
}

function statu()
{  
    include "koneksi.php";
	$res_statu = mysqli_query($link, "SELECT DISTINCT status FROM docu") 
	or die(mysql_error());

	  while($statu = mysqli_fetch_array( $res_statu )) 
  
		{
		   echo '<option value="'.$statu['group_dev'].'">'.$statu['group_dev'].'</option>';
		}

}

?>