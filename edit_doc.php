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
// ============================================================================
// SECTION: HANDLE FORM SUBMISSION - FIXED WORKFLOW
// ============================================================================
if (isset($_POST['submit'])) {

    include 'koneksi.php';

    // Ambil drf dari POST
    $drf = mysqli_real_escape_string($link, $_POST['drf']);

    // ========================================================================
    // STEP 1: Cek Status Dokumen Saat Ini
    // ========================================================================
    $checkStatusQuery = "SELECT status FROM docu WHERE no_drf='$drf'";
    $checkResult = mysqli_query($link, $checkStatusQuery);
    
    if (!$checkResult) {
        die("Error checking status: " . mysqli_error($link));
    }
    
    $currentDoc = mysqli_fetch_assoc($checkResult);
    $currentStatus = $currentDoc['status'];

    // ========================================================================
    // STEP 2: Tentukan Status Baru Berdasarkan Logic Workflow
    // ========================================================================
    // Jika dokumen Pending (suspended), kembalikan ke Review
    // Jika bukan Pending, biarkan status tetap
    $newStatus = ($currentStatus === 'Pending') ? 'Review' : $currentStatus;

    // ========================================================================
    // STEP 3: Escape Semua Input untuk Mencegah SQL Injection
    // ========================================================================
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

    // ========================================================================
    // STEP 4: Update Tabel DOCU dengan Status Baru
    // ========================================================================
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
            history='$hist',
            status='$newStatus'
            WHERE no_drf='$drf'";

    $res = mysqli_query($link, $sql);

    if (!$res) {
        die("Update failed: " . mysqli_error($link));
    }

    // ========================================================================
    // STEP 5: Update Related Documents
    // ========================================================================
    // Hapus related documents yang lama terlebih dahulu
    mysqli_query($link, "DELETE FROM rel_doc WHERE no_drf='$drf'");

    // Insert related documents yang baru
    if (isset($_POST["rel"]) && is_array($_POST["rel"])) {
        foreach($_POST["rel"] as $no_doc) {
            // Trim whitespace dan cek apakah tidak kosong
            $no_doc = trim($no_doc);
            
            if (!empty($no_doc)) {
                $no_doc_escaped = mysqli_real_escape_string($link, $no_doc);
                
                // Insert related document
                $q = mysqli_query($link, "INSERT INTO rel_doc(no_drf, no_doc) VALUES ('$drf', '$no_doc_escaped')"); 
                
                if (!$q) {
                    echo "Error inserting related doc: " . mysqli_error($link) . "<br>";
                }
            }
        }
    }

    // ========================================================================
    // STEP 6: Reset Status Approver (Jika Dokumen dari Pending ke Review)
    // ========================================================================
    if ($currentStatus === 'Pending' && $newStatus === 'Review') {
        
        // Reset semua approver agar bisa approve lagi
        $resetApproverQuery = "UPDATE rev_doc 
                              SET status='Review', 
                                  reason='', 
                                  tgl_approve=NULL
                              WHERE id_doc='$drf'";
        
        $resetResult = mysqli_query($link, $resetApproverQuery);
        
        if (!$resetResult) {
            echo "Warning: Failed to reset approver status: " . mysqli_error($link) . "<br>";
        }

        // ====================================================================
        // STEP 7: Kirim Email Notifikasi ke Approver
        // ====================================================================
        
        // Get approver information
        $getApproverQuery = "SELECT u.email, u.nama, r.nik_atasan 
                            FROM rev_doc r 
                            JOIN userak u ON r.nik_atasan = u.nik 
                            WHERE r.id_doc='$drf' 
                            LIMIT 1";
        
        $approverResult = mysqli_query($link, $getApproverQuery);
        
        if ($approverResult && mysqli_num_rows($approverResult) > 0) {
            $approverData = mysqli_fetch_assoc($approverResult);
            $approverEmail = $approverData['email'];
            $approverName = $approverData['nama'];
            
            // Cek apakah file PHPMailer ada
            if (file_exists('class.phpmailer.php') && file_exists('class.smtp.php')) {
                
                require_once('class.phpmailer.php');
                require_once('class.smtp.php');
                
                try {
                    $mail = new PHPMailer();
                    $mail->IsSMTP();
                    $mail->SMTPAuth = true;
                    
                    // ============================================================
                    // KONFIGURASI EMAIL - SESUAIKAN DENGAN SMTP ANDA
                    // ============================================================
                    $mail->Host = 'smtp.gmail.com';              // SMTP server
                    $mail->Port = 587;                           // SMTP port
                    $mail->Username = 'your-email@gmail.com';    // Email pengirim
                    $mail->Password = 'your-app-password';       // Password/App Password
                    $mail->SMTPSecure = 'tls';                   // Enkripsi (tls/ssl)
                    
                    $mail->SetFrom('noreply@company.com', 'Document Management System');
                    $mail->AddAddress($approverEmail, $approverName);
                    
                    $mail->Subject = "Dokumen Telah Direvisi - Perlu Review Ulang";
                    
                    // Email body HTML
                    $emailBody = "
                    <html>
                    <head>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                line-height: 1.6;
                                color: #333;
                            }
                            .container { 
                                max-width: 600px; 
                                margin: 0 auto; 
                                padding: 20px; 
                            }
                            .header { 
                                background: #4CAF50; 
                                color: white; 
                                padding: 20px; 
                                text-align: center; 
                                border-radius: 5px 5px 0 0;
                            }
                            .content { 
                                padding: 30px; 
                                background: #f9f9f9; 
                                border: 1px solid #ddd;
                            }
                            .button { 
                                display: inline-block; 
                                padding: 12px 30px; 
                                background: #4CAF50; 
                                color: white !important; 
                                text-decoration: none; 
                                border-radius: 5px; 
                                margin-top: 20px;
                                font-weight: bold;
                            }
                            .info { 
                                background: white; 
                                padding: 20px; 
                                margin: 20px 0; 
                                border-left: 4px solid #4CAF50; 
                                border-radius: 3px;
                            }
                            .info-row {
                                padding: 8px 0;
                                border-bottom: 1px solid #eee;
                            }
                            .info-row:last-child {
                                border-bottom: none;
                            }
                            .label {
                                font-weight: bold;
                                color: #555;
                                display: inline-block;
                                width: 150px;
                            }
                            .value {
                                color: #333;
                            }
                            .footer {
                                margin-top: 20px;
                                padding-top: 20px;
                                border-top: 1px solid #ddd;
                                color: #666;
                                font-size: 12px;
                                text-align: center;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2 style='margin: 0;'>📝 Dokumen Telah Direvisi</h2>
                            </div>
                            <div class='content'>
                                <p>Halo <strong>$approverName</strong>,</p>
                                
                                <p>Dokumen yang sebelumnya Anda suspend telah direvisi oleh originator dan memerlukan <strong>review ulang</strong> dari Anda.</p>
                                
                                <div class='info'>
                                    <h3 style='margin-top: 0; color: #4CAF50;'>Detail Dokumen:</h3>
                                    <div class='info-row'>
                                        <span class='label'>No DRF:</span>
                                        <span class='value'><strong>$drf</strong></span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='label'>No. Document:</span>
                                        <span class='value'>$nodoc</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='label'>Title:</span>
                                        <span class='value'>$title</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='label'>Document Type:</span>
                                        <span class='value'>$type</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='label'>Status:</span>
                                        <span class='value'><strong style='color: #FF9800;'>Review (Menunggu Approval Anda)</strong></span>
                                    </div>
                                </div>
                                
                                <p style='margin-top: 25px;'>Silakan login ke sistem untuk melakukan review dan approval:</p>
                                
                                <div style='text-align: center;'>
                                    <a href='http://yoursite.com/approval.php' class='button'>Review Dokumen Sekarang</a>
                                </div>
                                
                                <div class='footer'>
                                    <p>Email ini dikirim otomatis oleh Document Management System.<br>
                                    Mohon tidak membalas email ini.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $mail->MsgHTML($emailBody);
                    
                    // Send email (suppress errors agar tidak mengganggu flow)
                    @$mail->Send();
                    
                } catch (Exception $e) {
                    // Log error tapi tetap lanjutkan proses
                    error_log("Email sending failed: " . $e->getMessage());
                }
            }
        }
    }

    // ========================================================================
    // STEP 8: Redirect dengan Notifikasi
    // ========================================================================
    if ($currentStatus === 'Pending' && $newStatus === 'Review') {
        // Jika workflow diperbaiki (Pending → Review)
        ?>
        <script language='javascript'>
            alert('✅ Document updated successfully!\n\nStatus changed from Pending to Review.\nApprover will receive notification email.');
            document.location='my_doc.php';
        </script>
        <?php
    } else {
        // Update biasa (status tidak berubah)
        ?>
        <script language='javascript'>
            alert('✅ Document updated successfully!');
            document.location='my_doc.php';
        </script>
        <?php
    }
}
?>