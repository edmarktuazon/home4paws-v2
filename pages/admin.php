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
    die("Connection failed: " . $e->getMessage());
}

// Handle upload for rescued
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && !isset($_POST['adopt'])) {
    $file = $_FILES['image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
    
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
    
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => "File size exceeds 2MB limit."]);
        exit;
    }
    
    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO rescued_images (image_data, created_at) VALUES (?, NOW())");
        $stmt->execute([$imageData]);
        $imageId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $imageId, 'image_data' => $imageData]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// Handle upload for adoptables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['adopt'])) {
    $file = $_FILES['image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
    
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
    
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => "File size exceeds 2MB limit."]);
        exit;
    }
    
    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO adoptable_animals (image_data, created_at) VALUES (?, NOW())");
        $stmt->execute([$imageData]);
        $imageId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $imageId, 'image_data' => $imageData]);
    } catch (PDOException $e) {
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
    $images = [];
    error_log("Failed to fetch rescued images: " . $e->getMessage());
}

// Fetch adoptable animals
try {
    $stmt = $pdo->query("SELECT * FROM adoptable_animals ORDER BY created_at DESC");
    $adoptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $adoptables = [];
    error_log("Failed to fetch adoptable animals: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Rescue Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
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

    .sidebar.open {
        left: 0;
    }

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

    .overlay.open {
        opacity: 1;
        visibility: visible;
    }

    @media (min-width: 769px) {
        .sidebar {
            left: 0;
        }

        .main-content {
            margin-left: 250px;
        }

        .mobile-menu-btn {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            height: 100vh;
        }
    }

    .home-green {
        color: #10B981;
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
    </style>
</head>

<body class="bg-gray-50">
    <header class="md:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold home-green text-center">Home4Paws</h1>
            <button id="mobile-menu-btn"
                class="mobile-menu-btn p-2 rounded-md text-gray-600 hover:text-home-green focus:outline-none focus:ring-2 focus:ring-home-green">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </div>
    </header>
    <div id="overlay" class="overlay"></div>
    <nav id="sidebar" class="sidebar bg-white shadow-lg">
        <div class="p-6 flex flex-col h-full">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold home-green text-center">Home4Paws</h1>
                <button id="close-menu-btn"
                    class="md:hidden p-2 rounded-md text-gray-600 hover:text-home-green focus:outline-none focus:ring-2 focus:ring-home-green">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <ul class="space-y-4 flex-1">
                <li><a href="#animal-rescued"
                        class="flex items-center space-x-3 text-home-green font-medium p-2 rounded-lg hover:bg-green-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z">
                            </path>
                        </svg><span>Edit Animal Rescued</span></a>
                </li>
                <li><a href="#animal-adopt"
                        class="flex items-center space-x-3 text-home-green font-medium p-2 rounded-lg hover:bg-green-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v4.875h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25">
                            </path>
                        </svg><span>Edit Adopt Animal</span></a>
                </li>
            </ul>
            <div class="mt-auto pt-6 border-t border-gray-200">
                <a href="#"
                    class="flex items-center space-x-3 text-red-600 font-medium p-2 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg><span>Log Out</span>
                </a>
            </div>
        </div>
    </nav>
    <main class="main-content pt-16 md:pt-0">
        <div class="py-8 bg-white border-b">
            <div class="max-w-7xl mx-auto px-4">
                <h3 class="text-2xl font-bold text-home-green mb-4">Upload New Rescued Animal Image</h3>
                <form id="upload-rescued-form" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4">
                    <input type="file" name="image" accept="image/*" required
                        class="flex-1 px-4 py-2 border rounded-lg">
                    <button type="submit"
                        class="bg-home-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Upload</button>
                </form>
                <p id="upload-rescued-status" class="mt-2 text-sm text-gray-600 status-message"></p>
            </div>
        </div>
        <section id="animal-rescued" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="text-4xl md:text-5xl font-bold text-center home-green mb-12">Rescued Animals (Gallery)</h2>
                <div id="rescued-gallery" class="columns-1 sm:columns-2 md:columns-3 space-y-4">
                    <?php if (empty($images)): ?>
                    <p class="text-center text-gray-500" id="no-rescued-images">No images yet. Upload some to get
                        started!</p>
                    <?php else: ?>
                    <?php foreach ($images as $image): ?>
                    <div class="relative break-inside-avoid" data-image-id="<?php echo $image['id']; ?>">
                        <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($image['image_data']); ?>"
                            alt="Rescued animal" class="w-full rounded-lg object-cover h-64"
                            onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                        <div class="absolute bottom-3 right-3 flex space-x-2 opacity-95">
                            <button class="delete-btn text-white cursor-pointer" data-id="<?php echo $image['id']; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <div class="py-8 bg-white border-b" id="animal-adopt">
            <div class="max-w-7xl mx-auto px-4">
                <h3 class="text-2xl font-bold text-home-green mb-4">Add New Adoptable Animal</h3>
                <form id="upload-adopt-form" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="adopt" value="1">
                    <div class="grid md:grid-cols-3 gap-4">
                        <input type="file" name="image" accept="image/*" required
                            class="px-4 py-2 border rounded-lg md:col-span-3">
                    </div>
                    <button type="submit"
                        class="bg-home-green text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Add
                        Animal</button>
                </form>
                <p id="upload-adopt-status" class="mt-2 text-sm text-gray-600 status-message"></p>
            </div>
        </div>
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="text-4xl md:text-5xl font-bold text-center home-green mb-12">Adoptable Animals (Cards)</h2>
                <div id="adopt-gallery" class="grid md:grid-cols-3 gap-8">
                    <?php if (empty($adoptables)): ?>
                    <p class="text-center text-gray-500" id="no-adopt-images">No adoptable animals yet. Add some to get
                        started!</p>
                    <?php else: ?>
                    <?php foreach ($adoptables as $animal): ?>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow"
                        data-image-id="<?php echo $animal['id']; ?>">
                        <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($animal['image_data']); ?>"
                            alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4"
                            onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                        <div class="flex justify-between items-center">
                            <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank"
                                class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support
                                Me</a>
                            <button class="delete-btn text-white cursor-pointer" data-id="<?php echo $animal['id']; ?>"
                                data-adopt="1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
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

    // AJAX form submission for rescued image upload
    $('#upload-rescued-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const status = $('#upload-rescued-status');
        status.text('Loading...').removeClass('text-green-600 text-red-600 hidden').css('opacity', '1');
        console.log('Starting rescued image upload');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Rescued upload response:', response);
                if (response.success) {
                    status.text('Image uploaded successfully!').addClass('text-green-600').css(
                        'opacity', '1');
                    setTimeout(() => {
                        status.addClass('hidden').css('opacity', '0');
                    }, 4000);
                    const gallery = $('#rescued-gallery');
                    const noImages = $('#no-rescued-images');
                    if (noImages.length) {
                        noImages.remove();
                    }
                    const newImage = `
                        <div class="relative break-inside-avoid" data-image-id="${response.id}">
                            <img src="data:image/jpeg;base64,${response.image_data}" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                            <div class="absolute bottom-3 right-3 flex space-x-2 opacity-95">
                                <button class="delete-btn text-white cursor-pointer" data-id="${response.id}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>`;
                    gallery.prepend(newImage);
                    console.log('Rescued image appended to gallery, ID:', response.id);
                    lightbox.init();
                    $('#upload-rescued-form')[0].reset();
                } else {
                    status.text(response.error || 'Upload failed.').addClass('text-red-600').css(
                        'opacity', '1');
                    setTimeout(() => {
                        status.addClass('hidden').css('opacity', '0');
                    }, 4000);
                    console.error('Rescued upload error response:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Rescued upload AJAX error:', status, error, xhr.responseText);
                status.text('Upload failed. Please try again.').addClass('text-red-600').css(
                    'opacity', '1');
                setTimeout(() => {
                    status.addClass('hidden').css('opacity', '0');
                }, 4000);
            }
        });
    });

    // AJAX form submission for adoptable image upload
    $('#upload-adopt-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const status = $('#upload-adopt-status');
        status.text('Loading...').removeClass('text-green-600 text-red-600 hidden').css('opacity', '1');
        console.log('Starting adoptable image upload');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Adoptable upload response:', response);
                if (response.success) {
                    status.text('Animal added successfully!').addClass('text-green-600').css(
                        'opacity', '1');
                    setTimeout(() => {
                        status.addClass('hidden').css('opacity', '0');
                    }, 4000);
                    const gallery = $('#adopt-gallery');
                    const noImages = $('#no-adopt-images');
                    if (noImages.length) {
                        noImages.remove();
                    }
                    const newCard = `
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="${response.id}">
                            <img src="data:image/jpeg;base64,${response.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4" onerror="this.src='https://placehold.co/300x200?text=Image+Not+Found';this.onerror=null;" />
                            <div class="flex justify-between items-center">
                                <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                <button class="delete-btn text-white cursor-pointer" data-id="${response.id}" data-adopt="1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>`;
                    gallery.prepend(newCard);
                    console.log('Adoptable animal appended to gallery, ID:', response.id);
                    lightbox.init();
                    $('#upload-adopt-form')[0].reset();
                } else {
                    status.text(response.error || 'Upload failed.').addClass('text-red-600').css(
                        'opacity', '1');
                    setTimeout(() => {
                        status.addClass('hidden').css('opacity', '0');
                    }, 4000);
                    console.error('Adoptable upload error response:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Adoptable upload AJAX error:', status, error, xhr.responseText);
                status.text('Upload failed. Please try again.').addClass('text-red-600').css(
                    'opacity', '1');
                setTimeout(() => {
                    status.addClass('hidden').css('opacity', '0');
                }, 4000);
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
        if (!confirm('Are you sure you want to delete this ' + (isAdopt ? 'adoptable animal' : 'image') +
                '?')) {
            return;
        }
        console.log('Deleting ' + (isAdopt ? 'adoptable animal' : 'rescued image') + ', ID:', imageId);
        $.ajax({
            url: '',
            type: 'POST',
            data: {
                delete_id: imageId,
                adopt: isAdopt ? 1 : undefined
            },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    $(`div[data-image-id="${response.id}"]`).remove();
                    console.log((isAdopt ? 'Adoptable animal' : 'Rescued image') +
                        ' removed from gallery, ID:', response.id);
                    if (gallery.children().length === 0) {
                        gallery.append(
                            `<p class="text-center text-gray-500" id="${noImagesId.slice(1)}">No ${isAdopt ? 'adoptable animals' : 'images'} yet. ${isAdopt ? 'Add' : 'Upload'} some to get started!</p>`
                        );
                    }
                } else {
                    console.error('Delete error response:', response.error);
                    alert('Failed to delete ' + (isAdopt ? 'adoptable animal' : 'image') + ': ' + (
                        response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete AJAX error:', status, error, xhr.responseText);
                alert('Failed to delete ' + (isAdopt ? 'adoptable animal' : 'image') +
                    '. Please try again.');
            }
        });
    });
    </script>
</body>

</html>