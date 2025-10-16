<?php

include 'header.php';
include 'koneksi.php';
extract($_REQUEST);

/**
 * ============================================================================
 * SECTION NORMALIZATION FUNCTION
 * ============================================================================
 */
function normalizeSection($section) {
    if (empty($section) || $section === '-') {
        return $section;
    }
    
    $normalized = trim($section);
    $normalized = preg_replace('/\s+section$/i', '', $normalized);
    $normalized = preg_replace('/^section\s+/i', '', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    
    return trim($normalized);
}

/**
 * ============================================================================
 * LOAD DYNAMIC DOCUMENT TYPES FROM JSON
 * ============================================================================
 */
function loadDocumentTypes() {
    $jsonFile = __DIR__ . '/data/document_types.json';
    $docTypes = [];
    
    if (file_exists($jsonFile)) {
        $tmp = json_decode(file_get_contents($jsonFile), true);
        if (is_array($tmp)) {
            // Flatten categorized structure
            $is_assoc = array_keys($tmp) !== range(0, count($tmp)-1);
            if ($is_assoc) {
                foreach ($tmp as $catk => $items) {
                    if (is_array($items)) {
                        foreach ($items as $it) {
                            if (is_string($it)) $docTypes[] = $it;
                            elseif (is_array($it) && isset($it['name'])) $docTypes[] = $it['name'];
                        }
                    }
                }
            } else {
                foreach ($tmp as $it) {
                    if (is_string($it)) $docTypes[] = $it;
                    elseif (is_array($it) && isset($it['name'])) $docTypes[] = $it['name'];
                }
            }
            // Remove duplicates while preserving order
            $docTypes = array_unique($docTypes);
        }
    }
    
    // Fallback to default types if JSON not available
    if (empty($docTypes)) {
        $docTypes = ['WI', 'Procedure', 'Form', 'Monitor Sample', 'MSDS', 'Material Spec', 'ROHS'];
    }
    
    return $docTypes;
}

/**
 * ============================================================================
 * FUNGSI UNTUK MENGHITUNG NOTIFIKASI PER TIPE DOKUMEN
 * ============================================================================
 */
function getNotificationCounts($link, $nrp, $state, $docTypes) {
    $counts = ['total' => 0];
    
    // Initialize counts for all document types
    foreach($docTypes as $type) {
        $counts[$type] = 0;
    }
    
    foreach($docTypes as $type) {
        $type_escaped = mysqli_real_escape_string($link, $type);
        
        if($state == 'Admin') {
            $sql = "SELECT COUNT(*) as count FROM docu WHERE doc_type='$type_escaped' AND 
                    status IN ('Review', 'Pending')";
        } elseif($state == 'Originator') {
            $sql = "SELECT COUNT(*) as count FROM docu WHERE doc_type='$type_escaped' AND 
                    user_id='$nrp' AND status IN ('Review', 'Pending')";
        } elseif($state == 'Approver') {
            $sql = "SELECT COUNT(DISTINCT docu.no_drf) as count 
                    FROM docu
                    INNER JOIN rev_doc ON docu.no_drf=rev_doc.id_doc
                    WHERE docu.doc_type='$type_escaped' 
                    AND docu.status='Review' 
                    AND rev_doc.status='Review' 
                    AND rev_doc.nrp='$nrp'";
        }
        
        $result = mysqli_query($link, $sql);
        $row = mysqli_fetch_assoc($result);
        $counts[$type] = (int)$row['count'];
        $counts['total'] += $counts[$type];
    }
    
    return $counts;
}

/**
 * ============================================================================
 * FUNGSI UNTUK MENDAPATKAN NOTIFIKASI TERBARU
 * ============================================================================
 */
function getRecentNotifications($link, $nrp, $state, $limit = 10) {
    $notifications = array();
    
    if($state == 'Admin') {
        $sql = "SELECT no_drf, no_doc, title, doc_type, status, tgl_upload, 
                DATEDIFF(NOW(), tgl_upload) as days_passed
                FROM docu WHERE status IN ('Review', 'Pending') 
                ORDER BY tgl_upload DESC LIMIT $limit";
    } elseif($state == 'Originator') {
        $sql = "SELECT no_drf, no_doc, title, doc_type, status, tgl_upload,
                DATEDIFF(NOW(), tgl_upload) as days_passed
                FROM docu WHERE user_id='$nrp' AND status IN ('Review', 'Pending')
                ORDER BY tgl_upload DESC LIMIT $limit";
    } elseif($state == 'Approver') {
        $sql = "SELECT DISTINCT docu.no_drf, docu.no_doc, docu.title, docu.doc_type, 
                docu.status, docu.tgl_upload,
                DATEDIFF(NOW(), docu.tgl_upload) as days_passed
                FROM docu
                INNER JOIN rev_doc ON docu.no_drf=rev_doc.id_doc
                WHERE docu.status='Review' 
                AND rev_doc.status='Review' 
                AND rev_doc.nrp='$nrp'
                GROUP BY docu.no_drf
                ORDER BY docu.tgl_upload DESC 
                LIMIT $limit";
    }
    
    $result = mysqli_query($link, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// Load dynamic document types
$docTypes = loadDocumentTypes();

// Ambil data notifikasi
$notifCounts = getNotificationCounts($link, $nrp, $state, $docTypes);
$recentNotifications = getRecentNotifications($link, $nrp, $state);

?>

<style>
.notification-badge {
    position: relative;
    display: inline-block;
}

.notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    animation: pulse 2s infinite;
    z-index: 1000;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-panel {
    position: fixed;
    top: 70px;
    right: 20px;
    width: 350px;
    max-height: 400px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1050;
    display: none;
    border: 1px solid #dee2e6;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.notification-body {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f3f4;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.urgent {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}

.notification-item.overdue {
    background-color: #f8d7da;
    border-left: 3px solid #dc3545;
}

.notification-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.summary-item:last-child {
    margin-bottom: 0;
}

.summary-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.summary-link {
    text-decoration: none;
    color: white;
    display: block;
}

.summary-link:hover {
    color: white;
    text-decoration: none;
}

.dropdown-with-badge {
    position: relative;
}

.dropdown-badge {
    position: absolute;
    top: 5px;
    right: 25px;
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
    z-index: 10;
}

.auto-refresh-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12px;
    display: none;
}
</style>

<div id="profile">
<div class="alert alert-info" role="alert">
    <b id="welcome">Welcome : <i><?php echo htmlspecialchars($name); ?>, anda login sebagai <?php echo htmlspecialchars($state);?></i></b>
    
    <!-- Notification Bell -->
    <div style="float: right;">
        <button class="btn btn-outline-primary notification-badge" id="notificationBell" title="Notifications">
            <span class="glyphicon glyphicon-bell"></span>
            <?php if($notifCounts['total'] > 0): ?>
            <span class="notification-count" id="totalNotifications"><?php echo $notifCounts['total']; ?></span>
            <?php endif; ?>
        </button>
    </div>
</div>
</div>

<!-- Notification Summary Dashboard (DYNAMIC - Clickable) -->
<?php if($notifCounts['total'] > 0): ?>
<div class="notification-summary">
    <h5><span class="glyphicon glyphicon-dashboard"></span> Notification Summary - Click to Filter</h5>

    <?php foreach($docTypes as $type): ?>
        <?php if($notifCounts[$type] > 0): ?>
        <a href="?tipe=<?php echo urlencode($type); ?>&submit=Show<?php echo ($state == 'Admin' && isset($status)) ? '&status='.$status : ''; ?>" 
           class="summary-link" 
           title="Show <?php echo htmlspecialchars($type); ?> Documents">
          <div class="summary-item">
              <span><span class="glyphicon glyphicon-file"></span> <?php echo htmlspecialchars($type); ?></span>
              <span class="badge" style="background: white; color: #333;"><?php echo $notifCounts[$type]; ?></span>
          </div>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Notification Panel -->
<div class="notification-panel" id="notificationPanel">
    <div class="notification-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h6 style="margin: 0;"><span class="glyphicon glyphicon-bell"></span> Recent Updates</h6>
            <button class="btn btn-xs btn-default" onclick="$('#notificationPanel').fadeOut();">×</button>
        </div>
    </div>
    <div class="notification-body" id="notificationBody">
        <?php if(empty($recentNotifications)): ?>
        <div class="notification-item">
            <small class="text-muted">No recent notifications</small>
        </div>
        <?php else: ?>
        <?php foreach($recentNotifications as $notif): ?>
        <div class="notification-item <?php echo ($notif['days_passed'] >= 3) ? 'overdue' : (($notif['days_passed'] >= 1) ? 'urgent' : ''); ?>" 
             onclick="window.location.href='detail.php?drf=<?php echo $notif['no_drf']; ?>&no_doc=<?php echo urlencode($notif['no_doc']); ?>'">
            <div style="display: flex; justify-content: space-between;">
                <strong><?php echo htmlspecialchars($notif['doc_type']); ?> - <?php echo htmlspecialchars($notif['status']); ?></strong>
                <small class="text-muted"><?php echo $notif['days_passed']; ?> days ago</small>
            </div>
            <small><?php echo htmlspecialchars($notif['no_doc']); ?>: <?php echo htmlspecialchars(substr($notif['title'], 0, 50)); ?>...</small>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $('.upload-file').click(function () {
        $('span.user-id').text($(this).data('id'));
        var Id = $(this).data('id');
     $(".modal-body #drf").val( Id );
     
     var nodoc = $(this).data('nodoc');
     $(".modal-body #nodoc").val( nodoc );

     var type = $(this).data('type');
     $(".modal-body #type").val( type );

      var lama = $(this).data('lama');
     $(".modal-body #lama").val( lama );

     var title = $(this).data('title');
     $(".modal-body #title").val( title );
    });

    $('.sec-file').click(function () {
        $('span.user-id').text($(this).data('id'));
        var Id = $(this).data('id');
     $(".modal-body #drf").val( Id );
     
     var lama = $(this).data('lama');
     $(".modal-body #lama").val( lama );

      var type = $(this).data('type');
     $(".modal-body #type").val( type );

      var rev = $(this).data('rev');
     $(".modal-body #rev").val( rev );

     var status = $(this).data('status');
     $(".modal-body #status").val( status );
    });

    // Notification System
    $('#notificationBell').click(function(e) {
        e.preventDefault();
        $('#notificationPanel').fadeToggle();
    });

    // Close notification panel when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#notificationPanel, #notificationBell').length) {
            $('#notificationPanel').fadeOut();
        }
    });
});
</script>

