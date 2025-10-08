<?php
// Database config
$host = 'localhost';
$dbname = 'home4paws_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    $images = [];
}

try {
    $stmt = $pdo->query("SELECT * FROM rescued_images ORDER BY created_at DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    $images = [];
}
?>
<section class="py-20 min-h-[70dvh] grid place-content-center bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-4xl md:text-5xl font-bold text-[#718a47] mb-22">
                Rescued Animals
            </h2>
            <div id="rescued-gallery" class="columns-1 sm:columns-2 md:columns-3 space-y-4 space-x-4">
                <?php if (empty($images)): ?>
                    <p class="text-center text-gray-500 py-8" id="no-rescued-images">No rescued animals yet.</p>
                <?php else: ?>
                    <?php foreach ($images as $image): ?>
                        <a href="data:image/jpeg;base64,<?= htmlspecialchars($image['image_data']) ?>" data-lightbox="rescues" data-image-id="<?= $image['id'] ?>">
                            <img src="data:image/jpeg;base64,<?= htmlspecialchars($image['image_data']) ?>" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';">
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
    let lastRescuedId = <?= !empty($images) ? max(array_column($images, 'id')) : 0 ?>;
    let currentRescuedIds = [<?= !empty($images) ? implode(',', array_column($images, 'id')) : '' ?>];

    function pollRescuedImages() {
        $.ajax({
            url: 'fetch_images.php',
            type: 'GET',
            data: { last_rescued_id: lastRescuedId },
            dataType: 'json',
            success: function(response) {
                const gallery = $('#rescued-gallery');
                const noImages = $('#no-rescued-images');

                if (response.rescued_images && response.rescued_images.length > 0) {
                    if (noImages.length) noImages.remove();
                    response.rescued_images.forEach(img => {
                        const newImage = `
                            <a href="data:image/jpeg;base64,${img.image_data}" data-lightbox="rescues" data-image-id="${img.id}">
                                <img src="data:image/jpeg;base64,${img.image_data}" alt="Rescued animal" class="w-full rounded-lg object-cover h-64" onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found';" />
                            </a>`;
                        gallery.prepend(newImage);
                        currentRescuedIds.unshift(parseInt(img.id));
                    });
                    lastRescuedId = Math.max(lastRescuedId, ...response.rescued_images.map(img => parseInt(img.id)));
                    lightbox.init();
                }

                if (response.rescued_ids) {
                    const newIds = response.rescued_ids.map(id => parseInt(id));
                    const deleted = currentRescuedIds.filter(id => !newIds.includes(id));
                    deleted.forEach(id => $(`a[data-image-id="${id}"]`).remove());
                    currentRescuedIds = newIds;
                    if (currentRescuedIds.length === 0 && !$('#no-rescued-images').length) {
                        gallery.append('<p class="text-center text-gray-500 py-8" id="no-rescued-images">No rescued animals yet.</p>');
                    }
                }
            },
            complete: function() {
                setTimeout(pollRescuedImages, 3000);
            }
        });
    }

    pollRescuedImages();
    </script>
