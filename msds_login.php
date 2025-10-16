<?php
include 'header.php';
include 'koneksi.php';

// Ambil variabel dari session dengan aman
$state = $_SESSION['state'] ?? '';
$nrp   = $_SESSION['nrp'] ?? '';

// Atur error reporting untuk menampilkan semua masalah saat pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>MSDS Document</title>
<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>

<style>
.btn-upload-sos { margin-left:6px; }
.modal .form-control { margin-bottom:10px; }
</style>

<script type="text/javascript">
$(document).ready(function() {
    $('#wait_1').hide();
    $('#section').change(function(){
        $('#wait_1').show();
        $('#result_1').hide();
        $.get("func.php", {
            func: "section",
            drop_var: $(this).val()
        }, function(response){
            $('#result_1').fadeOut(400, function() {
                finishAjax1('result_1', response);
            });
        });
        return false;
    });

    // Modal Secure Document data binding
    $(document).on('click', '.sec-file', function(e) {
        e.preventDefault();
        var drf     = $(this).data('id') || '';
        var lama    = $(this).data('lama') || '';
        var type    = $(this).data('type') || '';
        var rev     = $(this).data('rev') || '';
        var status  = $(this).data('status') || '';
        var tipe    = $(this).data('tipe') || '';

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

function finishAjax1(id, response) {
    $('#wait_1').hide();
    $('#' + id).html(response).fadeIn();

    $('#device').change(function(){
        $('#wait_2').show();
        $('#result_2').hide();
        $.get("func3.php", {
            func: "device",
            drop_var2: $(this).val()
        }, function(response){
            $('#result_2').fadeOut(400, function() {
                finishAjax2('result_2', response);
            });
        });
        return false;
    });
}

function finishAjax2(id, response) {
    $('#wait_2').hide();
    $('#' + id).html(response).fadeIn();
}
</script>
</head>
<body>
<br />
<br />
<div class="row">
    <div class="col-xs-4 well well-lg">
        <h2>Select Device & Process For MSDS Document</h2>
        <form action="" method="GET">
            <table>
                <tr>
                    <td>Section</td>
                    <td>:</td>
                    <td>
                        <?php include('func.php'); ?>
                        <select name="section" id="section" class="form-control">
                            <option value="" selected="selected">Select Section</option>
                            <?php getTierOne(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Device</td>
                    <td>:</td>
                    <td>
                        <span id="wait_1" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_1" style="display: none;"></span>
                    </td>
                </tr>
                <tr>
                    <td>Process</td>
                    <td>:</td>
                    <td>
                        <span id="wait_2" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_2" style="display: none;"></span>
                    </td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>:</td>
                    <td>
                        <select name="status" class="form-control">
                            <option value=""> --- Select Status --- </option>
                            <option value="Secured" selected> Approved </option>
                            <option value="Review"> Review </option>
                            <option value="Pending"> Pending </option>
                            <option value="Obsolate"> Obsolate </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Category</td>
                    <td>:</td>
                    <td>
                        <select name="cat" class="form-control">
                            <option value=""> --- Select Category --- </option>
                            <option value="Internal" selected> Internal </option>
                            <option value="External"> External </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>
                        <input type='hidden' name='by' value='no_drf'>
                        <input type="submit" value="Show" name="submit" class="btn btn-info">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php
if (isset($_GET['submit'])) {
    $dev = isset($_GET['device']) ? mysqli_real_escape_string($link, $_GET['device']) : '';
    $proc = isset($_GET['proc']) ? mysqli_real_escape_string($link, $_GET['proc']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($link, $_GET['status']) : '';
    $cat = isset($_GET['cat']) ? mysqli_real_escape_string($link, $_GET['cat']) : '';

    $orderByOptions = ['no_drf', 'No_doc', 'title'];
    $by = isset($_GET['by']) && in_array($_GET['by'], $orderByOptions) ? $_GET['by'] : 'no_drf';

    if (!empty($dev)) {
        $sql = "SELECT * FROM docu WHERE device='$dev' AND doc_type='MSDS' AND status='$status' AND category='$cat' ORDER BY `$by`";
    } else {
        $sql = "SELECT * FROM docu WHERE doc_type='MSDS' AND status='$status' AND category='$cat' ORDER BY `$by`";
    }
    
    $res = mysqli_query($link, $sql);
    if (!$res) {
        die("Query Gagal: " . mysqli_error($link));
    }
?>
<table class="table table-hover">
    <h1>MSDS's List For Device: <strong><?php echo htmlspecialchars($dev);?></strong>, Process: <strong><?php echo htmlspecialchars($proc); ?></strong>, Category: <strong><?php echo htmlspecialchars($cat); ?></strong></h1>
    <thead style="background:#00FFFF;">
        <tr>
            <th>No</th>
            <th>Date</th>
            <th><a href='msds_login.php?device=<?php echo urlencode($dev); ?>&by=No_doc&status=<?php echo urlencode($status); ?>&proc=<?php echo urlencode($proc);?>&cat=<?php echo urlencode($cat); ?>&submit=Show'>No. Document</a></th>
            <th>No Rev.</th>
            <th><a href='msds_login.php?device=<?php echo urlencode($dev); ?>&by=no_drf&status=<?php echo urlencode($status); ?>&proc=<?php echo urlencode($proc);?>&cat=<?php echo urlencode($cat); ?>&submit=Show'>DRF</a></th>
            <th><a href='msds_login.php?device=<?php echo urlencode($dev); ?>&by=title&status=<?php echo urlencode($status); ?>&proc=<?php echo urlencode($proc);?>&cat=<?php echo urlencode($cat); ?>&submit=Show'>Title</a></th>
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
        $has_sos = !empty($info['sos_file']);
    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo htmlspecialchars($info['tgl_upload']);?></td>
            <td><?php echo htmlspecialchars($info['no_doc']);?></td>
            <td><?php echo htmlspecialchars($info['no_rev']);?></td>
            <td><?php echo htmlspecialchars($info['no_drf']);?></td>
            <td>
                <?php $tempat = ($info['no_drf'] > 12967) ? $info['doc_type'] : 'document'; ?>
                <a href="<?php echo htmlspecialchars($tempat); ?>/<?php echo htmlspecialchars($info['file']); ?>">
                    <?php echo htmlspecialchars($info['title']);?>
                </a>
            </td>
            <td><?php echo htmlspecialchars($info['process']);?></td>
            <td><?php echo htmlspecialchars($info['section']);?></td>
            <td style="white-space:nowrap;">
                <a href="detail.php?drf=<?php echo urlencode($info['no_drf']);?>&no_doc=<?php echo urlencode($info['no_doc']);?>" class="btn btn-xs btn-info" title="lihat detail"><span class="glyphicon glyphicon-search"></span></a>
                <a href="lihat_approver.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-info" title="lihat approver"><span class="glyphicon glyphicon-user"></span></a>
                <a href="radf.php?drf=<?php echo urlencode($info['no_drf']);?>&section=<?php echo urlencode($info['section']);?>" class="btn btn-xs btn-info" title="lihat RADF"><span class="glyphicon glyphicon-eye-open"></span></a>
                <?php if ($state == 'Admin' || ($state == "Originator" && $info['user_id'] == $nrp)) { ?>
                    <a href="edit_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-primary" title="Edit Doc"><span class="glyphicon glyphicon-pencil"></span></a>
                    <a href="del_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" class="btn btn-xs btn-danger" onClick="return confirm('Delete document <?php echo addslashes($info['no_doc']);?>?')" title="Delete Doc"><span class="glyphicon glyphicon-remove"></span></a>
                    <?php if ($info['status'] == 'Secured') { ?>
                        <a data-toggle="modal" data-target="#myModal2" 
                           data-id="<?php echo htmlspecialchars($info['no_drf']);?>" 
                           data-lama="<?php echo htmlspecialchars($info['file']);?>" 
                           data-type="<?php echo htmlspecialchars($info['doc_type']);?>"
                           data-rev="<?php echo htmlspecialchars($info['no_rev']);?>"
                           data-status="<?php echo htmlspecialchars($info['status']);?>" 
                           data-tipe="<?php echo htmlspecialchars($info['category']);?>" 
                           class="btn btn-xs btn-success sec-file" title="Secure Document">
                            <span class="glyphicon glyphicon-play"></span>
                        </a>
                    <?php } ?>
                <?php } ?>
            </td>
            
            <!-- Kolom Sosialisasi -->
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
    <?php
        $i++;
    }
    ?>
    </tbody>
</table>
<?php
}
?>

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
</body>
</html>