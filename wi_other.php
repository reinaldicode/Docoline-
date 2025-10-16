<?php include "wi_login.php"; ?>
<br />

<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>

<style>
.btn-upload-sos { margin-left:6px; }
.modal .form-control { margin-bottom:10px; }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Modal Secure Document data binding
    $(document).on('click', '.sec-file', function(e) {
        e.preventDefault();
        var drf = $(this).data('id') || '';
        var lama = $(this).data('lama') || '';
        var type = $(this).data('type') || '';
        var rev = $(this).data('rev') || '';
        var status = $(this).data('status') || '';
        var tipe = $(this).data('tipe') || '';
        
        $('#myModal2 #drf').val(drf);
        $('#myModal2 #lama').val(lama);
        $('#myModal2 #type').val(type);
        $('#myModal2 #rev').val(rev);
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

<div class="row">
<div class="col-xs-4 well well-lg">
 <h2>Select Section</h2>

<form action="" method="GET">
 <table>
 	<tr>
 	<?php 
	$sect="select * from section order by sect_name";
	$sql_sect=mysqli_query($link, $sect);
	?>
 		<td>Section</td>
 		<td>:</td>
 		<td>
 			<select name="section" class="form-control">
 				<option value="0"> --- Select Section --- </option>
 				<?php 
				while($data_sec = mysqli_fetch_array($sql_sect)) { 
					$selected = (isset($_GET['section']) && $_GET['section'] == $data_sec['id_section']) ? 'selected' : '';
				?>
					<option value="<?php echo htmlspecialchars($data_sec['id_section']); ?>" <?php echo $selected; ?>>
						<?php echo htmlspecialchars($data_sec['sect_name']); ?>
					</option>
				<?php } ?>
 			</select>
 		</td>
 	</tr>

 	<tr>
 		<td>Status</td>
 		<td>:</td>
 		<td>
 			<select name="status" class="form-control">
				<option value="0"> --- All Status --- </option>
				<option value="Secured" <?php if(isset($_GET['status']) && $_GET['status']=='Secured') echo 'selected'; ?>> Secured </option>
				<option value="Approved" <?php if(isset($_GET['status']) && $_GET['status']=='Approved') echo 'selected'; ?>> Approved </option>
				<option value="Review" <?php if(isset($_GET['status']) && $_GET['status']=='Review') echo 'selected'; ?>> Review </option>
				<option value="Pending" <?php if(isset($_GET['status']) && $_GET['status']=='Pending') echo 'selected'; ?>> Pending </option>
			</select>			
 		</td>
 	</tr>
 	<tr>
 		<td>Category</td>
 		<td>:</td>
 		<td>
 			<select name="category" class="form-control">
				<option value="0"> --- Select Category --- </option>
				<option value="Internal" <?php if(isset($_GET['category']) && $_GET['category']=='Internal') echo 'selected'; ?>> Internal </option>
				<option value="External" <?php if(isset($_GET['category']) && $_GET['category']=='External') echo 'selected'; ?>> External </option>
			</select>			
 		</td>
 	</tr>
 	<tr>
 		<td></td>
 		<td></td>
 		<td>
			<input type='hidden' name='by' value='no_drf'>
 			<input type="submit" value="Show" name="submit" class="btn btn-info">
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-default" style="margin-left:10px;">Reset</a>
 		</td>
 	</tr>
</form>
 </table>
 </div>
</div>

<?php
if (isset($_GET['submit'])){

    // ambil session state
    $state = isset($_SESSION['state']) ? $_SESSION['state'] : '';
    $nrp   = isset($_SESSION['nrp']) ? $_SESSION['nrp'] : '';

    $section = isset($_GET['section']) ? mysqli_real_escape_string($link, $_GET['section']) : '0';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($link, $_GET['status']) : '0';
    $category = isset($_GET['category']) ? mysqli_real_escape_string($link, $_GET['category']) : '0';
    $by = isset($_GET['by']) ? mysqli_real_escape_string($link, $_GET['by']) : 'no_drf';

    // Build query berdasarkan filter (LOGIC ASLI - tidak konversi)
    if ($section == '0') {
        echo "<div class='alert alert-warning'>Please select a section first.</div>";
    } else {
        // Query ASLI seperti production
        if ($status == '0' && $category == '0') {
            $sql = "SELECT * FROM docu WHERE section='$section' AND doc_type='WI' ORDER BY $by";
        } elseif ($status != '0' && $category == '0') {
            $sql = "SELECT * FROM docu WHERE section='$section' AND doc_type='WI' AND status='$status' ORDER BY $by";
        } elseif ($status == '0' && $category != '0') {
            $sql = "SELECT * FROM docu WHERE section='$section' AND doc_type='WI' AND category='$category' ORDER BY $by";
        } else {
            $sql = "SELECT * FROM docu WHERE section='$section' AND doc_type='WI' AND status='$status' AND category='$category' ORDER BY $by";
        }
        
        $res = mysqli_query($link, $sql);
        if (!$res) {
            echo "<div class='alert alert-danger'>Query error: ". mysqli_error($link) ."</div>";
        } else {
?>
<br /><br />
<h1>Work Instruction's List For Section: <strong><?php echo htmlspecialchars($section);?></strong>
    <?php if($status != '0'): ?>
        , Status: <strong><?php echo htmlspecialchars($status); ?></strong>
    <?php endif; ?>
    <?php if($category != '0'): ?>
        , Category: <strong><?php echo htmlspecialchars($category); ?></strong>
    <?php endif; ?>
</h1>

<div class="table-responsive">
<table class="table table-hover">
<thead style="background:#00FFFF;">
<tr>
	<th>No</th>
	<th>Date</th>
	<th><a href='wi_other.php?section=<?php echo urlencode($section); ?>&by=no_doc&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category)?>&submit=Show'>No. Document</a></th>
	<th>No Rev.</th>
	<th><a href='wi_other.php?section=<?php echo urlencode($section); ?>&by=no_drf&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category)?>&submit=Show'>drf</a></th>
	<th><a href='wi_other.php?section=<?php echo urlencode($section); ?>&by=title&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category)?>&submit=Show'>Title</a></th>
	<th>Process</th>
	<th>Section</th>
	<th>Action</th>
	<th>Sosialisasi</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
while($info = mysqli_fetch_assoc($res)) { 
    // periksa apakah ada bukti sosialisasi
    $has_sos = !empty($info['sos_file']);
    
    // tentukan folder tempat file
    if ($info['no_drf'] > 12800) {
        $tempat = $info['doc_type'];
    } else {
        $tempat = 'document';
    }
?>
<tr>
	<td><?php echo $i; ?></td>
	<td><?php echo htmlspecialchars($info['tgl_upload']);?></td>
	<td><?php echo htmlspecialchars($info['no_doc']);?></td>
	<td><?php echo htmlspecialchars($info['no_rev']);?></td>
	<td><?php echo htmlspecialchars($info['no_drf']);?></td>
	<td>
		<a href="<?php echo htmlspecialchars($tempat . '/' . $info['file']); ?>">
			<?php echo htmlspecialchars($info['title']);?>
		</a>
	</td>
	<td><?php echo htmlspecialchars($info['process']);?></td>
	<td><?php echo htmlspecialchars($info['section']);?></td>
	
	<!-- Action -->
	<td style="white-space:nowrap;">
		<!-- Tombol yang bisa diakses semua user -->
		<a href="detail.php?drf=<?php echo urlencode($info['no_drf']);?>&no_doc=<?php echo urlencode($info['no_doc']);?>" 
		   class="btn btn-xs btn-info" title="lihat detail">
			<span class="glyphicon glyphicon-search"></span>
		</a>
		
		<a href="lihat_approver.php?drf=<?php echo urlencode($info['no_drf']);?>" 
		   class="btn btn-xs btn-info" title="lihat approver">
			<span class="glyphicon glyphicon-user"></span>
		</a>
		
		<a href="radf.php?drf=<?php echo urlencode($info['no_drf']);?>&section=<?php echo urlencode($info['section']);?>" 
		   class="btn btn-xs btn-info" title="lihat RADF">
			<span class="glyphicon glyphicon-eye-open"></span>
		</a>

		<?php
		// Tombol khusus Admin atau Originator
		if ($state == 'Admin' || $state == 'Originator') {
		?>
			<a href="edit_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" 
			   class="btn btn-xs btn-primary" title="Edit Doc">
				<span class="glyphicon glyphicon-pencil"></span>
			</a>
			
			<a href="del_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" 
			   class="btn btn-xs btn-danger" 
			   onClick="return confirm('Delete document <?php echo addslashes($info['no_doc']); ?>?')" 
			   title="Delete Doc">
				<span class="glyphicon glyphicon-remove"></span>
			</a>

			<!-- Tombol Secure untuk SEMUA status -->
			<a data-toggle="modal" data-target="#myModal2"
				data-id="<?php echo htmlspecialchars($info['no_drf']); ?>"
				data-lama="<?php echo htmlspecialchars($info['file']); ?>"
				data-type="<?php echo htmlspecialchars($info['doc_type']); ?>"
				data-rev="<?php echo htmlspecialchars($info['no_rev']); ?>"
				data-status="<?php echo htmlspecialchars($info['status']); ?>"
				data-tipe="<?php echo htmlspecialchars($info['category']); ?>"
				class="btn btn-xs btn-success sec-file" 
				title="Secure Document">
				<span class="glyphicon glyphicon-play"></span>
			</a>
		<?php 
		} // end if Admin or Originator
		?>
	</td>
	
	<!-- Kolom Sosialisasi -->
	<td>
		<?php if ($has_sos) { ?>
			<a href="lihat_sosialisasi.php?drf=<?php echo urlencode($info['no_drf']);?>" 
			   class="btn btn-xs btn-primary" title="Lihat Bukti Sosialisasi">
				Lihat
				<span class="glyphicon glyphicon-file"></span>
			</a>
		<?php } else { ?>
			<a href="#" 
			   class="btn btn-xs btn-success btn-upload-sos"
			   data-drf="<?php echo htmlspecialchars($info['no_drf']); ?>"
			   data-nodoc="<?php echo htmlspecialchars($info['no_doc']); ?>"
			   title="Upload Bukti Sosialisasi">
				Upload
				<span class="glyphicon glyphicon-upload"></span>
			</a>
		<?php } ?>
	</td>
</tr>
<?php 
	$i++;
} // end while
?>
</tbody>
</table>
</div>

<?php
        mysqli_free_result($res);
        } // end else query ok
    } // end else section selected
} // end if submit
?> 

<!-- Modal Secure Document -->
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModal2Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModal2Label">Secure Document</h4>
            </div>
            <div class="modal-body">
                <form name="secure_doc" method="POST" action="process.php" enctype="multipart/form-data">
					<input type="hidden" name="drf" id="drf" class="form-control" value=""/>
					<input type="hidden" name="lama" id="lama" class="form-control" value=""/>
					<input type="hidden" name="rev" id="rev" class="form-control" value=""/>
					<input type="hidden" name="type" id="type" class="form-control" value=""/>
					<input type="hidden" name="status" id="status" class="form-control" value=""/>
					<input type="hidden" name="tipe" id="tipe" class="form-control" value=""/>
					<div class="form-group">
						<label>Upload New Secured File:</label>
						<input type="file" name="baru" class="form-control" required>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
						<button type="submit" name="upload" value="Update" class="btn btn-primary" onclick="return confirm('Are you sure you want to secure this document?');">Update</button>
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
            // token CSRF sederhana
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

<!-- bootstrap.js -->
<script src="bootstrap/js/bootstrap.min.js"></script>