<?php
include('header.php');
include 'koneksi.php';

// Ambil parameter
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$subtype = isset($_GET['subtype']) ? trim($_GET['subtype']) : '';

if (empty($type)) {
    echo "<div class='alert alert-danger'>Invalid document type</div>";
    exit;
}

// Load document types dan filter options
$jsonFile = __DIR__ . '/data/document_types.json';
$filterOptionsFile = __DIR__ . '/data/filter_options.json';

$docTypes = [];
$filterConfig = [];
$globalFilters = [];

if (file_exists($jsonFile)) {
    $docTypes = json_decode(file_get_contents($jsonFile), true);
    
    // Load global filters
    if (file_exists($filterOptionsFile)) {
        $globalFilters = json_decode(file_get_contents($filterOptionsFile), true);
    }
    
    // Cari document type yang sesuai
    foreach ($docTypes as $dt) {
        if (strcasecmp($dt['name'], $type) === 0) {
            // Jika ada subtype, cari config di submenu
            if (!empty($subtype) && !empty($dt['submenu'])) {
                foreach ($dt['submenu'] as $sub) {
                    if (strcasecmp($sub['name'], $subtype) === 0) {
                        $filterConfig = isset($sub['filter_config']) ? $sub['filter_config'] : [];
                        break;
                    }
                }
            } else {
                // Gunakan config dari parent
                $filterConfig = isset($dt['filter_config']) ? $dt['filter_config'] : [];
            }
            break;
        }
    }
}

// Default filter config jika tidak ditemukan
if (empty($filterConfig)) {
    $filterConfig = [
        'section' => true,
        'status' => true
    ];
}

// Function untuk get filter label dan options (SIMPLE VERSION)
function getFilterData($filterKey, $globalFilters) {
    if (isset($globalFilters[$filterKey])) {
        return [
            'label' => $globalFilters[$filterKey]['label'],
            'options' => $globalFilters[$filterKey]['options']
        ];
    }
    
    // Fallback default
    return [
        'label' => ucfirst($filterKey),
        'options' => []
    ];
}

// Title halaman
$pageTitle = htmlspecialchars($type);
if (!empty($subtype)) {
    $pageTitle .= " - " . htmlspecialchars($subtype);
}
?>

<!-- jQuery -->
<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>

<script type="text/javascript">
$(document).ready(function () {
    // Modal handler untuk update document
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

        var tipe = $(this).data('tipe');
        $(".modal-body #tipe").val( tipe );
    });

    // Tombol upload sosialisasi
    $(document).on('click', '.btn-upload-sos', function(e){
        e.preventDefault();
        var drf = $(this).data('drf');
        var nodoc = $(this).data('nodoc');
        $('#modal_upload_drf').val(drf);
        $('#modal_upload_nodoc').text(nodoc);
        $('#modalSosialisasi').modal('show');
    });
});
</script>

<br />

