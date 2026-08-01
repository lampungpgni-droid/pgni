<?php
// check_folder.php - Letakkan di root folder
echo "<h1>Check Folder Upload</h1>";

$folders = [
    'assets/images/ktp',
    'assets/images/kk',
    'assets/images/berita',
    'assets/images/pengurus',
    'assets/images/logo'
];

foreach ($folders as $folder) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $folder;
    echo "<h3>📁 " . $folder . "</h3>";
    echo "Path: " . $path . "<br>";
    
    if (file_exists($path)) {
        echo "✅ Folder exists<br>";
        echo "Is writable: " . (is_writable($path) ? '✅ Yes' : '❌ No') . "<br>";
        
        // Tampilkan isi folder
        $files = scandir($path);
        $file_count = count($files) - 2; // exclude . dan ..
        echo "Files: " . $file_count . "<br>";
        if ($file_count > 0) {
            echo "<ul>";
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $size = filesize($path . '/' . $file);
                    echo "<li>" . $file . " - " . number_format($size / 1024, 2) . " KB</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "❌ Folder NOT exists<br>";
        echo "Mencoba membuat folder...<br>";
        if (mkdir($path, 0777, true)) {
            echo "✅ Folder berhasil dibuat!<br>";
        } else {
            echo "❌ Gagal membuat folder!<br>";
        }
    }
    echo "<hr>";
}

// Test upload
echo "<h2>Test Upload</h2>";
$test_file = 'assets/images/ktp/test.txt';
if (file_put_contents($test_file, 'Test write permission')) {
    echo "✅ Berhasil menulis file test<br>";
    unlink($test_file);
    echo "✅ File test dihapus<br>";
} else {
    echo "❌ Gagal menulis file test<br>";
}

echo "<br><h3>PHP Info</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " seconds<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
?>