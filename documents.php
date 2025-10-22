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
$currentDocType = null;

// ===== AUTO-DETECT AVAILABLE DATABASE COLUMNS =====
$availableDbColumns = [];
$checkColumns = mysqli_query($link, "SHOW COLUMNS FROM docu");
if ($checkColumns) {
    while ($col = mysqli_fetch_assoc($checkColumns)) {
        $availableDbColumns[] = $col['Field'];
    }
}

if (file_exists($jsonFile)) {
    $docTypes = json_decode(file_get_contents($jsonFile), true);
    
    // Load global filters
    if (file_exists($filterOptionsFile)) {
        $globalFilters = json_decode(file_get_contents($filterOptionsFile), true);
    }
    
    // Cari document type yang sesuai
    foreach ($docTypes as $dt) {
        if (strcasecmp($dt['name'], $type) === 0) {
            $currentDocType = $dt;
            
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
        'section_dept' => true,
        'status' => true
    ];
}

// ===== AUTO-FILTER: Remove filters yang kolom database-nya tidak ada =====
foreach ($filterConfig as $filterKey => $isEnabled) {
    if (!$isEnabled) continue;
    
    // SKIP filters yang tidak butuh kolom database
    if (in_array($filterKey, ['section_prod', 'device', 'process', 'type'])) continue;
    
    $dbColumn = getDbColumn($filterKey);
    
    // Jika kolom tidak ada di database, auto-disable filter ini
    if (!in_array($dbColumn, $availableDbColumns)) {
        $filterConfig[$filterKey] = false;
    }
}

// Function untuk get filter label dan options
function getFilterData($filterKey, $globalFilters, $docTypeName = '') {
    if (isset($globalFilters[$filterKey])) {
        $filterData = [
            'label' => $globalFilters[$filterKey]['label'],
            'options' => $globalFilters[$filterKey]['options']
        ];
        
        // ✅ Special handling untuk type filter (dynamic)
        if ($filterKey === 'type' && !empty($docTypeName)) {
            if (isset($globalFilters['type']['options_by_doctype'][$docTypeName])) {
                $filterData['options'] = $globalFilters['type']['options_by_doctype'][$docTypeName];
            }
        }
        
        return $filterData;
    }
    
    // Fallback default
    return [
        'label' => ucfirst(str_replace('_', ' ', $filterKey)),
        'options' => []
    ];
}

// Mapping parameter GET untuk backward compatibility
function getParamName($filterKey) {
    // Map filter keys ke nama parameter GET
    $mapping = [
        'section_dept' => 'section',
        'section_prod' => 'section',
        'process' => 'proc',
        'category' => 'cat',
        'device' => 'device',
        'status' => 'status',
        'type' => 'doctype'  // ✅ Parameter untuk type filter
    ];
    
    return isset($mapping[$filterKey]) ? $mapping[$filterKey] : $filterKey;
}

// Mapping database column untuk section types
function getDbColumn($filterKey) {
    if ($filterKey === 'section_dept') return 'section';
    if ($filterKey === 'section_prod') return 'section_prod'; // Tidak ada di DB, pakai JOIN
    if ($filterKey === 'process') return 'process';
    if ($filterKey === 'category') return 'category';
    if ($filterKey === 'type') return 'doc_type'; // ✅ Kolom database untuk type
    if ($filterKey === 'device') return 'device';
    if ($filterKey === 'status') return 'status';
    return $filterKey;
}

// Check if this page needs CASCADE dropdown (section_prod + device)
$useCascadeDropdown = isset($filterConfig['section_prod']) && $filterConfig['section_prod'] === true 
                      && isset($filterConfig['device']) && $filterConfig['device'] === true;

// Title halaman
$pageTitle = htmlspecialchars($type);
if (!empty($subtype)) {
    $pageTitle .= " - " . htmlspecialchars($subtype);
}
?>

<!-- jQuery -->
<script type="text/javascript" src="bootstrap/js/jquery.min.js"></script>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style>
.btn-upload-sos { margin-left:6px; }
.modal .form-control { margin-bottom:10px; }
</style>

<?php if ($useCascadeDropdown): ?>
<!-- CASCADE DROPDOWN SCRIPT -->
<script type="text/javascript">
$(document).ready(function() {
    $('#wait_1').hide();
    $('#wait_2').hide();
    
    // Section Production → Device (CASCADE)
    $('#section').change(function(){
        var section_value = $(this).val();
        
        if(section_value == '') {
            $('#result_1').html('<select name="device" id="device" class="form-control"><option value="">--- Select Device ---</option></select>').show();
            $('#result_2').html('<select name="proc" id="proc" class="form-control"><option value="">--- Select Process ---</option></select>').show();
            return;
        }
        
        $('#wait_1').show();
        $('#result_1').hide();
        $('#result_2').hide();
        
        $.ajax({
            url: 'func.php',
            type: 'GET',
            data: { 
                func: 'section', 
                drop_var: section_value 
            },
            success: function(response){
                $('#wait_1').hide();
                $('#result_1').html(response).fadeIn();
                $('#result_2').html('<select name="proc" id="proc" class="form-control"><option value="">--- Select Process ---</option></select>').show();
                attachDeviceEvent();
            },
            error: function(xhr, status, error){
                console.error('AJAX Error:', error);
                $('#wait_1').hide();
                $('#result_1').html('<div class="alert alert-danger">Error loading devices</div>').show();
            }
        });
    });
    
    function attachDeviceEvent() {
        $('#device').off('change').on('change', function(){
            var device_value = $(this).val();
            
            if(device_value == '' || device_value == 'General production' || device_value == 'General PC') {
                $('#result_2').html('<select name="proc" id="proc" class="form-control"><option value="">--- Select Process ---</option><option value="General Process">General Process</option></select>').show();
                return;
            }
            
            $('#wait_2').show();
            $('#result_2').hide();
            
            $.ajax({
                url: 'func.php',
                type: 'GET',
                data: { 
                    func: 'device', 
                    drop_var2: device_value 
                },
                success: function(response){
                    $('#wait_2').hide();
                    $('#result_2').html(response).fadeIn();
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', error);
                    $('#wait_2').hide();
                    $('#result_2').html('<div class="alert alert-danger">Error loading processes</div>').show();
                }
            });
        });
    }
    
    // Modal handlers
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

    $(document).on('click', '.btn-upload-sos', function(e){
        e.preventDefault();
        var drf = $(this).data('drf');
        var nodoc = $(this).data('nodoc');
        $('#modal_upload_drf').val(drf);
        $('#modal_upload_nodoc').text(nodoc);
        $('#modalSosialisasi').modal('show');
    });
    
    $('#modalSosialisasi').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
    
    $('#myModal2').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
});
</script>
<?php else: ?>
<!-- STANDARD SCRIPT (no cascade) -->
<script type="text/javascript">
$(document).ready(function () {
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

    $(document).on('click', '.btn-upload-sos', function(e){
        e.preventDefault();
        var drf = $(this).data('drf');
        var nodoc = $(this).data('nodoc');
        $('#modal_upload_drf').val(drf);
        $('#modal_upload_nodoc').text(nodoc);
        $('#modalSosialisasi').modal('show');
    });
    
    $('#modalSosialisasi').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
    
    $('#myModal2').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });
});
</script>
<?php endif; ?>

