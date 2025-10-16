<?php
// ===== PROSES POST DULU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $jsonFile = __DIR__ . '/data/document_types.json';
    $types = json_decode(file_get_contents($jsonFile), true);
    
    if (!is_array($types)) {
        $types = [];
    }
    
    $name = trim($_POST['name']);
    $hasSubmenu = isset($_POST['has_submenu']) ? true : false;
    
    // Ambil filter config dari checkbox yang di-submit
    $filterConfig = [];
    
    // Load available filters from filter_options.json
    $filterOptionsFile = __DIR__ . '/data/filter_options.json';
    if (file_exists($filterOptionsFile)) {
        $availableFilters = json_decode(file_get_contents($filterOptionsFile), true);
        if (is_array($availableFilters)) {
            foreach ($availableFilters as $key => $filter) {
                $filterConfig[$key] = isset($_POST['filter_' . $key]) ? true : false;
            }
        }
    }
    
    if ($name !== '') {
        // Check duplicate
        $existingNames = array_column($types, 'name');
        $lower = array_map('strtolower', $existingNames);
        
        if (in_array(strtolower($name), $lower)) {
            header("Location: add_document.php?error=duplicate");
            exit;
        }
        
        // Generate ID dari nama
        $id = strtolower(str_replace(' ', '_', $name));
        
        // Create new document type (SELALU DYNAMIC - tidak ada custom file)
        $newType = [
            'id' => $id,
            'name' => $name,
            'has_submenu' => $hasSubmenu,
            'submenu' => [],
            'filter_config' => $filterConfig,
            'use_custom_file' => false,
            'custom_file' => ''
        ];
        
        // Add to array
        $types[] = $newType;
        
        // Save
        if (file_put_contents($jsonFile, json_encode($types, JSON_PRETTY_PRINT))) {
            header("Location: conf_document.php?success=added");
            exit;
        } else {
            header("Location: add_document.php?error=save_failed");
            exit;
        }
    } else {
        header("Location: add_document.php?error=empty");
        exit;
    }
}

// ===== LOAD HEADER =====
include('header.php');
include('config_head.php');

// Load available filters
$filterOptionsFile = __DIR__ . '/data/filter_options.json';
$availableFilters = [];
if (file_exists($filterOptionsFile)) {
    $availableFilters = json_decode(file_get_contents($filterOptionsFile), true);
    if (!is_array($availableFilters)) {
        $availableFilters = [];
    }
}

// Pesan error
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'duplicate') {
        $error = "Document type dengan nama tersebut sudah ada.";
    } elseif ($_GET['error'] == 'empty') {
        $error = "Silakan masukkan nama document type.";
    } elseif ($_GET['error'] == 'save_failed') {
        $error = "Gagal menyimpan ke file JSON. Periksa permission folder.";
    }
}
?>

