<?php
// Database configuration
$host = 'localhost';
$dbname = 'home4paws_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die(json_encode(['error' => "Database connection failed: " . $e->getMessage()]));
}

// Function to compress image (with fallback if GD is unavailable)
function compressImage($fileTmpName, $fileType, $maxWidth = 800, $maxHeight = 800, $quality = 60) {
    if (!extension_loaded('gd')) {
        error_log("GD extension not loaded. Using raw file data for $fileTmpName");
        return file_get_contents($fileTmpName);
    }

    if (!file_exists($fileTmpName) || !is_readable($fileTmpName)) {
        error_log("Cannot read file: $fileTmpName");
        return file_get_contents($fileTmpName);
    }

    list($width, $height) = @getimagesize($fileTmpName) ?: [0, 0];
    if ($width == 0 || $height == 0) {
        error_log("Invalid image dimensions for $fileTmpName");
        return file_get_contents($fileTmpName);
    }

    $ratio = $width / $height;
    if ($width > $maxWidth || $height > $maxHeight) {
        if ($maxWidth / $maxHeight > $ratio) {
            $maxWidth = $maxHeight * $ratio;
        } else {
            $maxHeight = $maxWidth / $ratio;
        }
    }

    $src = null;
    if ($fileType == 'jpeg' || $fileType == 'jpg') {
        $src = @imagecreatefromjpeg($fileTmpName);
    } else if ($fileType == 'png') {
        $src = @imagecreatefrompng($fileTmpName);
    } else if ($fileType == 'gif') {
        $src = @imagecreatefromgif($fileTmpName);
    } else {
        error_log("Unsupported file type: $fileType. Using raw file data.");
        return file_get_contents($fileTmpName);
    }

    if (!$src) {
        error_log("Failed to create image from $fileTmpName");
        return file_get_contents($fileTmpName);
    }

    $dst = imagecreatetruecolor($maxWidth, $maxHeight);
    if (!$dst) {
        error_log("Failed to create truecolor image for $fileTmpName");
        imagedestroy($src);
        return file_get_contents($fileTmpName);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $maxHeight, $width, $height);
    ob_start();
    imagejpeg($dst, null, $quality);
    $compressed = ob_get_contents();
    ob_end_clean();
    imagedestroy($src);
    imagedestroy($dst);
    error_log("Compressed image size: " . strlen($compressed) . " bytes");
    return $compressed;
}