<div class="row">
    <div class="col-xs-4 well well-lg">
        <h2>Filter Documents</h2>
        
        <form action="" method="GET">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <?php if (!empty($subtype)): ?>
            <input type="hidden" name="subtype" value="<?php echo htmlspecialchars($subtype); ?>">
            <?php endif; ?>
            
            <table>
                <?php
                // Render filters dynamically berdasarkan filterConfig
                foreach ($filterConfig as $filterKey => $isEnabled):
                    if (!$isEnabled) continue;
                    
                    // Get filter data - 100% DARI JSON (NO OVERRIDE)
                    $filterData = getFilterData($filterKey, $globalFilters);
                    $filterLabel = $filterData['label'];
                    $filterOptions = $filterData['options'];
                    
                    // Map parameter name untuk URL
                    $paramName = $filterKey;
                    if ($filterKey === 'process') $paramName = 'proc';
                    if ($filterKey === 'category') $paramName = 'cat';
                ?>
                
                <tr>
                    <td><?php echo htmlspecialchars($filterLabel); ?></td>
                    <td>:</td>
                    <td>
                        <select name="<?php echo htmlspecialchars($paramName); ?>" class="form-control">
                            <option value=""> --- Select <?php echo htmlspecialchars($filterLabel); ?> --- </option>
                            <?php 
                            // ===== 100% DARI JSON - TIDAK ADA QUERY DATABASE =====
                            foreach ($filterOptions as $opt) {
                                $selected = (isset($_GET[$paramName]) && $_GET[$paramName] == $opt) ? 'selected' : '';
                                
                                // Special handling untuk status display
                                $displayText = $opt;
                                if ($filterKey === 'status' && $opt === 'Secured') {
                                    $displayText = 'Approved';
                                }
                                
                                echo '<option value="' . htmlspecialchars($opt) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <?php endforeach; ?>

                <tr>
                    <td></td>
                    <td></td>
                    <td>
                        <input type="hidden" name="by" value="no_drf">
                        <input type="submit" value="Show" name="submit" class="btn btn-info">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php
// Build query jika form disubmit
if (isset($_GET['submit'])) {
    // WHERE condition untuk doc_type (WAJIB)
    $whereConditions = ["doc_type = '" . mysqli_real_escape_string($link, $type) . "'"];
    
    // ===== FLEXIBLE FILTER: Mirip procedure_login.php =====
    // HANYA filter jika user AKTIF memilih nilai (tidak ada auto-filter ketat)
    
    // Apply filters berdasarkan input user saja
    foreach ($filterConfig as $filterKey => $isEnabled) {
        if (!$isEnabled) continue;
        
        // Map GET parameter names
        $paramName = $filterKey;
        if ($filterKey === 'process') $paramName = 'proc';
        if ($filterKey === 'category') $paramName = 'cat';
        
        // HANYA tambahkan filter jika user memilih nilai
        if (isset($_GET[$paramName]) && trim($_GET[$paramName]) !== '') {
            $whereConditions[] = "$filterKey = '" . mysqli_real_escape_string($link, $_GET[$paramName]) . "'";
        }
    }
    
    $by = isset($_GET['by']) ? mysqli_real_escape_string($link, $_GET['by']) : 'no_drf';
    $sql = "SELECT * FROM docu WHERE " . implode(' AND ', $whereConditions) . " ORDER BY $by DESC LIMIT 200";
    
    $result = mysqli_query($link, $sql);
    
    if (!$result) {
        echo "<div class='alert alert-danger'>Query error: ". htmlspecialchars(mysqli_error($link)) ."</div>";
        echo "<div class='alert alert-info'>Query: " . htmlspecialchars($sql) . "</div>";
        exit;
    }
?>

<br /><br />
<table class="table table-hover table-bordered">
    <h1>Document List: <strong><?php echo $pageTitle; ?></strong></h1>
    
    <thead bgcolor="#00FFFF">
        <tr>
            <td>No</td>
            <td>Date</td>
            <td>No. Document</td>
            <td>No Rev.</td>
            <td>DRF</td>
            <td>Title</td>
            <?php 
            // Render kolom header sesuai filter yang aktif (SKIP status & category)
            foreach ($filterConfig as $filterKey => $isEnabled):
                if (!$isEnabled) continue;
                
                // Skip beberapa kolom yang tidak perlu ditampilkan di tabel
                if (in_array($filterKey, ['status', 'category'])) continue;
                
                $filterData = getFilterData($filterKey, $globalFilters);
                $filterLabel = $filterData['label'];
                
                echo "<td>" . htmlspecialchars($filterLabel) . "</td>";
            endforeach;
            ?>
            <td>Status</td>
            <td>Action</td>
            <td>Sosialisasi</td>
        </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    $rowCount = 0;
    while ($info = mysqli_fetch_assoc($result)) {
        $rowCount++;
        $has_sos = !empty($info['sos_file']);
    ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo htmlspecialchars($info['tgl_upload']);?></td>
            <td><?php echo htmlspecialchars($info['no_doc']);?></td>
            <td><?php echo htmlspecialchars($info['no_rev']);?></td>
            <td><?php echo htmlspecialchars($info['no_drf']);?></td>
            <td>
                <?php
                if ($info['no_drf'] > 12967) { 
                    $tempat = $info['doc_type']; 
                } else { 
                    $tempat = 'document'; 
                }
                ?>
                <a href="<?php echo htmlspecialchars($tempat . '/' . $info['file']); ?>" target="_blank">
                    <?php echo htmlspecialchars($info['title']);?>
                </a>
            </td>
            
            <?php 
            // Render kolom data sesuai filter yang aktif
            foreach ($filterConfig as $filterKey => $isEnabled):
                if (!$isEnabled) continue;
                if (in_array($filterKey, ['status', 'category'])) continue;
                
                echo "<td>" . htmlspecialchars($info[$filterKey]) . "</td>";
            endforeach;
            ?>
            
            <td>
                <?php
                $statusClass = '';
                switch ($info['status']) {
                    case 'Approved':
                    case 'Secured':
                        $statusClass = 'success';
                        break;
                    case 'Review':
                        $statusClass = 'warning';
                        break;
                    case 'Pending':
                        $statusClass = 'info';
                        break;
                    default:
                        $statusClass = 'default';
                }
                ?>
                <span class="label label-<?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($info['status']); ?>
                </span>
            </td>

            <!-- Action -->
            <td>
                <a href="detail.php?drf=<?php echo urlencode($info['no_drf']);?>&no_doc=<?php echo urlencode($info['no_doc']);?>" 
                   class="btn btn-xs btn-info" title="Lihat detail">
                    <span class="glyphicon glyphicon-search"></span>
                </a>

                <a href="lihat_approver.php?drf=<?php echo urlencode($info['no_drf']);?>" 
                   class="btn btn-xs btn-warning" title="Lihat approver">
                    <span class="glyphicon glyphicon-user"></span>
                </a>

                <a href="radf.php?drf=<?php echo urlencode($info['no_drf']);?>&section=<?php echo urlencode($info['section']);?>" 
                   class="btn btn-xs btn-primary" title="Lihat RADF">
                    <span class="glyphicon glyphicon-eye-open"></span>
                </a>

                <button type="button"
                    class="btn btn-xs btn-success btn-upload-sos"
                    data-drf="<?php echo htmlspecialchars($info['no_drf']);?>"
                    data-nodoc="<?php echo htmlspecialchars($info['no_doc']);?>"
                    title="Upload Bukti Sosialisasi">
                    <span class="glyphicon glyphicon-upload"></span>
                </button>

                <?php
                if ( ($_SESSION['state'] ?? '') == 'Admin' || 
                     (($_SESSION['state'] ?? '') == "Originator" && ($info['user_id'] ?? '') == ($_SESSION['nrp'] ?? '')) ) {
                ?>
                    <a href="edit_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" 
                       class="btn btn-xs btn-primary" title="Edit Doc">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </a>
                    <a href="del_doc.php?drf=<?php echo urlencode($info['no_drf']);?>" 
                       class="btn btn-xs btn-danger" 
                       onClick="return confirm('Delete document <?php echo addslashes($info['no_doc'])?>?')" 
                       title="Delete Doc">
                        <span class="glyphicon glyphicon-remove"></span>
                    </a>
                    
                    <?php if ($info['status'] == 'Secured') { ?>
                        <a data-toggle="modal" data-target="#myModal2" 
                           data-id="<?php echo $info['no_drf']?>" 
                           data-lama="<?php echo $info['file']?>" 
                           data-tipe="<?php echo $info['category']?>" 
                           data-status="<?php echo $info['status']?>" 
                           class="btn btn-xs btn-success sec-file" 
                           title="Secure Document">
                            <span class="glyphicon glyphicon-play"></span>
                        </a>
                    <?php } ?>
                <?php } ?>
            </td>

            <!-- Kolom Sosialisasi -->
            <td>
                <?php if ($has_sos) { ?>
                    <a href="lihat_sosialisasi.php?drf=<?php echo urlencode($info['no_drf']);?>" 
                       class="btn btn-xs btn-primary" title="Lihat Detail Sosialisasi">
                        <span class="glyphicon glyphicon-file"></span>
                    </a>
                <?php } else { ?>
                    <a href="lihat_sosialisasi.php?drf=<?php echo urlencode($info['no_drf']);?>" 
                       class="btn btn-xs btn-default" title="Belum ada bukti sosialisasi">
                        <span class="glyphicon glyphicon-file"></span>
                    </a>
                <?php } ?>
            </td>
        </tr>
    <?php
        $i++;
    }
    
    // Debug: tampilkan jumlah hasil
    if ($rowCount === 0) {
        echo "<tr><td colspan='20' class='text-center'><div class='alert alert-warning' style='margin:20px;'>Tidak ada dokumen yang ditemukan dengan filter tersebut.</div></td></tr>";
        echo "<tr><td colspan='20'><div class='alert alert-info' style='margin:20px;'><strong>Debug Query:</strong><br>" . htmlspecialchars($sql) . "</div></td></tr>";
    }
    ?>
    </tbody>
</table>

<?php
} else {
    // Tampilkan instruksi jika belum submit
    echo "<div class='alert alert-info' style='margin-top:20px;'>";
    echo "<h4><span class='glyphicon glyphicon-info-sign'></span> Cara Menggunakan</h4>";
    echo "<p>Klik tombol <strong>Show</strong> untuk menampilkan <strong>SEMUA dokumen " . htmlspecialchars($type) . "</strong>.</p>";
    echo "<p>Atau pilih filter terlebih dahulu untuk menyaring hasil:</p>";
    echo "<ul>";
    
    // List filter yang tersedia
    foreach ($filterConfig as $filterKey => $isEnabled) {
        if (!$isEnabled) continue;
        $filterData = getFilterData($filterKey, $globalFilters);
        echo "<li><strong>" . htmlspecialchars($filterData['label']) . "</strong></li>";
    }
    
    echo "</ul>";
    
    // Info submenu (jika ada) - bersifat informational saja
    if (!empty($subtype)) {
        echo "<hr>";
        echo "<p class='text-muted'><small><span class='glyphicon glyphicon-tag'></span> Anda berada di submenu: <strong>" . htmlspecialchars($subtype) . "</strong></small></p>";
        
        $subtype_lower = strtolower($subtype);
        if (strpos($subtype_lower, 'production') !== false) {
            echo "<p class='text-muted'><small>Submenu ini biasanya untuk dokumen dengan device production tertentu.</small></p>";
        } elseif (strpos($subtype_lower, 'other') !== false) {
            echo "<p class='text-muted'><small>Submenu ini biasanya untuk dokumen general/tanpa device spesifik.</small></p>";
        }
    }
    
    echo "</div>";
}
?>

<!-- Modal Update Document (Secure) -->
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Update Document</h4>
            </div>
            <div class="modal-body">
                <form name="secure_doc" method="POST" action="process.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="drf" id="drf" class="form-control" value=""/>
                        <input type="hidden" name="rev" id="rev" class="form-control" value=""/>
                        <input type="hidden" name="type" id="type" class="form-control" value=""/>
                        <input type="hidden" name="status" id="status" class="form-control" value=""/>
                        <input type="hidden" name="tipe" id="tipe" class="form-control" value=""/>
                        <input type="file" name="baru" class="form-control">
                    </div>
                    
                    <div class="modal-footer"> 
                        <a class="btn btn-default" data-dismiss="modal">Cancel</a>
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
                        if (function_exists('random_bytes')) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        else $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label>File bukti (pdf / jpg / png)</label>
                        <input type="file" name="sos_file" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan / Keterangan</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="upload_sosialisasi" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="bootstrap/js/bootstrap.min.js"></script>