<br /><br />
<div class="row">
    <div class="col-xs-1"></div>
    <div class="col-xs-6 well well-lg">
        <h2><span class="glyphicon glyphicon-plus-sign"></span> Add New Document Type</h2>
        <p class="text-muted">Tambahkan tipe dokumen baru ke sistem</p>
        <hr>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label><strong>Document Type Name</strong> <span class="text-danger">*</span></label>
                <input type="text" class="form-control input-lg" name="name" required 
                       placeholder="Contoh: Quality Report, Training Record, dll">
                <p class="help-block">
                    <span class="glyphicon glyphicon-info-sign"></span> 
                    Nama tipe dokumen yang akan ditampilkan di menu navigation
                </p>
            </div>
            
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="has_submenu" id="has_submenu"> 
                        <strong>Dokumen ini memiliki Sub-Menu</strong>
                    </label>
                </div>
                <p class="help-block">
                    <span class="glyphicon glyphicon-info-sign"></span> 
                    Centang jika dokumen ini akan memiliki kategori sub-menu (misalnya: Production, Other, dll)
                </p>
            </div>

            <div class="alert alert-info" id="submenu_info" style="display:none;">
                <span class="glyphicon glyphicon-info-sign"></span> 
                <strong>Info:</strong> Setelah document type dibuat, Anda bisa menambahkan submenu melalui tombol <strong>"Add Submenu"</strong> di halaman config.
            </div>

            <hr>

            <h4>
                <span class="glyphicon glyphicon-filter"></span> Filter Configuration
                <button type="button" class="btn btn-sm btn-info pull-right" id="btnManageFilters">
                    <span class="glyphicon glyphicon-cog"></span> Manage Filters
                </button>
            </h4>
            <p class="text-muted">Pilih filter yang akan tersedia di halaman document list:</p>

            <?php if (empty($availableFilters)): ?>
                <div class="alert alert-warning">
                    <span class="glyphicon glyphicon-warning-sign"></span>
                    <strong>Tidak ada filter tersedia.</strong> 
                    Klik tombol "Manage Filters" di atas untuk menambahkan filter.
                </div>
            <?php else: ?>
                <div class="well">
                    <div class="row">
                        <?php 
                        $count = 0;
                        $totalFilters = count($availableFilters);
                        $halfPoint = ceil($totalFilters / 2);
                        
                        foreach ($availableFilters as $key => $filter): 
                            if ($count == 0) echo '<div class="col-xs-6">';
                            if ($count == $halfPoint) echo '</div><div class="col-xs-6">';
                        ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="filter_<?php echo $key; ?>" 
                                           class="filter-checkbox" data-key="<?php echo $key; ?>"
                                           <?php echo in_array($key, ['section', 'status']) ? 'checked' : ''; ?>>
                                    <strong><?php echo htmlspecialchars($filter['label']); ?></strong>
                                    <small class="text-muted">(<?php echo count($filter['options']); ?> options)</small>
                                </label>
                                <button type="button" class="btn btn-xs btn-default btn-edit-filter" 
                                        data-filter-key="<?php echo $key; ?>" 
                                        title="Edit Global Filter"
                                        style="margin-left:10px;">
                                    <span class="glyphicon glyphicon-edit"></span>
                                </button>
                            </div>
                        <?php 
                            $count++;
                        endforeach; 
                        ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <button type="submit" name="submit" class="btn btn-success btn-lg btn-block">
                    <span class="glyphicon glyphicon-save"></span> Add Document Type
                </button>
                <a href="conf_document.php" class="btn btn-default btn-lg btn-block">
                    <span class="glyphicon glyphicon-arrow-left"></span> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Modal untuk Manage Filters (Global) -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <span class="glyphicon glyphicon-filter"></span> Manage Global Filter Options
                </h4>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <!-- Alert Container -->
                <div id="modalAlertContainer"></div>

                <!-- Add New Filter -->
                <div class="panel panel-success">
                    <div class="panel-heading" style="padding: 8px 15px;">
                        <strong><span class="glyphicon glyphicon-plus-sign"></span> Add New Filter</strong>
                    </div>
                    <div class="panel-body" style="padding: 10px 15px;">
                        <form id="addFilterFormModal" class="form-inline">
                            <input type="text" id="newFilterKeyModal" class="form-control input-sm" placeholder="Key (e.g., priority)" style="width: 160px; margin-right: 5px;">
                            <input type="text" id="newFilterLabelModal" class="form-control input-sm" placeholder="Label (e.g., Priority)" style="width: 180px; margin-right: 5px;">
                            <button type="submit" class="btn btn-success btn-sm">
                                <span class="glyphicon glyphicon-plus"></span> Add
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Filters List -->
                <div id="modalFiltersList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.help-block {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    margin-bottom: 0;
}

.option-tag {
    display: inline-block;
    background: #f5f5f5;
    padding: 5px 10px;
    margin: 3px;
    border-radius: 3px;
    border: 1px solid #ddd;
}

.option-tag:hover {
    background: #e9ecef;
}

.option-tag span {
    margin-right: 8px;
}

.modal-body {
    background: #fafafa;
}

