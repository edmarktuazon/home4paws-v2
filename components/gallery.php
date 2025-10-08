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
    error_log("Database connection failed: " . $e->getMessage());
    $images = [];
    $adoptables = [];
}

// Fetch rescued images
try {
    $stmt = $pdo->query("SELECT * FROM rescued_images ORDER BY created_at DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed for rescued images: " . $e->getMessage());
    $images = [];
}

// Fetch adoptable animals
try {
    $stmt = $pdo->query("SELECT * FROM adoptable_animals ORDER BY created_at DESC");
    $adoptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed for adoptable animals: " . $e->getMessage());
    $adoptables = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rescued Animals Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .home-green {
        color: #10B981;
    }
    </style>
</head>

<body class="bg-gray-50">
    <section id="gallery" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4">
            <h2 class="text-4xl md:text-5xl font-bold text-center text-home-green mb-12">Rescued Animals</h2>
            <div id="rescued-gallery" class="columns-1 sm:columns-2 md:columns-3 space-y-4 space-x-4">
                <?php if (empty($images)): ?>
                <p class="text-center text-gray-500 py-8" id="no-rescued-images">No rescued animals yet. Check back
                    soon!</p>
                <?php else: ?>
                <?php foreach ($images as $image): ?>
                <a href="data:image/jpeg;base64,<?php echo htmlspecialchars($image['image_data']); ?>"
                    data-lightbox="rescues" data-image-id="<?php echo $image['id']; ?>">
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($image['image_data']); ?>"
                        alt="Rescued animal" class="w-full rounded-lg object-cover h-64"
                        onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';" />
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <h2 class="text-4xl md:text-5xl font-bold text-center text-home-green mb-12 mt-16">Adoptable Animals</h2>
            <div id="adopt-gallery" class="grid md:grid-cols-3 gap-8">
                <?php if (empty($adoptables)): ?>
                <p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back
                    soon!</p>
                <?php else: ?>
                <?php foreach ($adoptables as $animal): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow"
                    data-image-id="<?php echo $animal['id']; ?>">
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($animal['image_data']); ?>"
                        alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4"
                        onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';" />
                    <div class="flex justify-center">
                        <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank"
                            class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support
                            Me</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-12 text-center">
                <h3 class="text-2xl font-semibold text-home-green mb-4">Give a Loving Home</h3>
                <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
                    These adorable animals are waiting for a forever home. Your adoption can make a difference in their
                    lives!
                </p>
                <div class="flex flex-col justify-center items-center gap-4">
                    <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank"
                        class="inline-block bg-home-green text-white font-semibold py-3 px-6 rounded-lg">
                        Reach Out to Adopt via Messenger!
                    </a>
                    <div class="flex items-center w-full max-w-xs">
                        <div class="flex-grow h-px bg-gray-200"></div>
                        <span class="mx-4 text-gray-400 font-semibold">or</span>
                        <div class="flex-grow h-px bg-gray-200"></div>
                    </div>
                    <a href="mailto:Heartofpaws2010@gmail.com" target="_blank"
                        class="inline-block bg-home-green text-white font-semibold py-3 px-6 rounded-lg">
                        Contact Us via Email
                    </a>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'disableScrolling': true
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
            data: {
                last_rescued_id: lastRescuedId,
                last_adopt_id: lastAdoptId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Fetch images response:', response);
                // Handle new rescued images
                if (response.rescued_images && response.rescued_images.length > 0) {
                    const gallery = $('#rescued-gallery');
                    const noImages = $('#no-rescued-images');
                    if (noImages.length) {
                        noImages.remove();
                    }
                    response.rescued_images.forEach(image => {
                        const newImage = `
                            <a href="data:image/jpeg;base64,${image.image_data}" data-lightbox="rescues" data-image-id="${image.id}">
                                <img src="data:image/jpeg;base64,${image.image_data}" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';" />
                            </a>`;
                        gallery.prepend(newImage);
                        currentRescuedIds.unshift(parseInt(image
                            .id)); // Add to beginning for prepend
                    });
                    lastRescuedId = Math.max(lastRescuedId, ...response.rescued_images.map(img => parseInt(
                        img.id)));
                    console.log('Updated lastRescuedId:', lastRescuedId);
                    lightbox.init();
                }
                // Handle deleted rescued images
                if (response.rescued_ids) {
                    const newRescuedIds = response.rescued_ids.map(id => parseInt(id));
                    const deletedRescuedIds = currentRescuedIds.filter(id => !newRescuedIds.includes(id));
                    deletedRescuedIds.forEach(id => {
                        $(`a[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted rescued image, ID:', id);
                    });
                    currentRescuedIds = newRescuedIds;
                    if (currentRescuedIds.length === 0 && !$('#no-rescued-images').length) {
                        $('#rescued-gallery').append(
                            '<p class="text-center text-gray-500 py-8" id="no-rescued-images">No rescued animals yet. Check back soon!</p>'
                        );
                    }
                }
                // Handle new adoptable animals
                if (response.adopt_images && response.adopt_images.length > 0) {
                    const gallery = $('#adopt-gallery');
                    const noImages = $('#no-adopt-images');
                    if (noImages.length) {
                        noImages.remove();
                    }
                    response.adopt_images.forEach(animal => {
                        const newCard = `
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow" data-image-id="${animal.id}">
                                <img src="data:image/jpeg;base64,${animal.image_data}" alt="Adoptable animal" class="w-full h-48 object-cover rounded-lg mb-4" onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';" />
                                <div class="flex justify-center">
                                    <a href="https://www.facebook.com/messages/t/315034048360145" target="_blank" class="bg-home-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Support Me</a>
                                </div>
                            </div>`;
                        gallery.prepend(newCard);
                        currentAdoptIds.unshift(parseInt(animal
                            .id)); // Add to beginning for prepend
                    });
                    lastAdoptId = Math.max(lastAdoptId, ...response.adopt_images.map(img => parseInt(img
                        .id)));
                    console.log('Updated lastAdoptId:', lastAdoptId);
                    lightbox.init();
                }
                // Handle deleted adoptable animals
                if (response.adopt_ids) {
                    const newAdoptIds = response.adopt_ids.map(id => parseInt(id));
                    const deletedAdoptIds = currentAdoptIds.filter(id => !newAdoptIds.includes(id));
                    deletedAdoptIds.forEach(id => {
                        $(`div[data-image-id="${id}"]`).remove();
                        console.log('Removed deleted adoptable animal, ID:', id);
                    });
                    currentAdoptIds = newAdoptIds;
                    if (currentAdoptIds.length === 0 && !$('#no-adopt-images').length) {
                        $('#adopt-gallery').append(
                            '<p class="text-center text-gray-500 py-8" id="no-adopt-images">No adoptable animals yet. Check back soon!</p>'
                        );
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Polling error:', status, error, xhr.responseText);
            },
            complete: function() {
                setTimeout(pollImages, 3000); // Poll every 3 seconds
            }
        });
    }
    pollImages();
    </script>
</body>

</html>