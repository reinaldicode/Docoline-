<?php

include('header.php');
?>

<br />
<br />
<br />
<br />

</head>

<?php

//include 'index.php';
include 'koneksi.php';

 ?>
<h3>Company Manual List</h3>
<form action="" method="GET" >
	<div class="col-sm-4">


	
					
		<select name="lang" class="form-control">
						<option value="0"> --- Select Languange --- </option>
						
						<option value="Indonesian"> Indonesian </option>
						<option value="English"> English </option>
						
						</option>
					</select>
		
					
					
	 <input type="submit" name="submit" value="Show" class="btn btn-primary" />
    </div>
    	<br />
    		<br />
    			<br />
    			
	</form>

<?php
if (isset($_GET['submit'])){





$lang=$_GET['lang'];


	

	$sql="select * from docu where section='Manual' and title='$lang' order by no_doc";	
	
	// echo $sql;
?>
<br /><br />

<?php

$res=mysqli_query($link, $sql);
//$rows = mysql_num_rows($res);

//echo $sql;


?>
<br />
<table class="table table-hover">

<thead bgcolor="#00FFFF">
<tr>
	<td>No</td>
	
	
	<td>Title</td>
	<td>Languange</td>
	
	
	
</tr>
</thead>
<?php
$i=1;
while($info = mysqli_fetch_array($res)) 
{ ?>
<tbody>
<tr>
	<td>
		<?php echo $i; ?>
	</td>
	
	<td>
	<?php if ($info['no_drf']>12955){$tempat=$info[doc_type];} else {$tempat='document';}?>
	<a href="<?php echo $tempat; ?>/<?php echo "$info[file]"; ?>" >
		<?php echo "$info[no_doc]";?>
		</a>
	</td>
	<td>
		<?php echo "$info[title]";?>
	</td>
	
	
	
	
</tr>
</tbody>
<div>
<?php 
$i++;} 


}


?> 
</div>