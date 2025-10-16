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

// Ambil variabel dari session dengan aman
$state = $_SESSION['state'] ?? '';
$nrp = $_SESSION['nrp'] ?? '';
?>

<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>

<style>
.modal .form-control { margin-bottom:10px; }
.btn-upload-sos { margin-left:6px; }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Modal Secure Document data binding (event delegation)
    $(document).on('click', '.sec-file', function(e) {
        e.preventDefault();
        var drf     = $(this).data('id') || '';
        var lama    = $(this).data('lama') || '';
        var type    = $(this).data('type') || '';
        var rev     = $(this).data('rev') || '';
        var section = $(this).data('section') || '';
        var status  = $(this).data('status') || '';
        var tipe    = $(this).data('tipe') || '';

        $('#myModal2 #drf').val(drf);
        $('#myModal2 #lama').val(lama);
        $('#myModal2 #type').val(type);
        $('#myModal2 #rev').val(rev);
        $('#myModal2 #section').val(section);
        $('#myModal2 #status').val(status);
        $('#myModal2 #tipe').val(tipe);

        $('#myModal2').modal('show');
    });

    // Modal Upload Sosialisasi data binding
    $(document).on('click', '.btn-upload-sos', function(e){
        e.preventDefault();
        var drf = $(this).data('drf') || '';
        var nodoc = $(this).data('nodoc') || '';
        
        $('#modal_upload_drf').val(drf);
        $('#modal_upload_nodoc').text(nodoc);
        $('#modalSosialisasi').modal('show');
    });

    // reset form saat modal ditutup
    $('#modalSosialisasi').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
    
    $('#myModal2').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
});
</script>

<h3>Obsolate Document's List</h3>
<form action="" method="GET" >
	<div class="col-sm-4">
				<select name="tipe" class="form-control">
					<option value="0"> --- Select Type --- </option>
					
					<option value="WI"> WI </option>
					<option value="Procedure"> Procedure </option>
					<option value="Form"> Form </option>
					
										
									</select>		

	<?php 
		$sect="select * from section order by sect_name";
		$sql_sect=mysqli_query($link, $sect);

	?>
		 <select name="section" class="form-control">
						<option value="0"> --- Select Section --- </option>
						<?php while($data_sec = mysqli_fetch_array( $sql_sect )) 
						{ ?>
						<option value="<?php echo htmlspecialchars($data_sec['id_section']); ?>"> <?php echo htmlspecialchars($data_sec['sect_name']); ?> </option>
						<?php } ?>
						</option>
					</select>
					
		<select name="cat" class="form-control">
						<option value="0"> --- Select Category --- </option>
						
						<option value="External"> External </option>
						<option value="Internal" selected="selected"> Internal </option>
						
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

    // Ambil input dengan aman
    $section = mysqli_real_escape_string($link, $_GET['section'] ?? '');
    $tipe = mysqli_real_escape_string($link, $_GET['tipe'] ?? '');
    $cat = mysqli_real_escape_string($link, $_GET['cat'] ?? '');

	$sql="select * from docu where section='$section' and doc_type='$tipe' and category='$cat' and status='Obsolate' order by no_drf";	
	
	// echo $sql;
?>
<br /><br />

<?php

$res=mysqli_query($link, $sql);
//$rows = mysql_num_rows($res);

//echo $sql;


?>
<br />
<div class="table-responsive">
<table class="table table-hover">
<h1> Obsolate <?php echo htmlspecialchars($tipe); ?> List For Section: <strong><?php echo htmlspecialchars($section);?></strong> , Category: <strong><?php echo htmlspecialchars($tipe);?></strong></h1>
<thead bgcolor="#00FFFF">
<tr>
	<td>No</td>
	<td>Date</td>
	<td>No Document</td>
	<td>No Rev.</td>
	<td>No. Drf</td>
	<td>Title</td>
	<td>Process</td>
	<td>Section</td>
	<td>Action</td>
	<td>Sosialisasi</td>
