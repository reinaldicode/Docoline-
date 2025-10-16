<?php
// wi_prod.php
include "wi_login.php"; // pastikan file ini mem-include koneksi $link & session
extract($_REQUEST);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING| E_PARSE| E_DEPRECATED));
?>
<!-- jQuery -->
<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

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
        var status = $(this).data('status') || '';
        
        $('#myModal2 #drf').val(drf);
        $('#myModal2 #lama').val(lama);
        $('#myModal2 #status').val(status);
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
        <h2>Select Device & Process</h2>
        <form action="" method="GET">
            <table>
                <?php
                $sect="select * from device";
                $sql_sect=mysqli_query($link, $sect);
                ?>
                <tr>
                    <td>Section</td><td>:</td>
                    <td>
                        <?php include('func.php'); ?>
                        <select name="section" id="section" class="form-control">
                            <option value="" selected="selected" >Select Section</option>
                            <?php getTierOne(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Device</td><td>:</td>
                    <td>
                        <span id="wait_1" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_1" style="display: none;"></span>
                    </td>
                </tr>
                <tr>
                    <td>Process</td><td>:</td>
                    <td>
                        <span id="wait_2" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_2" style="display: none;"></span>
                    </td>
                </tr>
                <tr>
                    <td>Status</td><td>:</td>
                    <td>
                        <select name="status" class="form-control">
                            <option value="0"> --- All Status --- </option>
                            <option value="Secured" selected> Secured </option>
                            <option value="Approved"> Approved </option>
                            <option value="Review"> Review </option>
                            <option value="Pending"> Pending </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Category</td><td>:</td>
                    <td>
                        <select name="cat" class="form-control">
                            <option value="0"> --- Select Category --- </option>
                            <option value="Internal" selected> Internal </option>
                            <option value="External"> External </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td><td></td>
                    <td><input type='hidden' name='by' value='no_drf'>
                        <input type="submit" value="Show" name="submit" class="btn btn-info"></td>
                </tr>
        </form>
        </table>
    </div>
</div>

<?php
if (isset($_GET['submit'])) {
    // ambil session state
    $state = isset($_SESSION['state']) ? $_SESSION['state'] : '';
    $nrp   = isset($_SESSION['nrp']) ? $_SESSION['nrp'] : '';

    $dev = isset($_GET['device']) ? mysqli_real_escape_string($link, $_GET['device']) : '';
    $proc = isset($_GET['proc']) ? mysqli_real_escape_string($link, $_GET['proc']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($link, $_GET['status']) : '';
    $cat = isset($_GET['cat']) ? mysqli_real_escape_string($link, $_GET['cat']) : '';
    $by = isset($_GET['by']) ? mysqli_real_escape_string($link, $_GET['by']) : 'no_drf';

    if ($dev=='General PC') {
        $sql="select * from docu where no_doc like 'O-W-EGPC%' and doc_type='WI' and status='Secured' order by $by ";
    } else {
        if ($proc=='-') {
            $sql="select * from docu where device='$dev' and doc_type='WI' and status='$status' and section='Production' and category='$cat'  order by $by ";
        } else {
            $sql="select * from docu where device='$dev' and process='$proc' and doc_type='WI' and status='$status' and section='Production' and category='$cat'   order by $by";
        }
    }

    $res = mysqli_query($link, $sql);
    if (!$res) {
        echo "<div class='alert alert-danger'>Query error: ". mysqli_error($link) ."</div>";
    } else {
        ?>
        <br /><br />
        <h1> Work Instruction's List For Device: <strong><?php echo htmlspecialchars($dev);?></strong> , Process: <strong><?php echo htmlspecialchars($proc); ?></strong>, Category: <strong><?php echo htmlspecialchars($cat); ?></strong></h1>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background:#00FFFF;">
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>No. Document</th>
                        <th>No Rev.</th>
                        <th>drf</th>
                        <th>Title</th>
                        <th>Process</th>
                        <th>Section</th>
                        <th>Action</th>
                        <th>Sosialisasi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                while ($info = mysqli_fetch_assoc($res)) {
                    // periksa apakah ada bukti sosialisasi (field di docu)
                    $has_sos = !empty($info['sos_file']);
                    
                    // tentukan folder tempat file (sama seperti procedure_login.php)
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
                                    data-status="<?php echo htmlspecialchars($info['status']); ?>"
                                    class="btn btn-xs btn-success sec-file" 
                                    title="Secure Document">
                                    <span class="glyphicon glyphicon-play"></span>
                                </a>
                                <?php
                            } // end if Admin or Originator
                            ?>
                        </td>

                        <!-- Kolom Sosialisasi: disamakan dengan procedure_login.php -->
                        <td>
                            <?php if ($has_sos) { ?>
                                <a href="lihat_sosialisasi.php?drf=<?php echo urlencode($info['no_drf']); ?>" 
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
                    <input type="hidden" name="status" id="status" class="form-control" value=""/>
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

<script type="text/javascript">
$(document).ready(function() {
    $('#wait_1').hide();
    $('#section').change(function(){
        $('#wait_1').show();
        $('#result_1').hide();
        $.get("func.php", { func: "section", drop_var: $('#section').val() }, function(response){
            $('#result_1').fadeOut();
            setTimeout(function(){ finishAjax1('result_1', escape(response)); }, 400);
        });
        return false;
    });
});
function finishAjax1(id, response) {
    $('#wait_1').hide();
    $('#'+id).html(unescape(response));
    $('#'+id).fadeIn();
    $('#wait_2').hide();
    $('#device').change(function(){
        $('#wait_2').show();
        $('#result_2').hide();
        $.get("func.php", { func: "device", drop_var2: $('#device').val() }, function(response){
            $('#result_2').fadeOut();
            setTimeout(function(){ finishAjax('result_2', escape(response)); }, 400);
        });
        return false;
    });
}
function finishAjax(id, response) {
    $('#wait_2').hide();
    $('#'+id).html(unescape(response));
    $('#'+id).fadeIn();
}
</script>