// Handle upload for rescued
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && !isset($_POST['adopt'])) {
    $file = $_FILES['image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExt, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid file extension. Allowed: " . implode(", ", $allowedExts)]);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => "Upload error: " . $file['error']]);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        http_response_code(400);
        echo json_encode(['error' => "File size exceeds 10MB limit."]);
        exit;
    }

    $compressedData = compressImage($file['tmp_name'], $fileExt);
    $imageData = base64_encode($compressedData);
    error_log("Base64 image size for rescued: " . strlen($imageData) . " bytes");

    try {
        $stmt = $pdo->prepare("INSERT INTO rescued_images (image_data, created_at) VALUES (?, NOW())");
        $stmt->execute([$imageData]);
        $imageId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $imageId, 'image_data' => $imageData]);
    } catch (PDOException $e) {
        error_log("Database error on rescued upload: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Handle upload for adoptables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['adopt'])) {
    $file = $_FILES['image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExt, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid file extension. Allowed: " . implode(", ", $allowedExts)]);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => "Upload error: " . $file['error']]);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        http_response_code(400);
        echo json_encode(['error' => "File size exceeds 10MB limit."]);
        exit;
    }

    $compressedData = compressImage($file['tmp_name'], $fileExt);
    $imageData = base64_encode($compressedData);
    error_log("Base64 image size for adoptable: " . strlen($imageData) . " bytes");

    try {
        $stmt = $pdo->prepare("INSERT INTO adoptable_animals (image_data, created_at) VALUES (?, NOW())");
        $stmt->execute([$imageData]);
        $imageId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $imageId, 'image_data' => $imageData]);
    } catch (PDOException $e) {
        error_log("Database error on adoptable upload: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Handle delete for rescued via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && !isset($_POST['adopt'])) {
    $id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM rescued_images WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        error_log("Database error on rescued delete: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Handle delete for adoptables via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && isset($_POST['adopt'])) {
    $id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM adoptable_animals WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        error_log("Database error on adoptable delete: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Fetch rescued images
try {
    $stmt = $pdo->query("SELECT * FROM rescued_images ORDER BY created_at DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch rescued images: " . $e->getMessage());
    $images = [];
}

// Fetch adoptable animals
try {
    $stmt = $pdo->query("SELECT * FROM adoptable_animals ORDER BY created_at DESC");
    $adoptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch adoptable animals: " . $e->getMessage());
    $adoptables = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home4Paws | Admin </title>
    <meta name="description"
        content="Home4Paws is a non-profit animal rescue organization in Camarines Sur dedicated to spay/neuter, adoption, and animal welfare education." />
    <meta name="keywords"
        content="animal rescue, pet adoption, Camarines Sur, spay and neuter, animal welfare, Home4Paws" />
    <meta name="robots" content="index, follow" />
    <meta property="og:title" content="Home4Paws | Animal Rescue & Welfare" />
    <meta property="og:description"
        content="Support animal rescue efforts in Camarines Sur through donations, adoptions, and education." />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Home4Paws | Animal Rescue & Welfare" />
    <meta name="twitter:description"
        content="Support animal rescue efforts in Camarines Sur through donations, adoptions, and education." />
    <link rel="stylesheet" href="./src/output.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: -250px;
        background-color: #f8fafc;
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
        transition: left 0.3s ease-in-out;
        z-index: 50;
    }
    .sidebar.open { left: 0; }
    .main-content {
        margin-left: 0;
        transition: margin-left 0.3s ease-in-out;
    }
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
    }
    .overlay.open { opacity: 1; visibility: visible; }
    @media (min-width: 769px) {
        .sidebar { left: 0; }
        .main-content { margin-left: 250px; }
        .mobile-menu-btn { display: none; }
    }
    @media (max-width: 768px) {
        .sidebar { height: 100vh; }
    }

    .delete-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(239, 68, 68, 0.9);
        backdrop-filter: blur(4px);
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .delete-btn:hover {
        background-color: rgb(220, 38, 38);
        transform: scale(1.1);
    }
    .delete-btn svg {
        width: 20px;
        height: 20px;
    }
    .status-message {
        transition: opacity 0.5s ease;
    }
    .status-message.hidden {
        opacity: 0;
        visibility: hidden;
    }
    #debug-message { 
        position: fixed; 
        top: 10px; 
        right: 10px; 
        background-color: rgba(255, 0, 0, 0.8); 
        color: white; 
        padding: 10px; 
        border-radius: 5px; 
        display: none; 
        z-index: 1000; 
        max-width: 300px; 
        word-wrap: break-word; 
    }
    </style>
</head>
<body class="bg-gray-50">
    <div id="debug-message"></div>
    <header class="md:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold home-green text-center text-[#718a47]">Home4Paws</h1>
            <button id="mobile-menu-btn" class="mobile-menu-btn p-2 rounded-md text-gray-600 hover:text-[#718a47] focus:outline-none focus:ring-2 focus:ring-home-green">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </header>
    <div id="overlay" class="overlay"></div>
    <nav id="sidebar" class="sidebar bg-white shadow-lg">
        <div class="p-6 flex flex-col h-full">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold home-green text-center text-[#718a47]">Home4Paws</h1>
                <button id="close-menu-btn" class="md:hidden p-2 rounded-md text-gray-600 hover:text-[#718a47] focus:outline-none focus:ring-2 focus:ring-home-green">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <ul class="space-y-4 flex-1">
                <li><a href="#animal-rescued" class="flex items-center space-x-3 font-medium p-2 rounded-lg hover:bg-green-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"></path>
                    </svg><span>Edit Animal Rescued</span></a>
                </li>
                <li><a href="#animal-adopt" class="flex items-center space-x-3  font-medium p-2 rounded-lg hover:bg-green-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v4.875h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"></path>
                    </svg><span>Edit Adopt Animal</span></a>
                </li>
            </ul>
            <div class="mt-auto pt-6 border-t border-gray-200">
                <a href="#" class="flex items-center space-x-3 text-red-600 font-medium p-2 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg><span>Log Out</span>
                </a>
            </div>
        </div>
    </nav>
    <main class="main-content pt-16 md:pt-0">
         <div class="py-8 bg-white border-b" id="animal-adopt">
            <div class="max-w-7xl mx-auto px-4">
                <h3 class="text-2xl font-bold text-[#718a47] mb-4">Add New Adoptable Animal</h3>
                <form id="upload-adopt-form" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="adopt" value="1">
                    <div class="grid md:grid-cols-3 gap-4">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required class="px-4 py-2 border rounded-lg md:col-span-3">
                    </div>
                    <button type="submit" class="bg-[#718a47] text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Add Animal</button>
                </form>
                <p id="upload-adopt-status" class="mt-2 text-sm text-gray-600 status-message"></p>
            </div>
        </div>
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="text-4xl md:text-5xl font-bold text-center home-green mb-12">Adoptable Animals (Cards)</h2>
                <div id="adopt-gallery" class="grid md:grid-cols-3 gap-8">
                    <?php if (empty($adoptables)): ?>
                        <p class="text-center text-gray-500" id="no-adopt-images">No adoptable animals yet. Add some to get started!</p>
                    <?php else: ?>
                        <?php foreach ($adoptables as $animal): ?>
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="<?php echo $animal['id']; ?>">
                                <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($animal['image_data']); ?>" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                <div class="flex justify-between items-center">
                                    <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-[#718a47] text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                    <button class="delete-btn text-white cursor-pointer" data-id="<?php echo $animal['id']; ?>" data-adopt="1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <div class="py-8 bg-white border-b">
            <div class="max-w-7xl mx-auto px-4">
                <h3 class="text-2xl font-bold text-[#718a47] mb-4">Upload New Rescued Animal Image</h3>
                <form id="upload-rescued-form" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required class="flex-1 px-4 py-2 border rounded-lg">
                    <button type="submit" class="bg-[#718a47] text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Upload</button>
                </form>
                <p id="upload-rescued-status" class="mt-2 text-sm text-gray-600 status-message"></p>
            </div>
        </div>
        <section id="animal-rescued" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="text-4xl md:text-5xl font-bold text-center home-green mb-12">Rescued Animals (Gallery)</h2>
                <div id="rescued-gallery" class="columns-1 sm:columns-2 md:columns-3 space-y-4">
                    <?php if (empty($images)): ?>
                        <p class="text-center text-gray-500" id="no-rescued-images">No images yet. Upload some to get started!</p>
                    <?php else: ?>
                        <?php foreach ($images as $image): ?>
                            <div class="relative break-inside-avoid" data-image-id="<?php echo $image['id']; ?>">
                                <a href="data:image/jpeg;base64,<?php echo htmlspecialchars($image['image_data']); ?>" data-lightbox="rescues">
                                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($image['image_data']); ?>" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                </a>
                                <div class="absolute bottom-3 right-3 flex space-x-2 opacity-95">
                                    <button class="delete-btn text-white cursor-pointer" data-id="<?php echo $image['id']; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'disableScrolling': true
    });

    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    mobileMenuBtn.addEventListener('click', openSidebar);
    closeMenuBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    const navLinks = sidebar.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 769) {
                closeSidebar();
            }
        });
    });

    // Client-side validation for file size
    function validateFileSize(file, maxSizeMB) {
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        if (file.size > maxSizeBytes) {
            return `File size exceeds ${maxSizeMB}MB limit.`;
        }
        return null;
    }

    // AJAX form submission for rescued image upload
    $('#upload-rescued-form').on('submit', function(e) {
        e.preventDefault();
        const file = this.querySelector('input[type="file"]').files[0];
        const status = $('#upload-rescued-status');
        const debugMessage = $('#debug-message');

        if (file) {
            const sizeError = validateFileSize(file, 10);
            if (sizeError) {
                status.text(sizeError).addClass('text-red-600').css('opacity', '1');
                setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                console.error('Client-side validation failed:', sizeError);
                return;
            }
        }

        const formData = new FormData(this);
        status.text('Uploading...').removeClass('text-green-600 text-red-600 hidden').css('opacity', '1');
        console.log('Starting rescued image upload, file size:', file ? file.size : 'N/A');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000, // 60 seconds timeout
            success: function(response) {
                console.log('Rescued upload response:', response);
                if (response.success) {
                    status.text('Image uploaded successfully!').addClass('text-green-600').css('opacity', '1');
                    setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                    $('#upload-rescued-form')[0].reset();
                } else {
                    status.text(response.error || 'Upload failed. Please try again.').addClass('text-red-600').css('opacity', '1');
                    setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                    console.error('Rescued upload error:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Rescued upload AJAX error:', status, error, xhr.status, xhr.responseText);
                let errorMsg = xhr.responseJSON?.error || 'Unknown error. Please try again.';
                if (status === 'parsererror') {
                    errorMsg = 'Server returned invalid response. Check server logs.';
                } else if (status === 'timeout') {
                    errorMsg = 'Upload timed out. Try a smaller image.';
                }
                status.text('Upload failed: ' + errorMsg).addClass('text-red-600').css('opacity', '1');
                debugMessage.text('Upload error: ' + errorMsg).show();
                setTimeout(() => {
                    status.addClass('hidden').css('opacity', '0');
                    debugMessage.fadeOut();
                }, 5000);
            }
        });
    });

    // AJAX form submission for adoptable image upload
    $('#upload-adopt-form').on('submit', function(e) {
        e.preventDefault();
        const file = this.querySelector('input[type="file"]').files[0];
        const status = $('#upload-adopt-status');
        const debugMessage = $('#debug-message');

        if (file) {
            const sizeError = validateFileSize(file, 10);
            if (sizeError) {
                status.text(sizeError).addClass('text-red-600').css('opacity', '1');
                setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                console.error('Client-side validation failed:', sizeError);
                return;
            }
        }

        const formData = new FormData(this);
        status.text('Uploading...').removeClass('text-green-600 text-red-600 hidden').css('opacity', '1');
        console.log('Starting adoptable image upload, file size:', file ? file.size : 'N/A');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000, // 60 seconds timeout
            success: function(response) {
                console.log('Adoptable upload response:', response);
                if (response.success) {
                    status.text('Animal added successfully!').addClass('text-green-600').css('opacity', '1');
                    setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                    $('#upload-adopt-form')[0].reset();
                } else {
                    status.text(response.error || 'Upload failed. Please try again.').addClass('text-red-600').css('opacity', '1');
                    setTimeout(() => status.addClass('hidden').css('opacity', '0'), 4000);
                    console.error('Adoptable upload error:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Adoptable upload AJAX error:', status, error, xhr.status, xhr.responseText);
                let errorMsg = xhr.responseJSON?.error || 'Unknown error. Please try again.';
                if (status === 'parsererror') {
                    errorMsg = 'Server returned invalid response. Check server logs.';
                } else if (status === 'timeout') {
                    errorMsg = 'Upload timed out. Try a smaller image.';
                }
                status.text('Upload failed: ' + errorMsg).addClass('text-red-600').css('opacity', '1');
                debugMessage.text('Upload error: ' + errorMsg).show();
                setTimeout(() => {
                    status.addClass('hidden').css('opacity', '0');
                    debugMessage.fadeOut();
                }, 5000);
            }
        });
    });

    // AJAX delete handler for both rescued and adoptable images
    $('#rescued-gallery, #adopt-gallery').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const imageId = $(this).data('id');
        const isAdopt = $(this).data('adopt') == 1;
        const gallery = isAdopt ? $('#adopt-gallery') : $('#rescued-gallery');
        const noImagesId = isAdopt ? '#no-adopt-images' : '#no-rescued-images';
        const debugMessage = $('#debug-message');
        if (!confirm('Are you sure you want to delete this ' + (isAdopt ? 'adoptable animal' : 'image') + '?')) {
            return;
        }
        console.log('Deleting ' + (isAdopt ? 'adoptable animal' : 'rescued image') + ', ID:', imageId);
        $.ajax({
            url: '',
            type: 'POST',
            data: { delete_id: imageId, adopt: isAdopt ? 1 : undefined },
            dataType: 'json',
            timeout: 10000, // 10 seconds timeout
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    $(`div[data-image-id="${response.id}"]`).remove();
                    console.log((isAdopt ? 'Adoptable animal' : 'Rescued image') + ' removed from gallery, ID:', response.id);
                    if (gallery.children().length === 0) {
                        gallery.append(`<p class="text-center text-gray-500" id="${noImagesId.slice(1)}">No ${isAdopt ? 'adoptable animals' : 'images'} yet. ${isAdopt ? 'Add' : 'Upload'} some to get started!</p>`);
                    }
                } else {
                    console.error('Delete error:', response.error);
                    debugMessage.text('Delete failed: ' + (response.error || 'Unknown error')).show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete AJAX error:', status, error, xhr.status, xhr.responseText);
                let errorMsg = xhr.responseJSON?.error || 'Unknown error. Please try again.';
                if (status === 'parsererror') {
                    errorMsg = 'Server returned invalid response. Check server logs.';
                } else if (status === 'timeout') {
                    errorMsg = 'Delete request timed out. Try again.';
                }
                debugMessage.text('Delete failed: ' + errorMsg).show();
                setTimeout(() => debugMessage.fadeOut(), 5000);
            }
        });
    });

    // Poll for new and deleted images for both rescued and adoptable animals
    let lastRescuedId = <?php echo !empty($images) ? max(array_column($images, 'id')) : 0; ?>;
    let currentRescuedIds = [<?php echo !empty($images) ? implode(',', array_column($images, 'id')) : ''; ?>];
    let lastAdoptId = <?php echo !empty($adoptables) ? max(array_column($adoptables, 'id')) : 0; ?>;
    let currentAdoptIds = [<?php echo !empty($adoptables) ? implode(',', array_column($adoptables, 'id')) : ''; ?>];

    function pollImages() {
        console.log('Polling for images, last rescued ID:', lastRescuedId, 'last adopt ID:', lastAdoptId);
        $.ajax({
            url: 'fetch_images.php',
            type: 'GET',
            data: { last_rescued_id: lastRescuedId, last_adopt_id: lastAdoptId },
            dataType: 'json',
            timeout: 10000, // 10 seconds timeout
            success: function(response) {
                console.log('Fetch images response:', response);
                const debugMessage = $('#debug-message');
                debugMessage.hide();

                // Validate response
                if (!response || typeof response !== 'object') {
                    console.error('Invalid response format:', response);
                    debugMessage.text('Invalid server response. Check console.').show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                    return;
                }

                // Handle new rescued images
                if (response.rescued_images && Array.isArray(response.rescued_images) && response.rescued_images.length > 0) {
                    const gallery = $('#rescued-gallery');
                    const noImages = $('#no-rescued-images');
                    if (noImages.length) {
                        noImages.remove();
                        console.log('Removed no-rescued-images placeholder');
                    }
                    response.rescued_images.forEach(image => {
                        const imageId = parseInt(image.id);
                        if (!currentRescuedIds.includes(imageId) && image.image_data) {
                            const newImage = `
                                <div class="relative break-inside-avoid" data-image-id="${image.id}">
                                    <a href="data:image/jpeg;base64,${image.image_data}" data-lightbox="rescues">
                                        <img src="data:image/jpeg;base64,${image.image_data}" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                    </a>
                                    <div class="absolute bottom-3 right-3 flex space-x-2 opacity-95">
                                        <button class="delete-btn text-white cursor-pointer" data-id="${image.id}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>`;
                            gallery.prepend(newImage);
                            currentRescuedIds.unshift(imageId);
                            console.log('Added new rescued image, ID:', image.id);
                        } else if (!image.image_data) {
                            console.warn('Skipping rescued image with missing image_data, ID:', image.id);
                        }
                    });
                    lastRescuedId = Math.max(lastRescuedId, ...response.rescued_images.map(img => parseInt(img.id)));
                    console.log('Updated lastRescuedId:', lastRescuedId);
                    lightbox.init();
                } else {
                    console.log('No new rescued images in response');
                }

                // Handle deleted rescued images
                if (response.rescued_ids && Array.isArray(response.rescued_ids)) {
                    const newRescuedIds = response.rescued_ids.map(id => parseInt(id));
                    const deletedRescuedIds = currentRescuedIds.filter(id => !newRescuedIds.includes(id));
                    deletedRescuedIds.forEach(id => {
                        $(`div[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted rescued image, ID:', id);
                    });
                    currentRescuedIds = newRescuedIds;
                    if (currentRescuedIds.length === 0 && !$('#no-rescued-images').length) {
                        $('#rescued-gallery').append('<p class="text-center text-gray-500" id="no-rescued-images">No images yet. Upload some to get started!</p>');
                        console.log('Added no-rescued-images placeholder');
                    }
                } else if (response.rescued_ids && !Array.isArray(response.rescued_ids)) {
                    console.error('Invalid rescued_ids format:', response.rescued_ids);
                    debugMessage.text('Invalid server response format for rescued_ids. Check console.').show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                }

                // Handle new adoptable animals
                if (response.adopt_images && Array.isArray(response.adopt_images) && response.adopt_images.length > 0) {
                    const gallery = $('#adopt-gallery');
                    const noImages = $('#no-adopt-images');
                    if (noImages.length) {
                        noImages.remove();
                        console.log('Removed no-adopt-images placeholder');
                    }
                    response.adopt_images.forEach(animal => {
                        const animalId = parseInt(animal.id);
                        if (!currentAdoptIds.includes(animalId) && animal.image_data) {
                            const newCard = `
                                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="${animal.id}">
                                    <img src="data:image/jpeg;base64,${animal.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                                    <div class="flex justify-between items-center">
                                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-[#718a47] text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                        <button class="delete-btn text-white cursor-pointer" data-id="${animal.id}" data-adopt="1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>`;
                            gallery.prepend(newCard);
                            currentAdoptIds.unshift(animalId);
                            console.log('Added new adoptable animal, ID:', animal.id);
                        } else if (!animal.image_data) {
                            console.warn('Skipping adoptable animal with missing image_data, ID:', animal.id);
                        }
                    });
                    lastAdoptId = Math.max(lastAdoptId, ...response.adopt_images.map(img => parseInt(img.id)));
                    console.log('Updated lastAdoptId:', lastAdoptId);
                } else {
                    console.log('No new adoptable animals in response');
                }

                // Handle deleted adoptable animals
                if (response.adopt_ids && Array.isArray(response.adopt_ids)) {
                    const newAdoptIds = response.adopt_ids.map(id => parseInt(id));
                    const deletedAdoptIds = currentAdoptIds.filter(id => !newAdoptIds.includes(id));
                    deletedAdoptIds.forEach(id => {
                        $(`div[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted adoptable animal, ID:', id);
                    });
                    currentAdoptIds = newAdoptIds;
                    if (currentAdoptIds.length === 0 && !$('#no-adopt-images').length) {
                        $('#adopt-gallery').append('<p class="text-center text-gray-500" id="no-adopt-images">No adoptable animals yet. Add some to get started!</p>');
                        console.log('Added no-adopt-images placeholder');
                    }
                } else if (response.adopt_ids && !Array.isArray(response.adopt_ids)) {
                    console.error('Invalid adopt_ids format:', response.adopt_ids);
                    debugMessage.text('Invalid server response format for adopt_ids. Check console.').show();
                    setTimeout(() => debugMessage.fadeOut(), 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Polling error:', status, error, xhr.status, xhr.responseText);
                const debugMessage = $('#debug-message');
                debugMessage.text(`Polling failed: ${status} (${xhr.status}). Retrying...`).show();
                setTimeout(() => debugMessage.fadeOut(), 5000);
            },
            complete: function() {
                setTimeout(pollImages, 5000); // Poll every 5 seconds
            }
        });
    }
    pollImages();
    </script>
</body>
</html>