</tr>
</thead>
<?php
$i=1;
while($info = mysqli_fetch_array($res)) 
{ 
    // periksa apakah ada bukti sosialisasi
    $has_sos = !empty($info['sos_file']);
?>
<tbody>
<tr>
	<td>
		<?php echo $i; ?>
	</td>
	<td>
		<?php echo htmlspecialchars($info['tgl_upload']);?>
	</td>
	<td>
		<?php echo htmlspecialchars($info['no_doc']);?>
	</td>
	<td>
		<?php echo htmlspecialchars($info['no_rev']);?>
	</td>
	<td>
		<?php echo htmlspecialchars($info['no_drf']);?>
	</td>
	<td>
	<?php if ($info['no_drf']>12800){$tempat=$info['doc_type'];} else {$tempat='document';}?>
	<a href="<?php echo htmlspecialchars($tempat); ?>/<?php echo htmlspecialchars($info['file']); ?>" >
		<?php echo htmlspecialchars($info['title']);?>
		</a>
	</td>
	<td>
		<?php echo htmlspecialchars($info['process']);?>
	</td>
	<td>
		<?php echo htmlspecialchars($info['section']);?>
	</td>
	<td style="white-space:nowrap;">
		<a href="detail.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-info" title="lihat detail"><span class="glyphicon glyphicon-search" ></span> </a>
	<a href="lihat_approver.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-info" title="lihat approver"><span class="glyphicon glyphicon-user" ></span> </a>
	<a href="radf.php?drf=<?php echo urlencode($info['no_drf']);?>&section=<?php echo urlencode($info['section'])?>" class="btn btn-xs btn-info" title="lihat RADF"><span class="glyphicon glyphicon-eye-open" ></span> </a>
	<?php if ($state=='Admin' || $state=="Originator"){?>
	<a href="edit_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-primary" title="Edit Doc"><span class="glyphicon glyphicon-pencil" ></span> </a>
	<a href="del_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-danger" onClick="return confirm('Delete document <?php echo addslashes($info['no_doc'])?>?')" title="Delete Doc"><span class="glyphicon glyphicon-remove" ></span> </a>
	
	<?php if ($info['status']=='Approved') {?>
	<a data-toggle="modal" data-target="#myModal2" 
       data-id="<?php echo htmlspecialchars($info['no_drf']);?>" 
       data-lama="<?php echo htmlspecialchars($info['file']);?>" 
       data-type="<?php echo htmlspecialchars($info['doc_type']);?>"
       data-rev="<?php echo htmlspecialchars($info['no_rev']);?>"
       data-section="<?php echo htmlspecialchars($info['section']);?>"
       data-status="<?php echo htmlspecialchars($info['status']);?>" 
       data-tipe="<?php echo htmlspecialchars($info['category']);?>"
       class="btn btn-xs btn-success sec-file" 
       title="Secure Document">
	<span class="glyphicon glyphicon-play" ></span></a>
	<?php }} ?>
	</td>
	
	<!-- Kolom Sosialisasi - SAMA SEPERTI form_other.php -->
	<td>
	    <?php if ($has_sos) { ?>
	        <a href="lihat_sosialisasi.php?drf=<?php echo urlencode($info['no_drf']); ?>" 
	           class="btn btn-xs btn-primary" title="Lihat Bukti Sosialisasi">
	            <span class="glyphicon glyphicon-file"></span>
	        </a>
	    <?php } else { ?>
	        <button type="button"
	            class="btn btn-xs btn-success btn-upload-sos"
	            data-drf="<?php echo htmlspecialchars($info['no_drf']);?>"
	            data-nodoc="<?php echo htmlspecialchars($info['no_doc']);?>"
	            title="Upload Bukti Sosialisasi">
	            <span class="glyphicon glyphicon-upload"></span>
	        </button>
	    <?php } ?>
	</td>
	
</tr>
</tbody>
<div>
<?php 
$i++;} 


}


?> 
</div>

<!-- Modal Secure Document -->
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Update Document</h4>
            </div>
            <div class="modal-body">
                <form name="secure_doc" method="POST" action="process.php" enctype="multipart/form-data">
                    <input type="hidden" name="drf" id="drf" class="form-control" value=""/>
                    <input type="hidden" name="lama" id="lama" class="form-control" value=""/>
                    <input type="hidden" name="rev" id="rev" class="form-control" value=""/>
                    <input type="hidden" name="type" id="type" class="form-control" value=""/>
                    <input type="hidden" name="section" id="section" class="form-control" value=""/>
                    <input type="hidden" name="status" id="status" class="form-control" value=""/>
                    <input type="hidden" name="tipe" id="tipe" class="form-control" value=""/>
                    <div class="form-group">
                        <label>Upload New Secured File:</label>
                        <input type="file" name="baru" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <input type="submit" name="upload" value="Update" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Sosialisasi -->
<div class="modal fade" id="modalSosialisasi" tabindex="-1" role="dialog" aria-labelledby="modalSosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="upload_sosialisasi.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="modalSosLabel">Upload Bukti Sosialisasi</h4>
                </div>
                <div class="modal-body">
                    <p>Upload bukti sosialisasi untuk No. Document: <strong id="modal_upload_nodoc"></strong></p>
                    <input type="hidden" name="drf" id="modal_upload_drf" value="">
                    <?php
                    if (empty($_SESSION['csrf_token'])) {
                        if (function_exists('random_bytes')) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
                        }
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label>File bukti (pdf / jpg / png)</label>
                        <input type="file" name="sos_file" class="form-control" accept=".pdf,image/*" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan / Keterangan</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal" type="button">Batal</button>
                    <button type="submit" name="upload_sosialisasi" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="bootstrap/js/bootstrap.min.js"></script>