<?php
// levels.php
session_start();

// Include file koneksi database
require_once 'config/database.php';

// Periksa apakah group_id dan group_name ada di session
if (!isset($_SESSION['group_id']) || !isset($_SESSION['group_name'])) {
    header("location: index.php");
    exit();
}

// Ambil subject_id dan subject_name dari URL
if (!isset($_GET['subject_id']) || !isset($_GET['subject_name'])) {
    // Jika tidak ada parameter yang diperlukan, redirect kembali ke halaman mata pelajaran
    header("location: subjects.php");
    exit();
}

$subjectId = intval($_GET['subject_id']); // Konversi ke integer untuk keamanan
$subjectName = htmlspecialchars(urldecode($_GET['subject_name'])); // Dekode dan amankan

// Ambil data level dari database
$levels = [];
$sql = "SELECT id, level_name FROM levels ORDER BY id ASC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $levels[] = $row;
    }
    $result->free();
} else {
    echo "Error mengambil data level: " . $conn->error;
}

// Tutup koneksi
$conn->close();

$groupName = $_SESSION['group_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Level - Empow Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .level-grid {
            display: flex; /* Menggunakan flexbox untuk penataan */
            flex-wrap: wrap; /* Jika item terlalu banyak, akan wrap ke bawah */
            justify-content: center; /* Pusatkan item */
            gap: 20px;
            margin-top: 30px;
        }

        .level-card {
            background-color: #d4edda; /* Warna hijau muda */
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex: 1; /* Agar card bisa membesar/mengecil */
            min-width: 250px; /* Lebar minimum */
            max-width: 300px; /* Lebar maksimum */
        }

        .level-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .level-card a {
            text-decoration: none;
            color: #28a745; /* Warna hijau tua */
            font-size: 1.5em;
            font-weight: bold;
            display: block;
            width: 100%;
            height: 100%;
        }

        .level-card a:hover {
            color: #218838;
        }

        .back-button {
            background-color: #6c757d; /* Abu-abu */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 30px;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mata Pelajaran: <?php echo $subjectName; ?></h1>
        <p>Halo, Kelompok **<?php echo htmlspecialchars($groupName); ?>**!</p>
        <p>Pilih level kesulitan untuk mata pelajaran ini:</p>

        <div class="level-grid">
            <?php if (!empty($levels)): ?>
                <?php foreach ($levels as $level): ?>
                    <div class="level-card">
                        <a href="quiz.php?subject_id=<?php echo $subjectId; ?>&level_id=<?php echo $level['id']; ?>">
                            <?php echo $level['level_name']; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada level yang tersedia saat ini.</p>
            <?php endif; ?>
        </div>
        <button class="back-button" onclick="history.back()">Kembali ke Pilihan Mata Pelajaran</button>
    </div>
</body>
</html>