.modal-body .panel {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>

<script>
// Manage Filter Modal Functions
function openFilterModal(focusKey) {
    focusKey = focusKey || '';
    $('#filterModal').modal('show');
    loadFilters(focusKey);
}

function loadFilters(focusKey) {
    focusKey = focusKey || '';
    $.post('manage_filters_modal.php', {
        action: 'get_filters'
    }, function(response) {
        if (response.success) {
            renderFilters(response.data, focusKey);
        }
    }, 'json');
}

function renderFilters(filters, focusKey) {
    focusKey = focusKey || '';
    let html = '';
    
    if (Object.keys(filters).length === 0) {
        html = '<div class="alert alert-info"><span class="glyphicon glyphicon-info-sign"></span> Belum ada filter. Tambahkan filter pertama di atas.</div>';
    } else {
        for (let key in filters) {
            let filter = filters[key];
            let focusClass = (key === focusKey) ? 'panel-primary' : 'panel-default';
            
            html += `
            <div class="panel ${focusClass}" data-filter-key="${key}" style="margin-bottom: 15px;">
                <div class="panel-heading" style="padding: 10px 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${filter.label}</strong> 
                            <small class="text-muted">(${key})</small>
                            <span class="badge" style="background: #5bc0de; margin-left: 8px;">${filter.options.length}</span>
                        </div>
                        <div>
                            <button class="btn btn-xs btn-warning" onclick="toggleEditLabel('${key}')">
                                <span class="glyphicon glyphicon-pencil"></span>
                            </button>
                            <button class="btn btn-xs btn-danger" onclick="deleteFilter('${key}', '${filter.label}')">
                                <span class="glyphicon glyphicon-trash"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div id="editLabel_${key}" style="display: none; margin-top: 10px;">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="newLabel_${key}" value="${filter.label}">
                            <span class="input-group-btn">
                                <button class="btn btn-success" onclick="saveLabel('${key}')">
                                    <span class="glyphicon glyphicon-ok"></span>
                                </button>
                                <button class="btn btn-default" onclick="toggleEditLabel('${key}')">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="panel-body" style="padding: 10px 15px;">
                    <div class="input-group input-group-sm" style="margin-bottom: 10px;">
                        <input type="text" class="form-control" id="newOption_${key}" placeholder="Add new option...">
                        <span class="input-group-btn">
                            <button class="btn btn-primary" onclick="addOption('${key}')">
                                <span class="glyphicon glyphicon-plus"></span> Add
                            </button>
                        </span>
                    </div>
                    
                    <div id="optionsList_${key}">
                        ${renderOptions(key, filter.options)}
                    </div>
                </div>
            </div>`;
        }
    }
    
    $('#modalFiltersList').html(html);
    
    if (focusKey) {
        setTimeout(function() {
            let target = $(`[data-filter-key="${focusKey}"]`);
            if (target.length) {
                $('.modal-body').animate({
                    scrollTop: target.position().top
                }, 300);
            }
        }, 100);
    }
}

function renderOptions(key, options) {
    if (options.length === 0) {
        return '<p class="text-muted" style="margin: 10px 0; font-style: italic;">No options yet.</p>';
    }
    
    let html = '<div style="max-height: 150px; overflow-y: auto;">';
    options.forEach(function(option) {
        html += `
        <div class="option-tag">
            <span>${option}</span>
            <button class="btn btn-xs btn-danger" onclick="deleteOption('${key}', '${option.replace(/'/g, "\\'")}')">
                <span class="glyphicon glyphicon-remove"></span>
            </button>
        </div>`;
    });
    html += '</div>';
    return html;
}

function showModalAlert(message, type) {
    let html = `
        <div class="alert alert-${type} alert-dismissible" style="margin-bottom: 10px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>`;
    $('#modalAlertContainer').html(html);
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}

function addOption(key) {
    let option = $('#newOption_' + key).val().trim();
    
    if (!option) {
        showModalAlert('Please enter an option', 'warning');
        return;
    }
    
    $.post('manage_filters_modal.php', {
        action: 'add_option',
        key: key,
        option: option
    }, function(response) {
        if (response.success) {
            showModalAlert(response.message, 'success');
            $('#newOption_' + key).val('');
            $('#optionsList_' + key).html(renderOptions(key, response.data.options));
            $(`[data-filter-key="${key}"] .badge`).text(response.data.options.length);
        } else {
            showModalAlert(response.message, 'danger');
        }
    }, 'json');
}

function deleteOption(key, option) {
    if (!confirm('Delete this option?')) return;
    
    $.post('manage_filters_modal.php', {
        action: 'delete_option',
        key: key,
        option: option
    }, function(response) {
        if (response.success) {
            showModalAlert(response.message, 'success');
            $('#optionsList_' + key).html(renderOptions(key, response.data.options));
            $(`[data-filter-key="${key}"] .badge`).text(response.data.options.length);
        } else {
            showModalAlert(response.message, 'danger');
        }
    }, 'json');
}

function toggleEditLabel(key) {
    $('#editLabel_' + key).slideToggle();
}

function saveLabel(key) {
    let label = $('#newLabel_' + key).val().trim();
    
    if (!label) {
        showModalAlert('Label cannot be empty', 'warning');
        return;
    }
    
    $.post('manage_filters_modal.php', {
        action: 'update_label',
        key: key,
        label: label
    }, function(response) {
        if (response.success) {
            showModalAlert(response.message, 'success');
            loadFilters(key);
        } else {
            showModalAlert(response.message, 'danger');
        }
    }, 'json');
}

function deleteFilter(key, label) {
    if (!confirm(`Delete filter "${label}" and all its options?`)) return;
    
    $.post('manage_filters_modal.php', {
        action: 'delete_filter',
        key: key
    }, function(response) {
        if (response.success) {
            showModalAlert(response.message, 'success');
            renderFilters(response.data);
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showModalAlert(response.message, 'danger');
        }
    }, 'json');
}

$(document).ready(function(){
    // Show submenu info
    $('#has_submenu').change(function(){
        if($(this).is(':checked')) {
            $('#submenu_info').slideDown();
        } else {
            $('#submenu_info').slideUp();
        }
    });

    // Manage Filters Button
    $('#btnManageFilters').on('click', function(e) {
        e.preventDefault();
        openFilterModal('');
    });
    
    // Edit Filter Button
    $(document).on('click', '.btn-edit-filter', function(e) {
        e.preventDefault();
        let filterKey = $(this).data('filter-key');
        openFilterModal(filterKey);
    });

    // Add Filter Form Submit
    $('#addFilterFormModal').submit(function(e) {
        e.preventDefault();
        
        let key = $('#newFilterKeyModal').val().trim().toLowerCase();
        let label = $('#newFilterLabelModal').val().trim();
        
        $.post('manage_filters_modal.php', {
            action: 'add_filter',
            key: key,
            label: label
        }, function(response) {
            if (response.success) {
                showModalAlert(response.message, 'success');
                $('#newFilterKeyModal, #newFilterLabelModal').val('');
                renderFilters(response.data);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showModalAlert(response.message, 'danger');
            }
        }, 'json');
    });

    // Enter to add option
    $(document).on('keypress', '[id^="newOption_"]', function(e) {
        if (e.which === 13) {
            let key = $(this).attr('id').replace('newOption_', '');
            addOption(key);
        }
    });

    // Dependency handling
    $('input[data-key="process"]').change(function(){
        if($(this).is(':checked')) {
            $('input[data-key="device"]').prop('checked', true);
        }
    });

    $('input[data-key="device"]').change(function(){
        if(!$(this).is(':checked') && $('input[data-key="process"]').is(':checked')) {
            alert('Filter Process membutuhkan Filter Device. Filter Process akan dinonaktifkan.');
            $('input[data-key="process"]').prop('checked', false);
        }
    });
});
</script>