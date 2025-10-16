<?php
include 'koneksi.php';

// Function untuk dropdown Section (Tier One)
function getTierOne() {
    global $link;
    $sql = "SELECT DISTINCT sect_name FROM section ORDER BY sect_name";
    $result = mysqli_query($link, $sql);
    
    while($row = mysqli_fetch_array($result)) {
        echo '<option value="' . htmlspecialchars($row['sect_name']) . '">' . htmlspecialchars($row['sect_name']) . '</option>';
    }
}

// Handler untuk AJAX requests
if (isset($_GET['func'])) {
    $func = $_GET['func'];
    
    // Cascading: Device → Process
    if ($func == "device" && isset($_GET['drop_var2'])) {
        $device = mysqli_real_escape_string($link, $_GET['drop_var2']);
        
        if ($device == 'General Production' || $device == 'General production') {
            echo '<select name="proc" class="form-control">
                    <option value=""> --- Select Process --- </option>
                    <option value="General Process">General Process</option>
                  </select>';
        } else {
            $sql = "SELECT DISTINCT process FROM docu WHERE device='$device' AND process != '' ORDER BY process";
            $result = mysqli_query($link, $sql);
            
            echo '<select name="proc" class="form-control">
                    <option value=""> --- Select Process --- </option>
                    <option value="-">-</option>';
            
            while($row = mysqli_fetch_array($result)) {
                echo '<option value="' . htmlspecialchars($row['process']) . '">' . htmlspecialchars($row['process']) . '</option>';
            }
            
            echo '</select>';
        }
    }
    
    // Cascading: Section → Device
    if ($func == "section" && isset($_GET['drop_var'])) {
        $section = mysqli_real_escape_string($link, $_GET['drop_var']);
        
        // Query device dari database
        $sql = "SELECT * FROM device ORDER BY name";
        $result = mysqli_query($link, $sql);
        
        // Collect devices dari database
        $dbDevices = [];
        while($row = mysqli_fetch_array($result)) {
            $dbDevices[] = $row['name'];
        }
        
        // Start output
        echo '<select id="device" name="device" class="form-control">';
        echo '<option value=""> --- Select Device --- </option>';
        echo '<option value="General production">General production</option>';
        echo '<option value="General PC">General PC</option>';
        echo '<option value="New Remocon">New Remocon</option>';
        echo '<option value="PI">PI</option>';
        echo '<option value="PC300N">PC300N</option>';
        echo '<option value="PC400 N">PC400 N</option>';
        echo '<option value="PT-GL">PT-GL</option>';
        echo '<option value="SC-63">SC-63</option>';
        echo '<option value="SC-63 Matrix">SC-63 Matrix</option>';
        echo '<option value="DDS">DDS</option>';
        echo '<option value="Smoke Sensor">Smoke Sensor</option>';
        echo '<option value="SOP 4 PIN">SOP 4 PIN</option>';
        echo '<option value="SSR 400">SSR 400</option>';
        
        // Tambahkan device dari database yang belum ada di hardcoded list
        $hardcodedDevices = ['General production', 'General PC', 'New Remocon', 'PI', 'PC300N', 
                             'PC400 N', 'PT-GL', 'SC-63', 'SC-63 Matrix', 'DDS', 
                             'Smoke Sensor', 'SOP 4 PIN', 'SSR 400'];
        
        foreach ($dbDevices as $devName) {
            if (!in_array($devName, $hardcodedDevices)) {
                echo '<option value="' . htmlspecialchars($devName) . '">' . htmlspecialchars($devName) . '</option>';
            }
        }
        
        echo '</select>';
    }
}
?>