<br />

<h3><?php echo htmlspecialchars($type); ?>
<?php if (!empty($subtype)): ?>
    - <?php echo htmlspecialchars($subtype); ?>
<?php endif; ?>
</h3>

<div class="row">
    <div class="col-xs-4 well well-lg">
        <h2>Filter Document - <?php echo htmlspecialchars($type); ?></h2>
        
        <form action="" method="GET">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <?php if (!empty($subtype)): ?>
            <input type="hidden" name="subtype" value="<?php echo htmlspecialchars($subtype); ?>">
            <?php endif; ?>
            
            <table>
                <?php
                // ===== FIXED ORDER: Pastikan cascade dropdown selalu berurutan =====
                $orderedFilters = [];
                
                // Jika pakai cascade, prioritaskan section_prod, device, process di awal
                if ($useCascadeDropdown) {
                    $orderedFilters = ['section_prod', 'device', 'process'];
                    
                    // Tambahkan filter lainnya yang aktif
                    foreach ($filterConfig as $filterKey => $isEnabled) {
                        if ($isEnabled && !in_array($filterKey, $orderedFilters)) {
                            $orderedFilters[] = $filterKey;
                        }
                    }
                } else {
                    // Jika tidak pakai cascade, gunakan urutan default dari config
                    foreach ($filterConfig as $filterKey => $isEnabled) {
                        if ($isEnabled) {
                            $orderedFilters[] = $filterKey;
                        }
                    }
                }
                
                // Render filters sesuai urutan yang sudah diatur
                foreach ($orderedFilters as $filterKey):
                    if (!isset($filterConfig[$filterKey]) || !$filterConfig[$filterKey]) continue;
                    
                    // ✅ Pass document type name untuk dynamic type options
                    $filterData = getFilterData($filterKey, $globalFilters, $type);
                    $filterLabel = $filterData['label'];
                    $filterOptions = $filterData['options'];
                    $paramName = getParamName($filterKey);
                    
                    // ===== SPECIAL HANDLING: CASCADE DROPDOWN =====
                    if ($useCascadeDropdown && $filterKey === 'section_prod'):
                ?>
                
                <tr>
                    <td><?php echo htmlspecialchars($filterLabel); ?></td>
                    <td>:</td>
                    <td>
                        <?php include('func.php'); ?>
                        <select name="<?php echo htmlspecialchars($paramName); ?>" id="section" class="form-control">
                            <option value="">Select <?php echo htmlspecialchars($filterLabel); ?></option>
                            <?php getTierOne(); ?>
                        </select>
                    </td>
                </tr>
                
                <?php elseif ($useCascadeDropdown && $filterKey === 'device'): ?>
                
                <tr>
                    <td>Device</td>
                    <td>:</td>
                    <td>
                        <span id="wait_1" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_1" style="display: none;"></span>
                    </td>
                </tr>
                
                <?php elseif ($useCascadeDropdown && $filterKey === 'process'): ?>
                
                <tr>
                    <td>Process</td>
                    <td>:</td>
                    <td>
                        <span id="wait_2" style="display: none;"><img alt="Please Wait" src="images/wait.gif"/></span>
                        <span id="result_2" style="display: none;"></span>
                    </td>
                </tr>
                
                <?php else: // STANDARD DROPDOWN ?>
                
                <tr>
                    <td><?php echo htmlspecialchars($filterLabel); ?></td>
                    <td>:</td>
                    <td>
                        <select name="<?php echo htmlspecialchars($paramName); ?>" class="form-control">
                            <option value=""> --- Select <?php echo htmlspecialchars($filterLabel); ?> --- </option>
                            <?php 
                            foreach ($filterOptions as $opt) {
                                $selected = (isset($_GET[$paramName]) && $_GET[$paramName] == $opt) ? 'selected' : '';
                                
                                // Special handling untuk status display
                                $displayText = $opt;
                                if ($filterKey === 'status' && $opt === 'Approve') {
                                    $displayText = 'Approved';
                                }
                                
                                echo '<option value="' . htmlspecialchars($opt) . '" ' . $selected . '>' . htmlspecialchars($displayText) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <?php 
                    endif; // end special handling
                endforeach; // end foreach filter
                ?>

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
    // ambil session state
    $state = isset($_SESSION['state']) ? $_SESSION['state'] : '';
    $nrp = isset($_SESSION['nrp']) ? $_SESSION['nrp'] : '';
    
    // WHERE condition untuk doc_type (WAJIB)
    $whereConditions = ["docu.doc_type = '" . mysqli_real_escape_string($link, $type) . "'"];
    
    // ===== FLEXIBLE FILTER WITH JOIN SUPPORT =====
    $needJoinDevice = false;
    $section_prod_value = '';
    
    foreach ($filterConfig as $filterKey => $isEnabled) {
        if (!$isEnabled) continue;
        
        // Get parameter name
        $paramName = getParamName($filterKey);
        
        // ✅ SKIP section_prod dari WHERE karena pakai JOIN
        if ($filterKey === 'section_prod') {
            if (isset($_GET[$paramName]) && trim($_GET[$paramName]) !== '') {
                $needJoinDevice = true;
                $section_prod_value = mysqli_real_escape_string($link, $_GET[$paramName]);
            }
            continue;
        }
        
        // Get database column name
        $dbColumn = getDbColumn($filterKey);
        
        // HANYA tambahkan filter jika user memilih nilai
        if (isset($_GET[$paramName]) && trim($_GET[$paramName]) !== '') {
            $whereConditions[] = "docu.$dbColumn = '" . mysqli_real_escape_string($link, $_GET[$paramName]) . "'";
        }
    }
    
    // ===== BUILD SQL QUERY =====
    $by = isset($_GET['by']) ? mysqli_real_escape_string($link, $_GET['by']) : 'no_drf';
    
    // Base query
    if ($needJoinDevice) {
        // Query dengan JOIN ke device table
        $sql = "SELECT docu.* FROM docu 
                LEFT JOIN device ON docu.device = device.name 
                WHERE " . implode(' AND ', $whereConditions);
        
        // Tambahkan filter section_prod
        if (!empty($section_prod_value)) {
            $sql .= " AND device.group_dev = '$section_prod_value'";
        }
        
        $sql .= " ORDER BY docu.$by DESC";
    } else {
        // Query normal tanpa JOIN
        $sql = "SELECT * FROM docu WHERE " . implode(' AND ', $whereConditions) . " ORDER BY $by DESC";
    }
    
    $result = mysqli_query($link, $sql);
    
    if (!$result) {
        echo "<div class='alert alert-danger'>Query error: ". htmlspecialchars(mysqli_error($link)) ."</div>";
        echo "<div class='alert alert-info'>Query: " . htmlspecialchars($sql) . "</div>";
        exit;
    }
    
    $rowCount = mysqli_num_rows($result);
?>

<br /><br />
<h3>Document List: <strong><?php echo $pageTitle; ?></strong></h3>

<?php if ($rowCount == 0): ?>
    <div class='alert alert-warning' style='margin:20px;'>
        <h4><span class='glyphicon glyphicon-search'></span> Tidak ada dokumen yang ditemukan</h4>
        <p>Tidak ada dokumen <strong><?php echo htmlspecialchars($type); ?></strong> dengan filter yang Anda pilih.</p>
        <hr>
        <p class='text-left'><strong>Filter yang aktif:</strong></p>
        <ul class='text-left'>
        <?php
        // Tampilkan filter yang digunakan
        foreach ($filterConfig as $filterKey => $isEnabled) {
            if (!$isEnabled) continue;
            $paramName = getParamName($filterKey);
            if (isset($_GET[$paramName]) && trim($_GET[$paramName]) !== '') {
                $filterData = getFilterData($filterKey, $globalFilters, $type);
                echo "<li><strong>" . htmlspecialchars($filterData['label']) . ":</strong> " . htmlspecialchars($_GET[$paramName]) . "</li>";
            }
        }
        ?>
        </ul>
        <p class='text-muted'><small>Coba gunakan filter yang berbeda atau klik <strong>Show</strong> tanpa filter untuk melihat semua dokumen.</small></p>
    </div>
<?php else: ?>

<div class="table-responsive">
    <table class="table table-hover">
        <thead style="background:#00FFFF;">
            <tr>
                <th>No</th>
                <th>Date</th>
                <th>No. Document</th>
                <th>No Rev.</th>
                <th>DRF</th>
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
        while ($info = mysqli_fetch_assoc($result)) {
            $has_sos = !empty($info['sos_file']);
            
            // Tentukan folder tempat file
            if ($info['no_drf'] > 12967) { 
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
                    <a href="<?php echo htmlspecialchars($tempat . '/' . $info['file']); ?>" target="_blank">
                        <?php echo htmlspecialchars($info['title']);?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($info['process']);?></td>
                <td><?php echo htmlspecialchars($info['section']);?></td>

                <!-- Action -->
                <td style="white-space:nowrap;">
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

                            <?php if ($info['status'] === 'Approved'): ?>
                        <a data-toggle="modal" data-target="#myModal2"
                            data-id="<?php echo htmlspecialchars($info['no_drf']); ?>"
                            data-lama="<?php echo htmlspecialchars($info['file']); ?>"
                            data-status="<?php echo htmlspecialchars($info['status']); ?>"
                            class="btn btn-xs btn-success sec-file" 
                            title="Secure Document">
                            <span class="glyphicon glyphicon-play"></span>
                        </a>
                    <?php endif; ?>
                    <?php
                    }
                    ?>
                </td>

                <!-- Kolom Sosialisasi -->
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
        }
        ?>
        </tbody>
    </table>
</div>

<?php
    endif; // end if rowCount > 0
    mysqli_free_result($result);
} else {
    // Tampilkan instruksi jika belum submit
    echo "<div class='alert alert-info' style='margin-top:20px;'>";
    echo "<h4><span class='glyphicon glyphicon-info-sign'></span> Cara Menggunakan</h4>";
    
    if ($useCascadeDropdown) {
        echo "<p>Untuk melihat dokumen <strong>" . htmlspecialchars($type) . "</strong>:</p>";
        echo "<ol>";
        echo "<li>Pilih <strong>Section (Production)</strong> terlebih dahulu</li>";
        echo "<li>Pilih <strong>Device</strong> (akan muncul otomatis setelah pilih section)</li>";
        echo "<li>Pilih <strong>Process</strong> (opsional, akan muncul setelah pilih device)</li>";
        echo "<li>Pilih filter tambahan lainnya jika diperlukan</li>";
        echo "<li>Klik tombol <strong>Show</strong></li>";
        echo "</ol>";
    } else {
        echo "<p>Klik tombol <strong>Show</strong> untuk menampilkan <strong>SEMUA dokumen " . htmlspecialchars($type) . "</strong>.</p>";
        echo "<p>Atau pilih filter terlebih dahulu untuk menyaring hasil:</p>";
        echo "<ul>";
        
        // List filter yang tersedia
        foreach ($filterConfig as $filterKey => $isEnabled) {
            if (!$isEnabled) continue;
            $filterData = getFilterData($filterKey, $globalFilters, $type);
            echo "<li><strong>" . htmlspecialchars($filterData['label']) . "</strong>";
            
            // Tampilkan options untuk filter type
            if ($filterKey === 'type' && !empty($filterData['options'])) {
                echo " (" . implode(', ', $filterData['options']) . ")";
            }
            echo "</li>";
        }
        
        echo "</ul>";
    }
    
    // Info submenu (jika ada)
    if (!empty($subtype)) {
        echo "<hr>";
        echo "<p class='text-muted'><small><span class='glyphicon glyphicon-tag'></span> Anda berada di submenu: <strong>" . htmlspecialchars($subtype) . "</strong></small></p>";
    }
    
    echo "</div>";
}
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
