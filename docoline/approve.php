<?php
if(session_status() === PHP_SESSION_NONE)
{
    session_start();
}
include 'header.php';
include 'koneksi.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">



 <body>
 </br>
 </br>
 
 
 
 <h3>Approve Document</h3>
	<table>
			  <form action="" method="post" enctype="multipart/form-data">	
				<tr>
                	<td bgcolor="#E8E8E8"><span class="input-group-addon">Comment</span></td>
                	<td width="10" align="center" bgcolor="#E8E8E8">:</td>
                	<td bordercolorlight="#0000FF" bgcolor="#E8E8E8"><textarea  class="form-control" name="reason" cols="40" rows="10" wrap="physical"></textarea>
                	<td><input type='hidden' name='no_doc' id='no_doc' value='<?php echo $no_doc;?>' ></input></td>
						<td><input type='hidden' name='title' id='title' value='<?php echo $title;?>' ></input></td>
						<td><input type='hidden' name='drf' id='drf' value='<?php echo $drf;?>' ></input></td>
                    </td>
                </tr>
				  <tr>
				  <td bgcolor="#E8E8E8"></td>
               	  <td colspan="2" align="center" bgcolor="#E8E8E8">
				  <input class="btn btn-success btn-sm" type="submit" name="approve" value="Approve" /></td>
                </tr>
				</form>
	</table>			
				
 </body>
 </html>
 
 <?php
 // if ($approve)			{

if (isset($_POST['approve'])){
			//$id_doc=$_Get['drf'];
			$reason=$_POST['reason'];
		
				$tglsekarang = date('d-m-Y');
			$sql = "UPDATE rev_doc SET status ='Approved' , reason='$reason', tgl_approve='$tglsekarang' WHERE nrp='$nrp' and id_doc='$drf'" ; // Jadi UPDATE pegawai SET nama = namaYangDiinput, alamat = alamatYangDiinput WHERE id_pegawai = idNya
						// echo $sql;
						mysqli_query($link, $sql); 			// Mengeksekusi syntak UPDDate nya ..
						
					
						
						$sql1="select distinct rev_doc.* from rev_doc,docu,users where docu.no_drf=rev_doc.id_doc and id_doc=$drf";
						$res1=mysqli_query($link, $sql1);
						echo $jumlah_drf=mysqli_num_rows($res1);
						$sql2="select distinct rev_doc.* from rev_doc,docu,users where docu.no_drf=rev_doc.id_doc and id_doc=$drf and rev_doc.status='Approved'";
						$res2=mysqli_query($link, $sql2);
						echo $jumlah_add=mysqli_num_rows($res2);
						echo $a=$jumlah_drf-$jumlah_add;

						if($a==0){

						 $perintah1="update docu set status='Approved',final='$tglsekarang' where no_drf='$drf'";
						$hasil=mysqli_query($link, $perintah1);
						

			
			
				//require_once("class.smtp.php");
				//require_once("class.phpmailer.php");
				require 'PHPMailer/PHPMailerAutoload.php';
				
				$mail = new PHPMailer();

				$mail->IsSMTP();// send via SMTP
				include 'smtp.php';
				//$mail->Host     = "relay.sharp.co.jp"; // SMTP servers
				//$mail->Port = "3080"; 
				// $mail->SMTPAuth = true;// turn on SMTP authentication
				// $mail->Username = "int\gzb310003";// SMTP username
				// $mail->Password = "20132013";// SMTP password

				// pengirim
				$mail->From     = "dc_admin@ssi.sharp-world.com";
				$mail->FromName = "Admin Document Online System";

				$sql2="SELECT DISTINCT users.name, users.email 
				FROM  users WHERE users.state = 'admin' OR users.state = 'PIC'";

			$res2=mysqli_query($link, $sql2)or die(mysqli_error());  
			mysqli_num_rows($res2);

			$sql3="SELECT DISTINCT users.name, users.email FROM docu,users WHERE (docu.no_drf ='$drf' AND users.username = docu.user_id)";
			$res3=mysqli_query($link, $sql3)or die(mysqli_error());  
			mysqli_num_rows($res3);

			if (!empty($res2)){	 

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
				$mail->Subject  = "Document Approved" ;
				$mail->Body     =  "Attention Mr./Mrs. : Originator <br /> This following Document was <span style='color:green'>APPROVED</span> 
				by all reviewer <br /> No. Document : ".$no_doc."<br /> Title : ".$title."<br />Please Login into <a href='192.168.132.15/document'>Document Online System</a>, Thank You";
				//$mail->AltBody  =  "This research is supported by MIS";

				if(!$mail->Send())
				{
			   		echo "Message was not sent <p>";
			   		echo "Mailer Error: " . $mail->ErrorInfo;
			   		echo $sql2;
   					exit;
				}
			
			
			
		}
	}										
				?>
						<script language='javascript'>
							alert('Document was approved');
							document.location='my_doc.php?tipe=<?php echo $tipe?>&submit=Show';
						</script>
						
						
				<?php
			}		
 
  
//include 'footer.php';


?>
