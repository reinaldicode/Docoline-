<?php
include 'koneksi.php';

// Handler untuk AJAX requests di upload.php
if (isset($_GET['func'])) {
    $func = $_GET['func'];
    
    // Cascading: Device → Section (untuk upload page)
    if ($func == "device" && isset($_GET['drop_var'])) {
        $device = mysqli_real_escape_string($link, $_GET['drop_var']);
        
        if ($device == 'General Production') {
            echo '<select name="section" class="form-control">
                    <option value="Production"> Production </option>
                  </select>';
        } else {
            // Ambil section dari device
            $sql = "SELECT DISTINCT section FROM docu WHERE device='$device' AND section != '' ORDER BY section";
            $result = mysqli_query($link, $sql);
            
            echo '<select name="section" class="form-control">
                    <option value=""> --- Select Section --- </option>';
            
            while($row = mysqli_fetch_array($result)) {
                echo '<option value="' . htmlspecialchars($row['section']) . '">' . htmlspecialchars($row['section']) . '</option>';
            }
            
            echo '</select>';
        }
    }
}
?>