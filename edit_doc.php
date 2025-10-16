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

// Pastikan $drf didefinisikan - ambil dari GET atau POST
$drf = isset($_GET['drf']) ? mysqli_real_escape_string($link, $_GET['drf']) : (isset($_POST['drf']) ? mysqli_real_escape_string($link, $_POST['drf']) : '');

if (empty($drf)) {
    die("Error: No DRF specified");
}

$sql="select * from docu where no_drf='$drf'";
$res=mysqli_query($link, $sql);

while($data = mysqli_fetch_array($res)) 
{
 ?>

<div class="row">
<div class="col-xs-1"></div>
<div class="col-xs-7 well well-lg">
 <h2>Edit Document</h2>

				
			


				<form action="" method="POST" enctype="multipart/form-data">
				 <table class="table">
				 	
				 	<!-- Hidden field untuk menyimpan drf -->
				 	<input type="hidden" name="drf" value="<?php echo htmlspecialchars($drf); ?>">
				 	
				 	<tr cellpadding="50px">
				 		<td>No. Document &nbsp;&nbsp;</td>
				 		<td>:&nbsp;	&nbsp; &nbsp;</td>
				 		<td><input type="text" class="form-control" name="nodoc" value="<?php echo htmlspecialchars($data['no_doc']); ?>"></td>
				 	</tr>
				 	<tr cellpadding="50px">
				 		<td>No. Revision &nbsp;&nbsp;</td>
				 		<td>:&nbsp;	&nbsp; &nbsp;</td>
				 		<td><input type="text" class="form-control" name="norev" value="<?php echo htmlspecialchars($data['no_rev']); ?>"></td>
				 	</tr>

				 	<tr>
				 		<td>Review To</td>
				 		<td>:</td>
				 		<td>
				 			
						 <select name="revto" class="form-control">
										<option value="-"> --- Select --- </option>
										
										<option value="Issue" <?php if ($data['rev_to']=="Issue") {echo 'selected';} ?> > Issue </option>
										<option value="Revision" <?php if ($data['rev_to']=="Revision") {echo 'selected';} ?> > Revision </option>
										<option value="Cancel" <?php if ($data['rev_to']=="Cancel") {echo 'selected';} ?> > Cancel </option>
									
										</option>
									</select>
				 		</td>
				 	</tr>

				 	<tr>
				 		<td>Category</td>
				 		<td>:</td>
				 		<td>
				 			
						 <select name="cat" class="form-control">
										<option value="-"> --- Select --- </option>
										
										<option value="Internal" <?php if ($data['category']=="Internal") {echo 'selected';} ?> > Internal </option>
										<option value="External" <?php if ($data['category']=="External") {echo 'selected';} ?> > External </option>
										
									
										</option>
									</select>
				 		</td>
				 	</tr>

				 	<tr>
				 		<td>Document Type</td>
				 		<td>:</td>
				 		<td>
				 			
						 <select name="type" class="form-control">
										<option value="-"> --- Select Type --- </option>
										
										<option value="Form" <?php if ($data['doc_type']=="Form") {echo 'selected';} ?> > Form </option>
										<option value="Procedure" <?php if ($data['doc_type']=="Procedure") {echo 'selected';} ?> > Procedure </option>
										<option value="WI" <?php if ($data['doc_type']=="WI") {echo 'selected';} ?> > WI </option>
									
										</option>
									</select>
				 		</td>
				 	</tr>

				 	<tr>
				 		<td>Section</td>
				 		<td>:</td>
				 		<td>
				 			<?php 
						$sect="select * from section order by id_section";
						$sql_sect=mysqli_query($link, $sect);

					?>
						 <select id="section" name="section" class="form-control" >
										<option value="-"> --- Select Section --- </option>
										<?php while($data_sec = mysqli_fetch_array( $sql_sect )) 
										{ ?>
										<option value="<?php echo htmlspecialchars($data_sec['id_section']); ?>" <?php if ($data['section']==$data_sec['id_section']) {echo 'selected';} ?>> <?php echo htmlspecialchars($data_sec['sect_name']); ?> </option>
										<?php } ?>
										</option>
									</select>
				 		</td>
				 	</tr>


					<tr>
				 		<td>Device</td>
				 		<td>:</td>
				 		<td>
				 			<?php 
						$dev="select * from device where status='Aktif' order by group_dev";
						$sql_dev=mysqli_query($link, $dev);

					?>
						 <select id="device" name="device" class="form-control" >
										<option value="-"> --- Select Device --- </option>
										<?php while($data_dev = mysqli_fetch_array( $sql_dev )) 
										{ ?>
										<option value="<?php echo htmlspecialchars($data_dev['name']); ?>" <?php if ($data['device']==$data_dev['name']) {echo 'selected';} ?> > <?php echo htmlspecialchars($data_dev['name']); ?> </option>
										<?php } ?>
										</option>
									</select>
				 		</td>
				 	</tr>


				 	<tr>
				 		<td>Process</td>
				 		<td>:</td>
				 		<td>
				 			<?php 
						$sect="select * from process order by  proc_name";
						$sql_sect=mysqli_query($link, $sect);

					?>
						 <select id="process" name="process" class="form-control">
										<option value="-"> --- Select Process --- </option>
										<?php while($data_sec = mysqli_fetch_array( $sql_sect )) 
										{ ?>
										<option value="<?php echo htmlspecialchars($data_sec['proc_name']); ?>" <?php if ($data['process']==$data_sec['proc_name']) {echo 'selected';} ?> > <?php echo htmlspecialchars($data_sec['proc_name']); ?> </option>
										<?php } ?>
										</option>
									</select>
				 		</td>
				 	</tr>

				 	<tr>
				 		<td>Doc. Title</td>
				 		<td>:</td>
				 		<td><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($data['title']); ?>"></td>
				 	</tr>

				 	<tr>
				 		<td>Doc. Description</td>
				 		<td>:</td>
				 		<td >
				 		<textarea  class="form-control" name="desc" cols="40" rows="10" wrap="physical" ><?php echo htmlspecialchars($data['descript']); ?></textarea>
                	    </td>
				 	</tr>

				 	<tr>
				 		<td>Requirement Document</td>
				 		<td>:</td>
				 		<td >
							
					     
					      <table>
					     <tr><input type="radio" aria-label="ISO 9001" name="iso" value="1" <?php if ($data['iso']==1){echo "checked";}?> >&nbsp; ISO 9001 <br /></tr>
					        <tr><input type="radio" aria-label="ISO 14001" name="iso" value="2" <?php if ($data['iso']==2){echo "checked";}?> >&nbsp; ISO 14001 <br /></tr>
					        <tr><input type="radio" aria-label="OHSAS" name="iso" value="3" <?php if ($data['iso']==3){echo "checked";}?>  >&nbsp; OHSAS <br /></tr>
					        <tr><input type="radio" aria-label="None" name="iso" value="0" <?php if ($data['iso']==0 || empty($data['iso'])){echo "checked";}?>  >&nbsp; None <br /></tr>
					      </table>
                	    </td>
				 	</tr>
				 	<tr>
				 		<td>Related/Addopted Document</td>
				 		<td>:</td>
				 		<td>
				 		<input type="text" class="form-control" name="rel[]" placeholder="Related Doc 1">
				 		<input type="text" class="form-control" name="rel[]" placeholder="Related Doc 2">
				 		<input type="text" class="form-control" name="rel[]" placeholder="Related Doc 3">
				 		<input type="text" class="form-control" name="rel[]" placeholder="Related Doc 4">
				 		<input type="text" class="form-control" name="rel[]" placeholder="Related Doc 5">
				 		</td>
				 	</tr>
				 	

				 	<tr>
				 		<td>Revision reason/history</td>
				 		<td>:</td>
				 		<td >
				 		<textarea  class="form-control" name="hist" cols="40" rows="10" wrap="physical" ><?php echo htmlspecialchars($data['history']); ?></textarea>
                	    </td>
				 	</tr>

				 	<tr>
				 		<td></td>
				 		<td></td>
				 		<td>
				 			<input type="submit" value="Save" name="submit" class="btn btn-success">
				 		</td>
				 	</tr>
				</form>



				 </table>
				 </div>

 </div>
	<?php } ?>


 <?php