<h2>Manage Document</h2>

<form action="" method="GET" >
    <div class="col-sm-4">
        <div class="dropdown-with-badge">
            <select name="tipe" class="form-control">
                <option value="-">--- Select Type ---</option>
                <?php foreach($docTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($tipe) && $tipe == $type) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type); ?> <?php echo ($notifCounts[$type] > 0) ? '(' . $notifCounts[$type] . ' new)' : ''; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($state=='Admin'){ ?>
        <select name="status" class="form-control">
            <option value="-">--- Select Status ---</option>
            <option <?php echo (isset($status) && $status == 'Review') ? 'selected' : 'selected'; ?> value="Review">Review</option>
            <option <?php echo (isset($status) && $status == 'Pending') ? 'selected' : ''; ?> value="Pending">Pending</option>
            <option <?php echo (isset($status) && $status == 'Approved') ? 'selected' : ''; ?> value="Approved">Approved</option>
        </select>           
        <?php } ?>

     <input type="submit" name="submit" value="Show" class="btn btn-primary" />
    </div>
        <br />
            <br />
                <br />
</form>

<!-- Auto Refresh Indicator -->
<div class="auto-refresh-indicator" id="refreshIndicator">
    <span class="glyphicon glyphicon-refresh"></span> Checking for updates...
</div>

