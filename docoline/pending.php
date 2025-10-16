<?php
// Tambalan untuk eregi()
if (!function_exists('eregi')) {
    function eregi($pattern, $string) {
        return preg_match('/' . preg_quote($pattern, '/') . '/i', $string);
    }
}

// ================================================================
// TAMBALAN BARU UNTUK FUNGsi each() - TAMBAHKAN BLOK INI
// ================================================================
if (!function_exists('each')) {
    function each(&$array) {
        $key = key($array);
        if ($key === null) {
            return false;
        }
        $value = current($array);
        next($array);
        return [$key, $value, 'key' => $key, 'value' => $value, 1 => $value, 0 => $key];
    }
}
// ================================================================

include 'header.php';
include 'koneksi.php';

// Tangkap 'drf' dan variabel lain dari URL (GET)
$drf = isset($_GET['drf']) ? (int)$_GET['drf'] : 0;
$no_doc = isset($_GET['no_doc']) ? $_GET['no_doc'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Tambahkan validasi sederhana
if ($drf == 0) {
    die("Error: DRF tidak valid atau tidak ditemukan.");
}

// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); // Sebaiknya jangan sembunyikan semua error saat development
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">



 <body>
 </br>
 </br>
 </br>
 </br>

 
 
 <h3>Suspend/Revise Document</h3>
	<table>
			  <form action="" method="post" enctype="multipart/form-data">	
				<tr>
                	<td bgcolor="#E8E8E8"><span class="input-group-addon">Reason</span></td>
                	<td width="10" align="center" bgcolor="#E8E8E8">:</td>
                	<td bordercolorlight="#0000FF" bgcolor="#E8E8E8"><textarea  class="form-control" name="reason" cols="40" rows="10" wrap="physical"></textarea>
					
                	<td>
						<input type='hidden' name='drf' value='<?php echo htmlspecialchars($drf); ?>' />
						<input type='hidden' name='no_doc' id='no_doc' value='<?php echo $no_doc;?>' ></input></td>
						<td><input type='hidden' name='type' id='type' value='<?php echo $type;?>' ></input></td>
						<td><input type='hidden' name='name_app' id='name_app' value='<?php echo $name;?>' ></input></td>
                    </td>
                </tr>
				  <tr>
				  <td bgcolor="#E8E8E8"></td>
               	  <td colspan="2" align="center" bgcolor="#E8E8E8">
				  <input class="btn btn-warning btn-sm" type="submit" name="submit" value="Suspend" /></td>
                </tr>
				</form>
	</table>			
				
 </body>
 </html>
 
 <?php
if (isset($_POST['submit'])){

			$id_doc=$_POST['drf'];
			$reason=$_POST['reason'];
			$name_app=$_POST['name_app'];
				extract($_REQUEST);
		
				$tglsekarang = date('d-m-Y');
			$sql = "UPDATE rev_doc SET status ='Pending' , reason='$reason', tgl_approve='$tglsekarang' WHERE nrp='$nrp' and id_doc='$drf'" ; // Jadi UPDATE pegawai SET nama = namaYangDiinput, alamat = alamatYangDiinput WHERE id_pegawai = idNya
						echo $sql;
						mysqli_query($link, $sql); 			// Mengeksekusi syntak UPDDate nya ..
						
					
						
			$perintah1="update docu set status='Pending' where no_drf='$drf'";
						$hasil=mysqli_query($link, $perintah1);		

					
				require_once("class.smtp.php");
				require_once("class.phpmailer.php");

				$mail = new PHPMailer();

				$mail->IsSMTP();// send via SMTP
				include 'smtp.php';
				// $mail->Host     = "relay.sharp.co.jp"; // SMTP servers
				//$mail->Port = "3080"; 
				// $mail->SMTPAuth = true;// turn on SMTP authentication
				// $mail->Username = "int\gzb310003";// SMTP username
				// $mail->Password = "20132013";// SMTP password

				// pengirim
				$mail->From     = "dc_admin@ssi.sharp-world.com";
				$mail->FromName = "Admin Document Online System";

				$sql2="SELECT DISTINCT users.name, users.email FROM rev_doc, users WHERE (rev_doc.id_doc ='$drf' AND users.username = rev_doc.nrp) OR users.state = 'admin'";
			$res2=mysqli_query($link, $sql2)or die(mysqli_error());  
			mysqli_num_rows($res2);

			$sql3="SELECT DISTINCT users.name, users.email FROM docu,users WHERE (docu.no_drf ='$drf' AND users.username = docu.user_id)";
			$res3=mysqli_query($link, $sql3)or die(mysqli_error());  
			mysqli_num_rows($res3);

			if (mysqli_num_rows($res2) > 0 || mysqli_num_rows($res3) > 0) {	 

			while ($data2=mysqli_fetch_row($res2))
			{


				// penerima
				 $mail->AddAddress($data2[1],$data2[0]);

				}

				while ($data3=mysqli_fetch_row($res3))
			{


				// penerima
				 $mail->AddAddress($data3[1],$data3[0]);

				}
				
				$mail->WordWrap = 50;                              // set word wrap
				//$mail->AddAttachment(getcwd()."/document/".$file_name);      // attachment
	//$mail->AddAttachment(getcwd() . "/file2.zip", "file_kedua.zip");
				$mail->IsHTML(true);                               // send as HTML
			
				//$link="http://www.ssi.global.sharp.co.jp/AglSystemOpto/";
				$mail->Subject  = "Document Suspended" ;
				$mail->Body     =  "Attention Mr./Mrs. : Originator <br /> This following <span style='color:green'>".$type."</span>
				 Document need to be <span style='color:orange'>REVISED</span> <br /> No. Document : ".$no_doc."<br /> Reason : ".$reason."<br />
				  Suspended by : ".$name_app."<br />
				 Please Login into <a href='192.168.132.15/document'>Document Online System</a>, Thank You";
				//$mail->AltBody  =  "This research is supported by MIS";

				if(!$mail->Send())
				{
			   		echo "Message was not sent <p>";
			   		echo "Mailer Error: " . $mail->ErrorInfo;
			   		echo $sql2;
   					exit;
				}
			
			
			
		}									
				?>
						<script language='javascript'>
							alert('Document Suspended');
							document.location='index_login.php';
						</script>
						<?php				
			 }
			?>