if (isset($_POST['submit'])){


include 'koneksi.php';

// Ambil drf dari POST
$drf = mysqli_real_escape_string($link, $_POST['drf']);

// Escape semua input untuk mencegah SQL injection
$nodoc = mysqli_real_escape_string($link, $_POST['nodoc']);
$norev = mysqli_real_escape_string($link, $_POST['norev']);
$revto = mysqli_real_escape_string($link, $_POST['revto']);
$cat = mysqli_real_escape_string($link, $_POST['cat']);
$type = mysqli_real_escape_string($link, $_POST['type']);
$section = mysqli_real_escape_string($link, $_POST['section']);
$device = mysqli_real_escape_string($link, $_POST['device']);
$process = mysqli_real_escape_string($link, $_POST['process']);
$title = mysqli_real_escape_string($link, $_POST['title']);
$desc = mysqli_real_escape_string($link, $_POST['desc']);
$iso = mysqli_real_escape_string($link, $_POST['iso']);
$hist = mysqli_real_escape_string($link, $_POST['hist']);

// Update query
$sql = "UPDATE docu SET 
        no_doc='$nodoc', 
        no_rev='$norev', 
        rev_to='$revto', 
        category='$cat', 
        doc_type='$type', 
        section='$section', 
        device='$device',
        process='$process',
        title='$title',
        descript='$desc', 
        iso='$iso', 
        history='$hist' 
        WHERE no_drf='$drf'";

$res = mysqli_query($link, $sql);

if (!$res) {
    die("Update failed: " . mysqli_error($link));
}

// Hapus related documents yang lama terlebih dahulu
mysqli_query($link, "DELETE FROM rel_doc WHERE no_drf='$drf'");

// Insert related documents yang baru
if (isset($_POST["rel"]) && is_array($_POST["rel"])) {
    foreach($_POST["rel"] as $no_doc) {
        // Trim whitespace dan cek apakah tidak kosong
        $no_doc = trim($no_doc);
        
        if (!empty($no_doc)) {
            $no_doc_escaped = mysqli_real_escape_string($link, $no_doc);
            
            // Tidak perlu mengisi kolom id jika AUTO_INCREMENT
            $q = mysqli_query($link, "INSERT INTO rel_doc(no_drf, no_doc) VALUES ('$drf', '$no_doc_escaped')"); 
            
            if (!$q) {
                echo "Error inserting related doc: " . mysqli_error($link) . "<br>";
            }
        }
    }
}

?>

<script language='javascript'>
    alert('Document updated successfully');
    document.location='my_doc.php';
</script>

<?php 
}
 ?>