<?php

if(isset($_GET['submit']))
{
 $tipe=$_GET['tipe'];

if($tipe=="-"){
    $sort="";
}else{
    $tipe_escaped = mysqli_real_escape_string($link, $tipe);
    $sort="and doc_type='$tipe_escaped'";
}

if($state=='Admin') {
    $sql="select * from docu where status='$status' $sort order by no_drf";
}
elseif($state=='Originator') {
    $sql="select * from docu where (docu.status='Review' or docu.status='Pending') $sort and user_id='$nrp' order by no_drf";
}
elseif($state=='Approver') {
    $sql="SELECT DISTINCT docu.* 
          FROM docu
          INNER JOIN rev_doc ON docu.no_drf=rev_doc.id_doc
          WHERE docu.status='Review' 
          AND rev_doc.status='Review' 
          AND rev_doc.nrp='$nrp' 
          $sort 
          ORDER BY docu.no_drf";
}

$res=mysqli_query($link, $sql);
?>

<table class="table table-hover">
<thead bgcolor="#00FFFF">
<tr>
    <td>No</td>
    <td>Date</td>
    <td>No. Drf</td>
    <td>No Document</td>
    <td>No Rev.</td>
    <td>Title</td>
    <td>Process</td>
    <td>Section</td>
    <td>Type</td>
    <td>Review To</td>
    <td>Pass Day</td>
    <td>Status</td>
    <td>Action</td>
</tr>
</thead>
<tbody>
<?php
$j=1;
while($info = mysqli_fetch_array($res)) 
{ ?>

<tr <?php 
    $tglsekarang = date('d-m-Y');
    $tglissue  =$info['tgl_upload'];
    $pecah1 = explode("-", $tglissue );
    $date1 = $pecah1[0];
    $month1 = $pecah1[1];
    $year1 = $pecah1[2];
    $pecah2 = explode("-", $tglsekarang);
    $date2 = $pecah2[0];
    $month2 = $pecah2[1];
    $year2 =  $pecah2[2];
    $waktusekarang = GregorianToJD($month1, $date1, $year1);
    $waktuinput = GregorianToJD($month2, $date2, $year2);
    $selisih =$waktuinput - $waktusekarang; 
    
    $dat1 = $info['tgl_upload'];
    $dat2 = $tglsekarang;
    $pecahTgl1 = explode("-", $dat1);
    $tgl1 = $pecahTgl1[0];
    $bln1 = $pecahTgl1[1];
    $thn1 = $pecahTgl1[2];
    
    $i = 0;
    $sum = 0;
    do {
        $tanggal = date("d-m-Y", mktime(0, 0, 0, $bln1, $tgl1+$i, $thn1));
        if (date("w", mktime(0, 0, 0, $bln1, $tgl1+$i, $thn1)) == 0 or date("w", mktime(0, 0, 0, $bln1, $tgl1+$i, $thn1)) == 6) {
            $sum++;
        }
        $i++;
    } while ($tanggal != $dat2);
    
    $day=$selisih-$sum;
    $dayx=$day+1;
    
    // Highlight rows based on days passed
    if($dayx >= 3 && $info['status']=='Review') {
        echo 'style="background-color: #f8d7da;"'; // Red for overdue
    } elseif($dayx >= 1 && $info['status']=='Review') {
        echo 'style="background-color: #fff3cd;"'; // Yellow for urgent
    }
?>>
    <td><?php echo $j; ?></td>
    <td><?php echo htmlspecialchars($info['tgl_upload']);?></td>
    <td><?php echo htmlspecialchars($info['no_drf']);?></td>
    <td><?php echo htmlspecialchars($info['no_doc']);?></td>
    <td><?php echo htmlspecialchars($info['no_rev']);?></td>
    <td>
    <?php 
    $doc_type = htmlspecialchars($info['doc_type']);
    $file_name = htmlspecialchars($info['file']);
    
    // Sanitize folder name
    $safe_type = preg_replace('/[^A-Za-z0-9 _\-&]/', '', $doc_type);
    $safe_type = str_replace(' ', '_', $safe_type);
    
    if ($info['no_drf']>12967){
        $tempat = $safe_type;
    } else {
        $tempat = "document";
    }
    ?>
    <a href="<?php echo $tempat; ?>/<?php echo $file_name; ?>" >
        <?php echo htmlspecialchars($info['title']);?>
    </a>
    </td>
    <td><?php echo htmlspecialchars($info['process']);?></td>
    <td><?php echo htmlspecialchars($info['section']);?></td>
    <td><?php echo $doc_type;?></td>
    <td><?php echo htmlspecialchars($info['rev_to']);?></td>
    <td>
        <?php
        echo $day+1;
        if ($dayx>=1 and $info['status']=='Review' and ($state=='Admin' or $state=='Originator') ) {
        ?>
            <a href="reminder.php?drf=<?php echo $info['no_drf'];?>&type=<?php echo urlencode($info['doc_type']);?>&nodoc=<?php echo urlencode($info['no_doc']);?>&title=<?php echo urlencode($info['title']);?>" class="btn btn-xs btn-warning"><span class="glyphicon glyphicon-envelope"></span>&nbsp; Reminder <strong><?php echo $info['reminder']?>x</strong></a>
        <?php
        }
        ?>
    </td>
    <td>
    <?php if ($info['status']=='Review'){ ?>
    <span class="label label-info"><?php } ?>
    <?php if ($info['status']=='Pending'){ ?>
    <span class="label label-warning"><?php } ?>
    <?php if ($info['status']=='Approved'){ ?>
    <span class="label label-success"><?php }?>
        <?php echo htmlspecialchars($info['status']);?>
        </span>
    </td>
    <td>
    <a href="detail.php?drf=<?php echo $info['no_drf'];?>&no_doc=<?php echo urlencode($info['no_doc']);?>" class="btn btn-xs btn-info" title="lihat detail"><span class="glyphicon glyphicon-search" ></span> </a>
    <a href="radf.php?drf=<?php echo $info['no_drf'];?>&section=<?php echo urlencode($info['section'])?>" class="btn btn-xs btn-info" title="lihat RADF"><span class="glyphicon glyphicon-eye-open" ></span> </a>
    <a href="lihat_approver.php?drf=<?php echo $info['no_drf'];?>&nodoc=<?php echo urlencode($info['no_doc'])?>&title=<?php echo urlencode($info['title'])?>&type=<?php echo urlencode($info['doc_type'])?>" class="btn btn-xs btn-info" title="lihat approver"><span class="glyphicon glyphicon-user" ></span> </a> 
    
    <?php if ($state=='Approver'){?>
    <a href="approve.php?drf=<?php echo $info['no_drf'];?>&device=<?php echo urlencode($info['device'])?>&no_doc=<?php echo urlencode($info['no_doc']);?>&title=<?php echo urlencode($info['title']) ?>&tipe=<?php echo urlencode($tipe); ?>" class="btn btn-xs btn-success" title="Approve Doc"><span class="glyphicon glyphicon-thumbs-up" ></span> </a>
    <a href="pending.php?drf=<?php echo $info['no_drf'];?>&no_doc=<?php echo urlencode($info['no_doc']);?>&type=<?php echo urlencode($info['doc_type']);?>" class="btn btn-xs btn-warning" title="Suspend Doc"><span class="glyphicon glyphicon-warning-sign" ></span>  </a>
    <?php } ?>

    <?php if ($state=='Admin' ||  ($state=="Originator" && $info['status']<>"Approved")){ ?>
    <a href="edit_doc.php?drf=<?php echo $info['no_drf'];?>" class="btn btn-xs btn-primary" title="Edit Doc"><span class="glyphicon glyphicon-pencil" ></span> </a>
    <a href="del_doc.php?drf=<?php echo $info['no_drf'];?>" class="btn btn-xs btn-danger" onClick="return confirm('Delete document <?php echo htmlspecialchars($info['no_doc'])?>?')" title="Delete Doc"><span class="glyphicon glyphicon-remove" ></span> </a>
    
    <?php if ($info['status']=='Approved') { ?>
    <a data-toggle="modal" data-target="#myModal2" data-id="<?php echo $info['no_drf']?>" data-lama="<?php echo htmlspecialchars($info['file'])?>" data-type="<?php echo htmlspecialchars($info['doc_type'])?>" data-status="<?php echo htmlspecialchars($info['status'])?>" data-rev="<?php echo htmlspecialchars($info['rev_to'])?>" class="btn btn-xs btn-success sec-file" title="Secure Document">
    <span class="glyphicon glyphicon-play" ></span></a>
    <?php } } ?>

    <?php if ($info['status']=='Pending' and ($state=='Originator' or $state='Admin')){ ?>
    <button data-toggle="modal" data-target="#myModal" data-id="<?php echo $info['no_drf']?>" data-type="<?php echo htmlspecialchars($info['doc_type'])?>" data-nodoc="<?php echo htmlspecialchars($info['no_doc'])?>" data-title="<?php echo htmlspecialchars($info['title'])?>" data-lama="<?php echo htmlspecialchars($info['file'])?>"  class="btn btn-xs btn-warning upload-file">
    <span class="glyphicon glyphicon-upload"></span>
    Change Document</button>
    <?php }?>
    </td>
</tr>

<?php 
$j++;} 
?> 
</tbody>
</table>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title" id="myModalLabel">Upload Document</h4>
            </div>
            <div class="modal-body">
                <form name="ganti_doc" method="POST" action="ganti_doc.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="drf" id="drf" class="form-control" value=""/>
                        <input type="hidden" name="nodoc" id="nodoc" class="form-control" value=""/>
                        <input type="hidden" name="type" id="type" class="form-control" value=""/>
                        <input type="hidden" name="title" id="title" class="form-control" value=""/>
                        <input type="text" name="lama" id="lama" class="form-control" value=""/>
                        File Document(.pdf/.xlsx):<input type="file" name="baru" class="form-control">
                        File Master(.docx/.xlsx):<input type="file" name="masterbaru" class="form-control"><br />
                    </div>
                    <div class="modal-footer"> <a class="btn btn-default" data-dismiss="modal">Cancel</a>
                        <input type="submit" name="upload" value="Update" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title" id="myModalLabel">Secure Document</h4>
            </div>
            <div class="modal-body">
                <form name="secure_doc" method="POST" action="process.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="drf" id="drf" class="form-control" value=""/>
                        <input type="hidden" name="rev" id="rev" class="form-control" value=""/>
                        <input type="hidden" name="type" id="type" class="form-control" value=""/>
                        <input type="hidden" name="status" id="status" class="form-control" value=""/>
                        <input type="file" name="baru" class="form-control">
                    </div>
                    <div class="modal-footer"> <a class="btn btn-default" data-dismiss="modal">Cancel</a>
                        <input type="submit" name="upload" value="Update" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php } ?>